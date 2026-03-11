<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Restrict access by users.role (case-insensitive).
     * Usage: ->middleware('role:admin') or ->middleware('role:admin,hr,manager')
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles  Allowed roles (e.g. admin, employee, supervisor)
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        $userRole = strtolower(trim((string) (Auth::user()->role ?? '')));
        $allowed   = array_map('strtolower', array_map('trim', $roles));

        if (! in_array($userRole, $allowed, true)) {
            abort(403, 'Access denied. This area is for: ' . implode(', ', $roles) . '.');
        }

        return $next($request);
    }
}
