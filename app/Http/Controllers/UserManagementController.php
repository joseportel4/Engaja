<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserManagementRequest;
use App\Models\Municipio;
use App\Models\Participante;
use App\Models\User;
use App\Models\ModeloCertificado;
use App\Imports\ParticipantesPreviewImport;
use App\Exports\UsuariosNaoCadastradosExport;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use App\Exports\UsersExport;
use Maatwebsite\Excel\Facades\Excel;

class UserManagementController extends Controller
{
    private const PROTECTED_ROLES = ['administrador'];

    private const LEGACY_ROLES = ['gestor', 'formador'];

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $users = User::with(['roles', 'participante'])
            ->whereDoesntHave('roles', fn($q) => $q->whereIn('name', self::PROTECTED_ROLES))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'ilike', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(12)
            ->appends(['q' => $search]);

        return view('usuarios.index', [
            'users' => $users,
            'search' => $search,
            'modelosCertificado' => ModeloCertificado::orderBy('nome')->get(['id', 'nome']),
        ]);
    }

    public function edit(User $managedUser): View|RedirectResponse
    {
        if ($this->isProtected($managedUser)) {
            return redirect()
                ->route('usuarios.index')
                ->with('error', 'Este usuario nao pode ser editado.');
        }

        $managedUser->load(['participante.municipio.estado', 'roles']);

        $municipios = Municipio::with('estado')
            ->orderBy('nome')
            ->get(['id', 'nome', 'estado_id']);

        $organizacoes = config('engaja.organizacoes', []);
        $participanteTags = config('engaja.participante_tags', Participante::TAGS);
        $roles = $this->assignableRoles();

        return view('usuarios.edit', [
            'user'             => $managedUser,
            'municipios'       => $municipios,
            'organizacoes'     => $organizacoes,
            'participanteTags' => $participanteTags,
            'roles'            => $roles,
            'currentRole'      => $managedUser->roles->first()?->name,
        ]);
    }

    public function update(UserManagementRequest $request, User $managedUser): RedirectResponse
    {
        if ($this->isProtected($managedUser)) {
            return redirect()
                ->route('usuarios.index')
                ->with('error', 'Este usuario nao pode ser editado.');
        }

        $data = $request->validated();

        $oldEmail = $managedUser->email;
        $managedUser->fill([
            'name'  => $data['name'],
            'email' => $data['email'],

            //campos demograficos
            'identidade_genero'            => $data['identidade_genero'] ?? null,
            'identidade_genero_outro'      => $data['identidade_genero_outro'] ?? null,
            'raca_cor'                     => $data['raca_cor'] ?? null,
            'comunidade_tradicional'       => $data['comunidade_tradicional'] ?? null,
            'comunidade_tradicional_outro' => $data['comunidade_tradicional_outro'] ?? null,
            'faixa_etaria'                 => $data['faixa_etaria'] ?? null,
            'pcd'                          => $data['pcd'] ?? null,
            'orientacao_sexual'            => $data['orientacao_sexual'] ?? null,
            'orientacao_sexual_outra'      => $data['orientacao_sexual_outra'] ?? null,
        ]);

        if ($oldEmail !== $data['email']) {
            $managedUser->email_verified_at = null;
        }

        $managedUser->save();

        $managedUser->participante()->updateOrCreate(
            ['user_id' => $managedUser->id],
            [
                'cpf'              => $data['cpf']              ?? null,
                'telefone'         => $data['telefone']         ?? null,
                'municipio_id'     => $data['municipio_id']     ?? null,
                'escola_unidade'   => $data['escola_unidade']   ?? null,
                'tipo_organizacao' => $data['tipo_organizacao'] ?? null,
                'tag'              => $data['tag']              ?? null,
            ]
        );

        if (auth()->user()->hasRole('administrador')) {
            //se a role vier preenchida no request, aplica. Se vier vazia, remove os acessos.
            $roleToApply = $data['role'] ?? null;

            if ($roleToApply) {
                $managedUser->syncRoles([$roleToApply]);
            } else {
                $managedUser->syncRoles([]);
            }
        }

        return redirect()
            ->route('usuarios.index')
            ->with('success', 'Usuario atualizado com sucesso.');
    }

    private function assignableRoles()
    {
        $rolesToExclude = array_merge(self::PROTECTED_ROLES, self::LEGACY_ROLES);

        return Role::whereNotIn('name', $rolesToExclude)
            ->orderBy('name')
            ->get(['name']);
    }

    private function isProtected(User $user): bool
    {
        return $user->hasAnyRole(self::PROTECTED_ROLES);
    }

    public function export()
    {
        return Excel::download(new UsersExport, 'usuarios.xlsx');
    }

    public function verificarIndex(Request $request): View|RedirectResponse
    {
        $sessionKey = (string) $request->query('session_key', '');
        $rows = collect();
        $resumo = null;
        $rowsPaginator = null;

        if ($sessionKey !== '') {
            $payload = session($sessionKey);
            if (!is_array($payload) || !array_key_exists('rows', $payload)) {
                return redirect()
                    ->route('usuarios.verificar.index')
                    ->withErrors(['arquivo' => 'Sessao de verificacao expirada. Envie o arquivo novamente.']);
            }

            $rows = collect($payload['rows'] ?? [])->values();
            $perPage = (int) $request->query('per_page', 50);
            if (!in_array($perPage, [25, 50, 100, 200], true)) {
                $perPage = 50;
            }

            $page = (int) max(1, $request->query('page', 1));
            $slice = $rows->slice(($page - 1) * $perPage, $perPage)->values();

            $rowsPaginator = new LengthAwarePaginator(
                $slice,
                $rows->count(),
                $perPage,
                $page,
                [
                    'path' => route('usuarios.verificar.index'),
                    'query' => [
                        'session_key' => $sessionKey,
                        'per_page'    => $perPage,
                    ],
                ]
            );

            $resumo = [
                'total_importacao'      => (int) ($payload['total_count'] ?? 0),
                'usuarios_existentes'   => (int) ($payload['existing_count'] ?? 0),
                'usuarios_nao_cadastrados' => (int) ($payload['new_count'] ?? 0),
                'gerado_em'             => $payload['generated_at'] ?? null,
            ];
        }

        return view('usuarios.verificar', [
            'sessionKey' => $sessionKey,
            'resumo' => $resumo,
            'rows' => $rowsPaginator,
        ]);
    }

    public function verificarProcessar(Request $request): RedirectResponse
    {
        $request->validate([
            'arquivo' => 'required|file|mimes:xlsx,xls,csv|max:20480',
        ]);

        try {
            $import = new ParticipantesPreviewImport();
            Excel::import($import, $request->file('arquivo'));

            $rows = collect($import->rows ?? [])->values();
            $resumo = $this->montarResumoVerificacao($rows);

            $sessionKey = 'user_verification_' . Str::uuid();
            session([$sessionKey => [
                'rows'          => $resumo['rows_nao_cadastrados']->values()->all(),
                'existing_count'=> $resumo['usuarios_existentes'],
                'new_count'     => $resumo['usuarios_nao_cadastrados'],
                'total_count'   => $resumo['total_importacao'],
                'generated_at'  => now()->toDateTimeString(),
            ]]);

            return redirect()
                ->route('usuarios.verificar.index', ['session_key' => $sessionKey])
                ->with('success', 'Verificacao concluida com sucesso.');
        } catch (\Throwable $e) {
            return back()
                ->withErrors(['arquivo' => 'Falha ao processar o arquivo: ' . $e->getMessage()])
                ->withInput();
        }
    }

    public function verificarExportar(Request $request, string $format)
    {
        if (!in_array($format, ['csv', 'xlsx'], true)) {
            abort(404);
        }

        $sessionKey = (string) $request->query('session_key', '');
        $payload = session($sessionKey);

        if (!is_array($payload) || !array_key_exists('rows', $payload)) {
            return redirect()
                ->route('usuarios.verificar.index')
                ->withErrors(['arquivo' => 'Sessao de verificacao expirada. Envie o arquivo novamente.']);
        }

        $rows = collect($payload['rows'] ?? [])->values();
        $export = new UsuariosNaoCadastradosExport($rows);
        $filename = 'usuarios-nao-cadastrados-' . now()->format('Ymd_His') . '.' . $format;
        $writerType = $format === 'csv'
            ? \Maatwebsite\Excel\Excel::CSV
            : \Maatwebsite\Excel\Excel::XLSX;

        return Excel::download($export, $filename, $writerType);
    }

    private function montarResumoVerificacao(Collection $rows): array
    {
        $rowsUnicosPorEmail = $rows
            ->filter(function ($row) {
                $email = strtolower(trim((string) ($row['email'] ?? '')));
                return $email !== '';
            })
            ->groupBy(fn($row) => strtolower(trim((string) ($row['email'] ?? ''))))
            ->map(fn($grupo) => $grupo->first())
            ->values();

        $emailsImportacao = $rowsUnicosPorEmail
            ->map(fn($row) => strtolower(trim((string) ($row['email'] ?? ''))))
            ->values();

        $emailsExistentes = $emailsImportacao->isEmpty()
            ? collect()
            : User::whereIn('email', $emailsImportacao)
                ->pluck('email')
                ->map(fn($email) => strtolower(trim((string) $email)))
                ->unique()
                ->values();

        $emailsExistentesLookup = array_fill_keys($emailsExistentes->all(), true);

        $rowsNaoCadastrados = $rowsUnicosPorEmail
            ->filter(function ($row) use ($emailsExistentesLookup) {
                $email = strtolower(trim((string) ($row['email'] ?? '')));
                return !isset($emailsExistentesLookup[$email]);
            })
            ->values();

        return [
            'total_importacao' => $emailsImportacao->count(),
            'usuarios_existentes' => $emailsExistentes->count(),
            'usuarios_nao_cadastrados' => $rowsNaoCadastrados->count(),
            'rows_nao_cadastrados' => $rowsNaoCadastrados,
        ];
    }
}
