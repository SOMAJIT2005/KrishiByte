<?php

namespace App\Http\Controllers;

use App\Models\District;
use App\Models\User;
use App\Services\AuditLoggerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return $this->redirectBasedOnRole(Auth::user());
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            $user = Auth::user();

            if ($user->account_status !== 'active') {
                Auth::logout();
                throw ValidationException::withMessages([
                    'email' => 'Your account has been suspended.',
                ]);
            }

            AuditLoggerService::log(
                action: 'USER_LOGIN',
                targetType: 'User',
                targetId: $user->id,
                notes: "User logged in with role {$user->role}"
            );

            return $this->redirectBasedOnRole($user);
        }

        throw ValidationException::withMessages([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }

    /**
     * 1-Click Demo Login for fast local testing and evaluator review
     */
    public function demoLogin(string $role)
    {
        abort_unless(app()->environment('local'), 404);
        $emailMap = [
            'admin' => 'admin@krishibyte.local',
            'authority' => 'authority@krishibyte.local',
            'farmer' => 'farmer.rahim@krishibyte.local',
            'collector' => 'collector.karim@krishibyte.local',
            'wholesale' => 'wholesale.hasan@krishibyte.local',
            'retail' => 'retail.rafiq@krishibyte.local',
            'consumer' => 'consumer.tariq@krishibyte.local',
        ];

        if (! isset($emailMap[$role])) {
            return redirect()->route('login')->with('error', 'Invalid demo role.');
        }

        $user = User::where('email', $emailMap[$role])->first();
        if (! $user) {
            return redirect()->route('login')->with('error', 'Demo user not found. Please run seeders.');
        }

        abort_unless($user->account_status === 'active', 403);
        Auth::login($user);
        request()->session()->regenerate();

        return $this->redirectBasedOnRole($user)
            ->with('info', "Logged in as {$user->name} ({$user->role}) via local demo shortcut.");
    }

    public function showRegister()
    {
        if (Auth::check()) {
            return $this->redirectBasedOnRole(Auth::user());
        }

        $districts = District::orderBy('name')->get();

        return view('auth.register', compact('districts'));
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['required', 'in:consumer,farmer,collector,wholesale,retail'], // Strictly prohibit authority/admin
            'district_id' => ['nullable', 'exists:districts,id'],
            'nid_or_trade_license' => ['nullable', 'string', 'max:100'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['role'],
            'account_status' => 'active',
            'district_id' => $validated['district_id'] ?? null,
            'nid_or_trade_license' => $validated['nid_or_trade_license'] ?? null,
            'password' => Hash::make($validated['password']),
        ]);

        abort_unless($user->account_status === 'active', 403);
        Auth::login($user);

        AuditLoggerService::log(
            action: 'USER_REGISTERED',
            targetType: 'User',
            targetId: $user->id,
            notes: "New account registered as role: {$user->role}"
        );

        return $this->redirectBasedOnRole($user)
            ->with('success', 'Registration successful! Welcome to KrishiByte.');
    }

    public function logout(Request $request)
    {
        if (Auth::check()) {
            AuditLoggerService::log(
                action: 'USER_LOGOUT',
                targetType: 'User',
                targetId: Auth::id()
            );
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('info', 'You have been logged out.');
    }

    protected function redirectBasedOnRole(User $user)
    {
        return match ($user->role) {
            'admin' => redirect()->route('admin.dashboard'),
            'authority' => redirect()->route('authority.dashboard'),
            'farmer' => redirect()->route('farmer.dashboard'),
            'collector', 'wholesale', 'retail' => redirect()->route('vendor.dashboard'),
            default => redirect()->route('public.prices'),
        };
    }
}
