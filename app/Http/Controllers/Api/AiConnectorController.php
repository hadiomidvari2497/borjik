<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiActionRequest;
use App\Models\AiAccessToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class AiConnectorController extends Controller
{
    public function overview(): JsonResponse
    {
        $count = static fn (string $table): int => Schema::hasTable($table) ? DB::table($table)->count() : 0;
        $latestHealth = Schema::hasTable('operations_health_checks')
            ? DB::table('operations_health_checks')->orderByDesc('id')->value('status')
            : null;
        $latestBackup = Schema::hasTable('backup_runs')
            ? DB::table('backup_runs')->orderByDesc('id')->value('status')
            : null;

        return response()->json([
            'data' => [
                'application' => config('app.name', 'Borjik'),
                'environment' => app()->environment(),
                'checked_at' => now()->toIso8601String(),
                'counts' => [
                    'buildings' => $count('buildings'),
                    'users' => $count('users'),
                    'errors_last_24h' => Schema::hasTable('system_error_logs')
                        ? DB::table('system_error_logs')->where('created_at', '>=', now()->subDay())->count()
                        : null,
                    'failed_cron_last_24h' => Schema::hasTable('cron_execution_logs')
                        ? DB::table('cron_execution_logs')->where('status', 'failed')->where('started_at', '>=', now()->subDay())->count()
                        : null,
                ],
                'operations' => [
                    'latest_health_check_status' => $latestHealth,
                    'latest_backup_status' => $latestBackup,
                ],
            ],
        ]);
    }

    public function incidents(Request $request): JsonResponse
    {
        $limit = min(max((int) $request->query('limit', 10), 1), 50);
        if (! Schema::hasTable('system_error_logs')) {
            return response()->json(['data' => [], 'meta' => ['count' => 0, 'available' => false]]);
        }

        // Deliberately omit messages, traces, file paths, IPs, and user identifiers.
        $items = DB::table('system_error_logs')
            ->orderByDesc('id')
            ->limit($limit)
            ->get(['id', 'exception_class', 'severity', 'category', 'status_code', 'route_name', 'created_at'])
            ->map(static fn ($row) => [
                'id' => (int) $row->id,
                'type' => class_basename((string) $row->exception_class),
                'severity' => (string) $row->severity,
                'category' => (string) $row->category,
                'http_status' => $row->status_code === null ? null : (int) $row->status_code,
                'route' => $row->route_name,
                'occurred_at' => $row->created_at,
            ]);

        return response()->json(['data' => $items, 'meta' => ['count' => $items->count(), 'available' => true]]);
    }

    public function requestAction(Request $request): JsonResponse
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['run_health_check', 'run_backup'])],
            'reason' => ['required', 'string', 'min:8', 'max:500'],
        ]);

        /** @var AiAccessToken $token */
        $token = $request->attributes->get('ai_access_token');
        $action = AiActionRequest::query()->create([
            'ai_access_token_id' => $token->id,
            'action' => $data['action'],
            'payload' => [],
            'reason' => trim($data['reason']),
            'status' => 'pending',
        ]);

        return response()->json([
            'data' => [
                'id' => $action->id,
                'action' => $action->action,
                'status' => $action->status,
                'message' => 'The request is queued and will not run until an administrator explicitly approves it in the Borjik admin panel.',
            ],
        ], 202);
    }
}
