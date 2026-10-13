<?php

namespace App\Http\Controllers;

use App\Models\District;
use App\Models\Marketplace;
use App\Models\User;
use App\Services\AuditLoggerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
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
            $request->session()->passwordConfirmed();
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

    public function showRegister()
    {
        if (Auth::check()) {
            return $this->redirectBasedOnRole(Auth::user());
        }

        $districts = District::orderBy('name')->get();

        $markets = Marketplace::where('is_active', true)->with('district')->orderBy('name')->get();

        return view('auth.register', compact('districts', 'markets'));
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['required', 'in:consumer,farmer,collector,wholesale,retail'], // Strictly prohibit authority/admin
            'district_id' => ['required_if:role,collector,wholesale,retail', 'nullable', 'exists:districts,id'],
            'marketplace_id' => ['exclude_unless:role,retail', 'required', Rule::exists('marketplaces', 'id')->where('district_id', $request->input('district_id'))->where('is_active', true)],
            'shop_name' => ['exclude_unless:role,retail', 'required', 'string', 'max:150'],
            'nid_or_trade_license' => ['required_if:role,collector,retail', 'nullable', 'string', 'max:100'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'nid_or_trade_license.required_if' => 'A trade license number is required for collectors and retail shops.',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['role'],
            'account_status' => 'active',
            'district_id' => $validated['district_id'] ?? null,
            'marketplace_id' => $validated['marketplace_id'] ?? null,
            'shop_name' => $validated['shop_name'] ?? null,
            'nid_or_trade_license' => $validated['nid_or_trade_license'] ?? null,
            'password' => Hash::make($validated['password']),
        ]);

        abort_unless($user->account_status === 'active', 403);
        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->passwordConfirmed();

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
            'farmer' => redirect()->route('farmers.show', $user->id),
            'collector', 'wholesale' => redirect()->route('vendor.dashboard'),
            'retail' => redirect()->route('retail.prices'),
            default => redirect()->route('public.prices'),
        };
    }
}
