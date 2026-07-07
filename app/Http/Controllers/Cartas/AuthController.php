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

class AuthController extends Controller
{
    public function login(): View
    {
        return view('cartas.auth.login');
    }

    public function authenticate(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'terms' => ['accepted'],
        ], [
            'terms.accepted' => 'Confirme que leu e concorda com os termos de uso.',
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
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'sistema_origem' => User::SISTEMA_CARTAS,
        ]);

        Auth::login($user);

        $request->session()->regenerate();

        return redirect()->route('cartas.terms');
    }

    public function terms(): View
    {
        return view('cartas.auth.terms');
    }

    public function acceptTerms(Request $request): RedirectResponse
    {
        $user = $request->user();

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
        ]);

        $user = User::where('email', $request->email)
            ->where('sistema_origem', User::SISTEMA_CARTAS)
            ->first();

        if (! $user || ! $user->isCartasUser()) {
            return back()->with('status', 'Se o e-mail estiver cadastrado, enviaremos as instruções de recuperação.');
        }

        $token = Password::broker()->createToken($user);
        $user->sendPasswordResetNotification($token);

        return back()->with('status', __(Password::RESET_LINK_SENT));
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
                ->withErrors(['email' => __(Password::INVALID_TOKEN)]);
        }

        $existingUser->forceFill([
            'password' => Hash::make($request->password),
            'remember_token' => Str::random(60),
        ])->save();

        Password::broker()->deleteToken($existingUser);

        return redirect()->route('cartas.login')->with('status', __(Password::PASSWORD_RESET));
    }

}
