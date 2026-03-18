<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class ActivityLogger
{
    /**
     * Log employee activity into activity_logs table.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = Auth::user();

        // Only log authenticated employees and if table exists.
        if ($user && $user->role === 'employee' && Schema::hasTable('activity_logs')) {
            try {
                DB::table('activity_logs')->insert([
                    'performed_at' => now(),
                    'user'         => $user->name,
                    'module'       => $request->route()?->getName() ?? $request->path(),
                    'action'       => $request->method(),
                    'details'      => json_encode([
                        'path'   => $request->path(),
                        'ip'     => $request->ip(),
                        'params' => $request->route()?->parameters() ?? [],
                    ]),
                    'status'       => ($response->getStatusCode() >= 200 && $response->getStatusCode() < 400) ? 'success' : 'failed',
                ]);
            } catch (\Throwable $e) {
                // Swallow logging errors to avoid breaking the request.
            }
        }

        return $response;
    }
}
