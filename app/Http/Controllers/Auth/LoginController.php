<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class LoginController extends Controller
{
    /**
     * Show the application login form or redirect authenticated user.
     */
    public function showLoginForm(Request $request): InertiaResponse|RedirectResponse
    {
        if (Auth::check()) {
            return redirect($this->roleHome(Auth::user()));
        }

        return Inertia::render('Auth/Login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function login(LoginRequest $request): RedirectResponse|Response
    {
        $throttleKey = Str::transliterate(Str::lower($request->input('email')) . '|' . $request->ip());

        // Check rate limiter lockout (5 attempts per 60 seconds)
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return response(
                "Terlalu banyak percobaan login. Silakan coba lagi dalam {$seconds} detik.",
                429,
                ['Retry-After' => (string) $seconds]
            );
        }

        $email = $request->input('email');
        $password = $request->input('password');

        $user = User::where('email', $email)->first();

        // 1. Validate existence and password match
        if (!$user || !Hash::check($password, $user->password)) {
            RateLimiter::hit($throttleKey, 60);

            throw ValidationException::withMessages([
                'email' => 'Kredensial yang diberikan tidak cocok dengan data kami.',
            ]);
        }

        // 2. Intercept deactivated accounts
        if (!$user->isActive()) {
            throw ValidationException::withMessages([
                'email' => 'Akun Anda dinonaktifkan. Silakan hubungi pemilik.',
            ]);
        }

        // 3. Attempt authentication with remember token
        if (!Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey, 60);

            throw ValidationException::withMessages([
                'email' => 'Kredensial yang diberikan tidak cocok dengan data kami.',
            ]);
        }

        // 4. Success: clear throttle counters and regenerate session ID
        RateLimiter::clear($throttleKey);
        $altKey = Str::transliterate($request->ip() . '|' . Str::lower($email));
        RateLimiter::clear($altKey);

        $request->session()->regenerate();

        return redirect()->intended($this->roleHome($user));
    }

    /**
     * Resolve default home route based on user role.
     */
    public function roleHome(User $user): string
    {
        return match ($user->role) {
            'owner' => '/dashboard',
            'cashier' => '/pos',
            'operator' => '/production/station',
            default => '/dashboard',
        };
    }
}
