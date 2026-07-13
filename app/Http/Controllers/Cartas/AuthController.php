<?php

namespace App\Http\Controllers\Cartas;

use App\Http\Controllers\Controller;
use App\Models\Estado;
use App\Models\Municipio;
use App\Models\Participante;
use App\Models\Regiao;
use App\Models\User;
use App\Services\IbgeLocalidadesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    private const PENDING_REGISTRATION_SESSION_KEY = 'cartas.pending_registration';

    public function __construct(private readonly IbgeLocalidadesService $ibgeLocalidades) {}

    public function login(): View
    {
        return view('cartas.auth.login');
    }

    public function authenticate(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Informe seu e-mail.',
            'email.email' => 'Informe um e-mail válido.',
            'password.required' => 'Informe sua senha.',
        ]);

        $user = User::where('email', $credentials['email'])
            ->where('sistema_origem', User::SISTEMA_CARTAS)
            ->first();

        if (! $user || ! $user->isCartasUser()) {
            throw ValidationException::withMessages(['email' => 'E-mail ou senha inválidos.']);
        }

        if (! Auth::attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
            'sistema_origem' => User::SISTEMA_CARTAS,
        ], $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => 'E-mail ou senha inválidos.']);
        }

        $request->session()->regenerate();

        if (! $request->user()->cartas_terms_accepted_at) {
            return redirect()->route('cartas.terms');
        }

        if (! $request->user()->hasVerifiedEmail()) {
            return redirect()->route('cartas.verification.notice');
        }

        return redirect()->intended(route('cartas.dashboard'));
    }

    public function register(): View
    {
        return view('cartas.auth.register');
    }

    public function estados(): JsonResponse
    {
        return response()->json($this->ibgeLocalidades->estados());
    }

    public function municipios(int $estadoIbgeId): JsonResponse
    {
        return response()->json($this->ibgeLocalidades->municipiosDoEstado($estadoIbgeId));
    }

    public function storeRegister(Request $request): RedirectResponse
    {
        $data = $this->validatedRegistrationData($request);

        try {
            $localidade = $this->ibgeLocalidades->localizar($data['estado_ibge_id'], $data['municipio_ibge_id']);
        } catch (\RuntimeException $exception) {
            throw ValidationException::withMessages(['municipio_ibge_id' => $exception->getMessage()]);
        }


        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'cpf' => $data['cpf'],
            'telefone' => $data['telefone'] ?? null,
            'estado_nome' => $localidade['estado']['nome'],
            'estado_sigla' => $localidade['estado']['sigla'],
            'municipio_nome' => $localidade['municipio']['nome'],
            'sistema_origem' => User::SISTEMA_CARTAS,
            'cartas_terms_accepted_at' => now(),
        ]);

        if ($role = Role::where('name', 'cartas_voluntario')->where('guard_name', 'web')->first()) {
            $user->assignRole($role);
        }

        Auth::login($user);
        $request->session()->regenerate();

        $user->sendEmailVerificationNotification();

        return redirect()->route('cartas.verification.notice');
    }

    public function terms(Request $request): RedirectResponse|View
    {
        if ($request->user()) {
            if (! $request->user()->isCartasUser()) {
                abort(403);
            }

            return view('cartas.auth.terms');
        }

        if (! $request->session()->has(self::PENDING_REGISTRATION_SESSION_KEY)) {
            return redirect()->route('cartas.register');
        }

        return view('cartas.auth.terms');
    }

    public function acceptTerms(Request $request): RedirectResponse
    {
        if (! $request->user() && $request->session()->has(self::PENDING_REGISTRATION_SESSION_KEY)) {
            return $this->completePendingRegistration($request);
        }

        $user = $request->user();
        if (! $user) {
            return redirect()->route('cartas.register');
        }

        if (! $user->isCartasUser()) {
            abort(403);
        }

        $user->forceFill(['cartas_terms_accepted_at' => now()])->save();

        if (! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }

        return redirect()->route('cartas.verification.notice');
    }

    public function verificationNotice(Request $request): RedirectResponse|View
    {
        return $request->user()->hasVerifiedEmail()
            ? redirect()->route('cartas.dashboard')
            : view('cartas.auth.verify-email');
    }

    public function forgotPassword(): View
    {
        return view('cartas.auth.forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'Informe seu e-mail.',
            'email.email' => 'Informe um e-mail válido.',
        ]);

        $user = User::where('email', $request->email)
            ->where('sistema_origem', User::SISTEMA_CARTAS)
            ->first();

        if (! $user || ! $user->isCartasUser()) {
            return back()->with('status', 'Se o e-mail estiver cadastrado, enviaremos as instruções de recuperação.');
        }

        $token = Password::broker()->createToken($user);
        $user->sendPasswordResetNotification($token);

        return back()->with('status', 'Enviamos o link de redefinição de senha para seu e-mail.');
    }

    public function resetPassword(Request $request, string $token): View
    {
        return view('cartas.auth.reset-password', [
            'request' => $request,
            'token' => $token,
        ]);
    }

    public function storeNewPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'token.required' => 'O token de redefinição é obrigatório.',
            'email.required' => 'Informe seu e-mail.',
            'email.email' => 'Informe um e-mail válido.',
            'password.required' => 'Informe a nova senha.',
            'password.confirmed' => 'A confirmação da senha não confere.',
            'password.min' => 'A senha deve ter pelo menos :min caracteres.',
        ]);

        $user = User::where('email', $request->email)
            ->where('sistema_origem', User::SISTEMA_CARTAS)
            ->first();

        if (! $user?->isCartasUser()) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'Não foi possível redefinir a senha para este e-mail.']);
        }

        if (! Password::broker()->tokenExists($user, $request->token)) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'Este link de redefinição de senha é inválido ou expirou.']);
        }

        $user->forceFill([
            'password' => Hash::make($request->password),
            'remember_token' => Str::random(60),
        ])->save();
        Password::broker()->deleteToken($user);

        return redirect()->route('cartas.login')->with('status', 'Sua senha foi redefinida com sucesso.');
    }

    private function validatedRegistrationData(Request $request): array
    {
        $data = $this->prepareRegistrationData($request);
        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->where('sistema_origem', User::SISTEMA_CARTAS)],
            'password' => ['required', Rules\Password::defaults()],
            'cpf' => ['required', 'digits:11'],
            'telefone' => ['nullable', 'regex:/^\d{10,11}$/'],
            'estado_ibge_id' => ['required', 'integer', 'min:1'],
            'municipio_ibge_id' => ['required', 'integer', 'min:1'],
            'termos_aceitos' => ['accepted'],
        ], [
            'cpf.required' => 'Informe seu CPF.',
            'cpf.digits' => 'CPF deve conter 11 dígitos.',
            'telefone.regex' => 'Telefone deve ter DDD e 10 ou 11 dígitos.',
            'estado_ibge_id.required' => 'Selecione seu estado.',
            'municipio_ibge_id.required' => 'Selecione seu município.',
            'termos_aceitos.accepted' => 'Você precisa aceitar os termos de uso para continuar.',
        ]);

        $validator->after(function ($validator) use ($data) {
            if (! $this->isValidCpf($data['cpf'] ?? '')) {
                $validator->errors()->add('cpf', 'CPF inválido.');
            } elseif ($this->cpfDuplicado($data['cpf'])) {
                $validator->errors()->add('cpf', 'Este CPF já possui cadastro no sistema.');
            }
        });

        return $validator->validate();
    }

    private function completePendingRegistration(Request $request): RedirectResponse
    {
        $pendingRegistration = $request->session()->get(self::PENDING_REGISTRATION_SESSION_KEY);
        $data = Validator::make($pendingRegistration, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->where('sistema_origem', User::SISTEMA_CARTAS)],
            'password' => ['required', 'string'],
            'cpf' => ['required', 'digits:11'],
            'telefone' => ['nullable', 'regex:/^\d{10,11}$/'],
            'estado_nome' => ['required', 'string', 'max:255'],
            'estado_sigla' => ['required', 'string', 'size:2'],
            'municipio_nome' => ['required', 'string', 'max:255'],
        ])->after(function ($validator) use ($pendingRegistration) {
            $cpf = $pendingRegistration['cpf'] ?? '';
            if (! $this->isValidCpf($cpf)) {
                $validator->errors()->add('cpf', 'CPF inválido.');
            } elseif ($this->cpfDuplicado($cpf)) {
                $validator->errors()->add('cpf', 'Este CPF já possui cadastro no sistema.');
            }
        })->validate();

        $user = DB::transaction(function () use ($data) {
            $municipio = $this->createOrFindMunicipio($data);
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'sistema_origem' => User::SISTEMA_CARTAS,
                'cartas_terms_accepted_at' => now(),
            ]);

            $user->participante()->updateOrCreate(['user_id' => $user->id], [
                'cpf' => $data['cpf'],
                'telefone' => $data['telefone'] ?? null,
                'municipio_id' => $municipio->id,
            ]);

            if ($role = Role::where('name', 'cartas_voluntario')->where('guard_name', 'web')->first()) {
                $user->assignRole($role);
            }

            return $user;
        });

        $request->session()->forget(self::PENDING_REGISTRATION_SESSION_KEY);
        Auth::login($user);
        $request->session()->regenerate();
        $user->sendEmailVerificationNotification();

        return redirect()->route('cartas.verification.notice');
    }

    private function prepareRegistrationData(Request $request): array
    {
        $toNull = fn ($value) => $value === '' || $value === null ? null : $value;

        return array_merge($request->all(), [
            'name' => trim((string) $request->input('name')),
            'email' => trim((string) $request->input('email')),
            'cpf' => $toNull(preg_replace('/\D+/', '', (string) $request->input('cpf'))),
            'telefone' => $toNull(preg_replace('/\D+/', '', (string) $request->input('telefone'))),
            'estado_ibge_id' => $toNull($request->input('estado_ibge_id')),
            'municipio_ibge_id' => $toNull($request->input('municipio_ibge_id')),
        ]);
    }

    private function createOrFindMunicipio(array $data): Municipio
    {
        $estado = Estado::withTrashed()->whereRaw('UPPER(sigla) = ?', [mb_strtoupper($data['estado_sigla'])])->first();
        if ($estado?->trashed()) {
            $estado->restore();
        }

        if (! $estado) {
            if (! Regiao::query()->whereKey(4)->exists()) {
                throw ValidationException::withMessages([
                    'estado_ibge_id' => 'Cadastre a região "Outras" (ID 4) antes de incluir um novo estado.',
                ]);
            }

            $estado = Estado::create([
                'nome' => $data['estado_nome'],
                'sigla' => mb_strtoupper($data['estado_sigla']),
                'regiao_id' => 4,
            ]);
        }

        $municipio = Municipio::withTrashed()
            ->where('estado_id', $estado->id)
            ->whereRaw('LOWER(nome) = ?', [mb_strtolower($data['municipio_nome'])])
            ->first();
        if ($municipio?->trashed()) {
            $municipio->restore();
        }

        return $municipio ?? Municipio::create([
            'estado_id' => $estado->id,
            'nome' => $data['municipio_nome'],
        ]);
    }

    private function cpfDuplicado(string $cpf): bool
    {
        return Participante::query()->whereNotNull('cpf')
            ->whereRaw("regexp_replace(cpf, '[^0-9]', '', 'g') = ?", [$cpf])
            ->exists();
    }

    private function isValidCpf(string $cpf): bool
    {
        $cpf = preg_replace('/\D+/', '', $cpf);
        if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        foreach ([9, 10] as $position) {
            $sum = 0;
            for ($index = 0, $weight = $position + 1; $index < $position; $index++, $weight--) {
                $sum += (int) $cpf[$index] * $weight;
            }
            $digit = ($sum % 11) < 2 ? 0 : 11 - ($sum % 11);
            if ((int) $cpf[$position] !== $digit) {
                return false;
            }
        }

        return true;
    }
}
