<?php

namespace App\Providers;

use App\Models\Document;
use App\Policies\DocumentPolicy;
use App\Support\PipelineLogger;
use Illuminate\Support\Facades\Cache;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Document::class, DocumentPolicy::class);

        Paginator::useTailwind();
        $this->configureApiRateLimiter();
        $this->logServiceStartup();

        $this->configureLocalRuntimeFallbacks();
    }

    private function configureApiRateLimiter(): void
    {
        RateLimiter::for('api', function (Request $request): Limit {
            $key = $request->user() ? (string) $request->user()->id : (string) $request->ip();

            return Limit::perMinute(60)->by($key);
        });
    }

    private function configureLocalRuntimeFallbacks(): void
    {
        if (! isLocal() || ! (bool) config('app.runtime_fallbacks', true)) {
            return;
        }

        $queueDefault = (string) config('queue.default', 'database');
        $cacheDefault = (string) config('cache.default', 'file');
        $sessionDriver = (string) config('session.driver', 'database');

        $usesRedis = $queueDefault === 'redis'
            || $cacheDefault === 'redis'
            || $sessionDriver === 'redis';

        if (! $usesRedis || $this->isRedisAvailable()) {
            return;
        }

        if ($queueDefault === 'redis') {
            config([
                'queue.default' => config('app.queue_fallback_connection', 'database'),
            ]);
        }

        if ($cacheDefault === 'redis') {
            config([
                'cache.default' => config('app.cache_fallback_store', 'file'),
            ]);
        }

        if ($sessionDriver === 'redis') {
            config([
                'session.driver' => config('app.session_fallback_driver', 'database'),
            ]);
        }

        Log::warning('Redis indisponivel. Fallback local aplicado automaticamente.', [
            'queue.default' => config('queue.default'),
            'cache.default' => config('cache.default'),
            'session.driver' => config('session.driver'),
        ]);
    }

    private function isRedisAvailable(): bool
    {
        try {
            $connection = (string) config('queue.connections.redis.connection', 'default');
            $pong = Redis::connection($connection)->ping();

            if (is_bool($pong)) {
                return $pong;
            }

            if (is_string($pong)) {
                return str_contains(strtolower($pong), 'pong');
            }

            return (bool) $pong;
        } catch (Throwable) {
            return false;
        }
    }

    private function logServiceStartup(): void
    {
        if (! isLocal()) {
            return;
        }

        try {
            $logged = Cache::add('service.startup.logged', true, now()->addMinutes(10));
            if (! $logged) {
                return;
            }
        } catch (Throwable) {
            // Se o cache nao estiver disponivel, evita falhar o boot.
        }

        PipelineLogger::info('service.started', [
            'service' => 'ocr-laravel',
            'environment' => config('app.env'),
            'app_url' => config('app.url'),
            'ocr_service_url' => config('ocr.service_url'),
            'queue_connection' => config('queue.default'),
            'cache_store' => config('cache.default'),
            'message' => 'Service rodando e escutando requisicoes HTTP.',
        ]);
    }
}
