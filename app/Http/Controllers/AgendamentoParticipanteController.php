<?php

namespace App\Http\Controllers;

use App\Models\Agendamento;
use App\Models\AgendamentoParticipante;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class AgendamentoParticipanteController extends Controller
{
    public function index(Request $request, Agendamento $agendamento)
    {
        $search = trim((string) $request->query('q', ''));
        $perPage = (int) $request->query('per_page', 25);
        if (!in_array($perPage, [25, 50, 100], true)) {
            $perPage = 25;
        }

        $participantes = $agendamento->participantesClonados()
            ->when($search, function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $like = '%' . $search . '%';
                    $nested->where('nome', 'ilike', $like)
                        ->orWhere('email', 'ilike', $like)
                        ->orWhere('cpf', 'ilike', $like)
                        ->orWhere('telefone', 'ilike', $like);
                });
            })
            ->orderBy('nome')
            ->paginate($perPage)
            ->appends($request->query());

        return view('agendamentos.participantes.index', compact('agendamento', 'participantes', 'search', 'perPage'));
    }

    public function create(Agendamento $agendamento)
    {
        $this->garantirAgendamentoNaoEfetivado($agendamento);

        return view('agendamentos.participantes.create', compact('agendamento'));
    }

    public function store(Request $request, Agendamento $agendamento)
    {
        $this->garantirAgendamentoNaoEfetivado($agendamento);

        $dados = $this->validarParticipante($request, $agendamento);
        $dados['origem'] = 'formulário';
        $dados['turma'] = $agendamento->turma;

        $agendamento->participantesClonados()->create($dados);

        return redirect()
            ->route('agendamentos.participantes.index', $agendamento)
            ->with('success', 'Participante cadastrado com sucesso.');
    }

    public function edit(Agendamento $agendamento, AgendamentoParticipante $participante)
    {
        $this->garantirAgendamentoNaoEfetivado($agendamento);
        $this->garantirParticipanteDoAgendamento($agendamento, $participante);

        return view('agendamentos.participantes.edit', compact('agendamento', 'participante'));
    }

    public function update(Request $request, Agendamento $agendamento, AgendamentoParticipante $participante)
    {
        $this->garantirAgendamentoNaoEfetivado($agendamento);
        $this->garantirParticipanteDoAgendamento($agendamento, $participante);

        $dados = $this->validarParticipante($request, $agendamento, $participante);
        $dados['turma'] = $agendamento->turma;
        $participante->update($dados);

        return redirect()
            ->route('agendamentos.participantes.index', $agendamento)
            ->with('success', 'Participante atualizado com sucesso.');
    }

    public function destroy(Agendamento $agendamento, AgendamentoParticipante $participante)
    {
        $this->garantirAgendamentoNaoEfetivado($agendamento);
        $this->garantirParticipanteDoAgendamento($agendamento, $participante);

        $participante->delete();

        return redirect()
            ->route('agendamentos.participantes.index', $agendamento)
            ->with('success', 'Participante removido com sucesso.');
    }

    public function import(Agendamento $agendamento)
    {
        $this->garantirAgendamentoNaoEfetivado($agendamento);

        return view('agendamentos.participantes.import', compact('agendamento'));
    }

    public function upload(Request $request, Agendamento $agendamento)
    {
        $this->garantirAgendamentoNaoEfetivado($agendamento);

        $dados = $request->validate([
            'arquivo' => 'required|file|mimes:xlsx|max:10240',
            'observacoes' => 'nullable|string|max:2000',
            'aceite_lgpd' => 'accepted',
        ], [
            'arquivo.mimes' => 'Envie somente arquivo .xlsx.',
            'aceite_lgpd.accepted' => 'Você precisa aceitar a declaração LGPD para continuar.',
        ]);

        $rowsSheet = Excel::toArray([], $request->file('arquivo'))[0] ?? [];
        if (empty($rowsSheet)) {
            return back()->withErrors(['arquivo' => 'A planilha está vazia.'])->withInput();
        }

        $headersRaw = array_map(fn ($value) => trim((string) $value), $rowsSheet[0] ?? []);
        $headers = collect($headersRaw)
            ->map(fn ($header) => $this->normalizarCabecalho($header))
            ->all();

        $rows = [];
        foreach (array_slice($rowsSheet, 1) as $lineIndex => $row) {
            $rowMap = $this->mapearLinha($headers, $row);

            $nome = trim((string) ($rowMap['nome'] ?? ''));
            $cpf = $this->normalizarCpf((string) ($rowMap['cpf'] ?? ''));
            $email = strtolower(trim((string) ($rowMap['email'] ?? '')));
            $telefone = $this->normalizarTelefone((string) ($rowMap['telefone'] ?? ''));
            $dataNascimento = $this->normalizarData($rowMap['data_nascimento'] ?? null);
            $vinculo = trim((string) ($rowMap['vinculo'] ?? ''));
            if (
                $nome === '' &&
                $cpf === null &&
                $email === '' &&
                $telefone === null &&
                $vinculo === ''
            ) {
                continue;
            }

            if ($nome === '') {
                return back()
                    ->withErrors(['arquivo' => 'Encontramos linha sem nome na planilha (linha ' . ($lineIndex + 2) . ').'])
                    ->withInput();
            }

            $rows[] = [
                'nome' => $nome,
                'cpf' => $cpf,
                'email' => $email !== '' ? $email : null,
                'data_nascimento' => $dataNascimento,
                'telefone' => $telefone,
                'vinculo' => $vinculo !== '' ? $vinculo : null,
                'turma' => $agendamento->turma,
            ];
        }

        if (empty($rows)) {
            return back()->withErrors(['arquivo' => 'Nenhuma linha válida encontrada na planilha.'])->withInput();
        }

        $sessionKey = 'agendamento_import_' . $agendamento->id . '_' . Str::random(16);
        session([
            $sessionKey => [
                'rows' => $rows,
                'observacoes' => trim((string) ($dados['observacoes'] ?? '')) ?: null,
                'created_at' => now()->toIso8601String(),
            ],
        ]);

        return redirect()->route('agendamentos.participantes.import.preview', [
            'agendamento' => $agendamento,
            'session_key' => $sessionKey,
        ]);
    }

    public function preview(Request $request, Agendamento $agendamento)
    {
        $this->garantirAgendamentoNaoEfetivado($agendamento);

        $sessionKey = (string) $request->query('session_key', '');
        $payload = session($sessionKey);

        if (!is_array($payload) || empty($payload['rows'] ?? [])) {
            return redirect()
                ->route('agendamentos.participantes.import', $agendamento)
                ->withErrors(['arquivo' => 'Sessão expirada. Faça upload da planilha novamente.']);
        }

        $rows = collect($payload['rows']);
        $perPage = (int) $request->query('per_page', 25);
        if (!in_array($perPage, [25, 50, 100], true)) {
            $perPage = 25;
        }

        $page = max(1, (int) $request->query('page', 1));
        $slice = $rows->slice(($page - 1) * $perPage, $perPage)->values();

        $paginator = new LengthAwarePaginator(
            $slice,
            $rows->count(),
            $perPage,
            $page,
            [
                'path' => route('agendamentos.participantes.import.preview', $agendamento),
                'query' => [
                    'session_key' => $sessionKey,
                    'per_page' => $perPage,
                ],
            ]
        );

        return view('agendamentos.participantes.preview', [
            'agendamento' => $agendamento,
            'rows' => $paginator,
            'sessionKey' => $sessionKey,
            'observacoes' => $payload['observacoes'] ?? null,
        ]);
    }

    public function confirm(Request $request, Agendamento $agendamento)
    {
        $this->garantirAgendamentoNaoEfetivado($agendamento);

        $dados = $request->validate([
            'session_key' => 'required|string',
        ]);

        $payload = session($dados['session_key']);
        if (!is_array($payload) || empty($payload['rows'] ?? [])) {
            return redirect()
                ->route('agendamentos.participantes.import', $agendamento)
                ->withErrors(['arquivo' => 'Sessão expirada. Faça upload da planilha novamente.']);
        }

        $observacoes = $payload['observacoes'] ?? null;
        $rows = collect($payload['rows']);

        [$criados, $atualizados] = DB::transaction(function () use ($agendamento, $rows, $observacoes) {
            $criados = 0;
            $atualizados = 0;

            foreach ($rows as $row) {
                $email = strtolower(trim((string) ($row['email'] ?? '')));
                $cpf = $this->normalizarCpf((string) ($row['cpf'] ?? ''));

                $existente = null;
                if ($email !== '') {
                    $existente = $agendamento->participantesClonados()
                        ->whereRaw('LOWER(email) = ?', [$email])
                        ->first();
                }

                if (!$existente && $cpf) {
                    $existente = $agendamento->participantesClonados()
                        ->where('cpf', $cpf)
                        ->first();
                }

                $payloadParticipante = [
                    'nome' => trim((string) ($row['nome'] ?? '')),
                    'cpf' => $cpf,
                    'email' => $email !== '' ? $email : null,
                    'data_nascimento' => $this->normalizarData($row['data_nascimento'] ?? null),
                    'telefone' => $this->normalizarTelefone((string) ($row['telefone'] ?? '')),
                    'vinculo' => $this->normalizarTexto($row['vinculo'] ?? null),
                    'turma' => $agendamento->turma,
                    'origem' => 'importação',
                    'observacoes' => $observacoes,
                ];

                if ($existente) {
                    $existente->update($payloadParticipante);
                    $atualizados++;
                    continue;
                }

                $agendamento->participantesClonados()->create($payloadParticipante);
                $criados++;
            }

            return [$criados, $atualizados];
        });

        session()->forget($dados['session_key']);

        return redirect()
            ->route('agendamentos.participantes.index', $agendamento)
            ->with('success', "Importação concluída. Criados: {$criados}. Atualizados: {$atualizados}.");
    }

    private function validarParticipante(
        Request $request,
        Agendamento $agendamento,
        ?AgendamentoParticipante $participante = null
    ): array
    {
        $dados = $request->validate([
            'nome' => 'required|string|max:255',
            'cpf' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'data_nascimento' => 'nullable|date',
            'telefone' => 'nullable|string|max:25',
            'vinculo' => 'nullable|string|max:100',
            'observacoes' => 'nullable|string|max:2000',
        ]);

        $dados['cpf'] = $this->normalizarCpf((string) ($dados['cpf'] ?? ''));
        $dados['telefone'] = $this->normalizarTelefone((string) ($dados['telefone'] ?? ''));

        if ($dados['cpf'] !== null && strlen($dados['cpf']) !== 11) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'cpf' => 'O CPF deve conter exatamente 11 números.',
            ]);
        }

        if ($dados['cpf'] !== null) {
            $cpfEmUso = $agendamento->participantesClonados()
                ->where('cpf', $dados['cpf'])
                ->when($participante, fn ($query) => $query->where('id', '!=', $participante->id))
                ->exists();

            if ($cpfEmUso) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'cpf' => 'Já existe participante com este CPF neste agendamento.',
                ]);
            }
        }

        if ($dados['telefone'] !== null && !in_array(strlen($dados['telefone']), [10, 11], true)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'telefone' => 'O telefone deve conter 10 ou 11 números.',
            ]);
        }

        if ($dados['telefone'] !== null) {
            $telefoneEmUso = $agendamento->participantesClonados()
                ->where('telefone', $dados['telefone'])
                ->when($participante, fn ($query) => $query->where('id', '!=', $participante->id))
                ->exists();

            if ($telefoneEmUso) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'telefone' => 'Já existe participante com este telefone neste agendamento.',
                ]);
            }
        }

        return $dados;
    }

    private function garantirAgendamentoNaoEfetivado(Agendamento $agendamento): void
    {
        abort_if($agendamento->efetivado, 403, 'Agendamentos efetivados não permitem alteração de participantes.');
    }

    private function normalizarCabecalho(string $header): string
    {
        $slug = Str::slug($header, '_');

        return match ($slug) {
            'nome' => 'nome',
            'cpf' => 'cpf',
            'email', 'e_mail' => 'email',
            'data_nascimento', 'data_de_nascimento', 'nascimento' => 'data_nascimento',
            'telefone', 'celular' => 'telefone',
            'vinculo' => 'vinculo',
            'turma' => 'turma',
            default => $slug,
        };
    }

    private function mapearLinha(array $headers, array $row): array
    {
        $mapped = [];
        foreach ($headers as $index => $header) {
            $mapped[$header] = $row[$index] ?? null;
        }
        return $mapped;
    }

    private function normalizarCpf(string $cpf): ?string
    {
        $digits = preg_replace('/\D+/', '', $cpf);
        return $digits !== '' ? $digits : null;
    }

    private function normalizarTelefone(string $telefone): ?string
    {
        $digits = preg_replace('/\D+/', '', $telefone);
        return $digits !== '' ? $digits : null;
    }

    private function normalizarTexto(mixed $valor): ?string
    {
        $texto = trim((string) $valor);
        return $texto !== '' ? $texto : null;
    }

    private function normalizarData(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            try {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }

        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }

        $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y'];
        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $text)->format('Y-m-d');
            } catch (\Throwable) {
            }
        }

        try {
            return Carbon::parse($text)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function garantirParticipanteDoAgendamento(Agendamento $agendamento, AgendamentoParticipante $participante): void
    {
        abort_unless($participante->agendamento_id === $agendamento->id, 404);
    }
}
