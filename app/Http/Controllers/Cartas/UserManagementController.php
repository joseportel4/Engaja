<?php

namespace App\Http\Controllers\Cartas;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    private const ROLES = [
        'cartas_admin' => 'Administrador',
        'cartas_gestao' => 'Gestor',
        'cartas_voluntario' => 'Voluntário',
    ];

    public function index(Request $request): View
    {
        $this->authorizeAdmin($request);

        $search = trim((string) $request->query('q', ''));

        $users = User::query()
            ->where('sistema_origem', User::SISTEMA_CARTAS)
            ->with('roles')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('cartas.operacional.usuarios.index', [
            'users' => $users,
            'roles' => self::ROLES,
            'search' => $search,
        ]);
    }

    public function edit(Request $request, User $managedUser): View
    {
        $this->authorizeAdmin($request);
        $this->ensureCartasUser($managedUser);

        return view('cartas.operacional.usuarios.edit', [
            'managedUser' => $managedUser->load('roles'),
            'roles' => self::ROLES,
        ]);
    }

    public function update(Request $request, User $managedUser): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $this->ensureCartasUser($managedUser);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')
                    ->where('sistema_origem', User::SISTEMA_CARTAS)
                    ->ignore($managedUser->id),
            ],
            'role' => ['required', Rule::in(array_keys(self::ROLES))],
        ], [
            'name.required' => 'Informe o nome do usuário.',
            'email.required' => 'Informe o e-mail.',
            'email.email' => 'Informe um e-mail válido.',
            'email.unique' => 'Este e-mail já está em uso no Cartas.',
            'role.required' => 'Selecione o perfil de acesso.',
            'role.in' => 'Selecione um perfil válido.',
        ]);

        if ($request->user()->is($managedUser) && $data['role'] !== 'cartas_admin') {
            return back()
                ->withInput()
                ->withErrors(['role' => 'Você não pode remover o próprio acesso de administrador.']);
        }

        $managedUser->update([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        $managedUser->syncRoles([$data['role']]);

        return redirect()
            ->route('cartas.usuarios.index')
            ->with('status', 'Usuário atualizado.');
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->hasRole('cartas_admin'), 403);
    }

    private function ensureCartasUser(User $user): void
    {
        abort_unless($user->sistema_origem === User::SISTEMA_CARTAS, 404);
    }
}
