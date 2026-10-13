<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! auth()->check()) {
            return redirect()->route('login')->with('error', 'Please log in to continue.');
        }

        $user = auth()->user();

        if ($user->account_status !== 'active') {
            auth()->logout();

            return redirect()->route('login')->with('error', 'Your account has been suspended. Please contact support.');
        }

        if (! in_array($user->role, $roles, true)) {
            abort(403, 'Unauthorized access: your role ('.$user->role.') does not permit viewing this page.');
        }

        return $next($request);
    }
}
