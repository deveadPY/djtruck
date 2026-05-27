<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * HealthController — endpoint público para monitoreo externo (Uptime Robot, Pingdom, etc.).
 *
 * GET /api/v1/health        → básico (siempre 200 si la app responde)
 * GET /api/v1/health/deep   → verifica DB, cache, storage, billing, scheduler
 */
final class HealthController extends Controller
{
    public function basic(): JsonResponse
    {
        return response()->json([
            'status'    => 'ok',
            'timestamp' => now()->toIso8601String(),
            'app'       => config('app.name'),
            'env'       => config('app.env'),
            'version'   => config('app.version', 'unknown'),
        ]);
    }

    public function deep(): JsonResponse
    {
        $checks = [
            'database'        => $this->checkDatabase(),
            'cache'           => $this->checkCache(),
            'storage'         => $this->checkStorage(),
            'billing_driver'  => $this->checkBilling(),
            'scheduler_recent'=> $this->checkScheduler(),
            'queue_worker'    => $this->checkQueueWorker(),
        ];

        $allHealthy = collect($checks)->every(fn($c) => ($c['status'] ?? 'fail') === 'ok');

        return response()->json([
            'status'    => $allHealthy ? 'ok' : 'degraded',
            'timestamp' => now()->toIso8601String(),
            'checks'    => $checks,
        ], $allHealthy ? 200 : 503);
    }

    private function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            $result = DB::select('SELECT 1 as ok');
            $duration = round((microtime(true) - $start) * 1000, 2);

            return [
                'status'      => $result[0]->ok === 1 ? 'ok' : 'fail',
                'driver'      => config('database.default'),
                'duration_ms' => $duration,
            ];
        } catch (Throwable $e) {
            return ['status' => 'fail', 'error' => substr($e->getMessage(), 0, 200)];
        }
    }

    private function checkCache(): array
    {
        try {
            $key = '_healthcheck_' . uniqid();
            Cache::put($key, 'ok', 5);
            $value = Cache::get($key);
            Cache::forget($key);

            return [
                'status' => $value === 'ok' ? 'ok' : 'fail',
                'driver' => config('cache.default'),
            ];
        } catch (Throwable $e) {
            return ['status' => 'fail', 'error' => substr($e->getMessage(), 0, 200)];
        }
    }

    private function checkStorage(): array
    {
        try {
            $disk = Storage::disk('local');
            $key = '_healthcheck_' . uniqid() . '.txt';
            $disk->put($key, 'ok');
            $value = $disk->get($key);
            $disk->delete($key);

            return ['status' => $value === 'ok' ? 'ok' : 'fail'];
        } catch (Throwable $e) {
            return ['status' => 'fail', 'error' => substr($e->getMessage(), 0, 200)];
        }
    }

    private function checkBilling(): array
    {
        try {
            $provider = app(\App\Domain\Billing\Contracts\BillingProviderInterface::class);
            return [
                'status'   => 'ok',
                'provider' => $provider->providerName(),
                'driver'   => config('billing.default'),
            ];
        } catch (Throwable $e) {
            return ['status' => 'fail', 'error' => substr($e->getMessage(), 0, 200)];
        }
    }

    private function checkScheduler(): array
    {
        try {
            $lastOverdueCheck = DB::table('audit_logs')
                ->where('action', 'like', '%OVERDUE%')
                ->orWhere('action', 'like', '%SCHEDULE%')
                ->orderByDesc('created_at')
                ->value('created_at');

            if (!$lastOverdueCheck) {
                return [
                    'status'  => 'unknown',
                    'message' => 'No hay registros recientes del scheduler — verificar cron',
                ];
            }

            $hoursAgo = now()->diffInHours($lastOverdueCheck);

            return [
                'status'         => $hoursAgo < 26 ? 'ok' : 'stale',
                'last_run_hours' => $hoursAgo,
            ];
        } catch (Throwable $e) {
            return ['status' => 'unknown', 'error' => substr($e->getMessage(), 0, 200)];
        }
    }

    private function checkQueueWorker(): array
    {
        try {
            $failedCount = DB::table('failed_jobs')->count();
            return [
                'status'       => $failedCount === 0 ? 'ok' : 'has_failures',
                'failed_jobs'  => $failedCount,
                'queue_driver' => config('queue.default'),
            ];
        } catch (Throwable $e) {
            return ['status' => 'unknown', 'error' => substr($e->getMessage(), 0, 200)];
        }
    }
}
