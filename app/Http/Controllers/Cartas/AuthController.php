<?php

namespace App\Http\Controllers\Cartas;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    private const PENDING_REGISTRATION_SESSION_KEY = 'cartas.pending_registration';

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
            throw ValidationException::withMessages([
                'email' => 'E-mail ou senha inválidos.',
            ]);
        }

        if (! Auth::attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
            'sistema_origem' => User::SISTEMA_CARTAS,
        ], $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'E-mail ou senha inválidos.',
            ]);
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

    public function storeRegister(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'lowercase', 'email', 'max:255',
                Rule::unique('users', 'email')->where('sistema_origem', User::SISTEMA_CARTAS),
            ],
            'password' => ['required', Rules\Password::defaults()],
        ], [
            'name.required' => 'Informe seu nome.',
            'name.max' => 'O nome deve ter no máximo 255 caracteres.',
            'email.required' => 'Informe seu e-mail.',
            'email.email' => 'Informe um e-mail válido.',
            'email.unique' => 'Este e-mail já está cadastrado no Cartas para Esperançar.',
            'password.required' => 'Informe sua senha.',
            'password.min' => 'A senha deve ter pelo menos :min caracteres.',
        ]);

        $request->session()->put(self::PENDING_REGISTRATION_SESSION_KEY, [
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        return redirect()->route('cartas.terms');
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
        $user = $request->user();

        if (! $user && $request->session()->has(self::PENDING_REGISTRATION_SESSION_KEY)) {
            $pendingRegistration = $request->session()->get(self::PENDING_REGISTRATION_SESSION_KEY);

            validator($pendingRegistration, [
                'name' => ['required', 'string', 'max:255'],
                'email' => [
                    'required', 'string', 'lowercase', 'email', 'max:255',
                    Rule::unique('users', 'email')->where('sistema_origem', User::SISTEMA_CARTAS),
                ],
                'password' => ['required', 'string'],
            ], [
                'name.required' => 'Informe seu nome.',
                'email.required' => 'Informe seu e-mail.',
                'email.email' => 'Informe um e-mail válido.',
                'email.unique' => 'Este e-mail já está cadastrado no Cartas para Esperançar.',
                'password.required' => 'Informe sua senha.',
            ])->validate();

            $user = User::create([
                'name' => $pendingRegistration['name'],
                'email' => $pendingRegistration['email'],
                'password' => $pendingRegistration['password'],
                'sistema_origem' => User::SISTEMA_CARTAS,
                'cartas_terms_accepted_at' => now(),
            ]);

            if ($role = Role::where('name', 'cartas_voluntario')->where('guard_name', 'web')->first()) {
                $user->assignRole($role);
            }

            $request->session()->forget(self::PENDING_REGISTRATION_SESSION_KEY);

            Auth::login($user);
            $request->session()->regenerate();

            $user->sendEmailVerificationNotification();

            return redirect()->route('cartas.verification.notice');
        }

        if (! $user) {
            return redirect()->route('cartas.register');
        }

        if (! $user->isCartasUser()) {
            abort(403);
        }

        $user->forceFill([
            'cartas_terms_accepted_at' => now(),
        ])->save();

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

        $existingUser = User::where('email', $request->email)
            ->where('sistema_origem', User::SISTEMA_CARTAS)
            ->first();

        if (! $existingUser?->isCartasUser()) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Não foi possível redefinir a senha para este e-mail.']);
        }

        if (! Password::broker()->tokenExists($existingUser, $request->token)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Este link de redefinição de senha é inválido ou expirou.']);
        }

        $existingUser->forceFill([
            'password' => Hash::make($request->password),
            'remember_token' => Str::random(60),
        ])->save();

        Password::broker()->deleteToken($existingUser);

        return redirect()->route('cartas.login')->with('status', 'Sua senha foi redefinida com sucesso.');
    }
}
