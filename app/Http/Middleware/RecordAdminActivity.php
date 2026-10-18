<?php

namespace App\Http\Middleware;

use App\Models\AdminActivityLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class RecordAdminActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $admin = auth('admin')->user();
        $method = strtoupper($request->method());

        $response = $next($request);

        if ($admin && in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true) && $response->getStatusCode() < 400) {
            try {
                AdminActivityLog::query()->create([
                    'admin_user_id' => $admin->getAuthIdentifier(),
                    'action' => (string) ($request->route()?->getName() ?: $request->path()),
                    'method' => $method,
                    'path' => mb_substr('/'.$request->path(), 0, 500),
                    'route_name' => $request->route()?->getName(),
                    'status_code' => $response->getStatusCode(),
                    'ip_address' => $request->ip(),
                    'user_agent' => mb_substr((string) $request->userAgent(), 0, 2000),
                    'created_at' => now(),
                ]);
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return $response;
    }
}
