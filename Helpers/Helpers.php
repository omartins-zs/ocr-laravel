<?php

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

if (! function_exists('isLocal')) {
    /**
     * Verifica se o ambiente e local.
     */
    function isLocal(): bool
    {
        return config('app.env') === 'local';
    }
}

if (! function_exists('processErrorBehaviour')) {
    /**
     * Trata erros/excecoes com log local e envio ao Sentry.
     */
    function processErrorBehaviour(Throwable $e): void
    {
        if (isLocal()) {
            Log::error('Erro capturado (local)', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            dd($e->getMessage(), $e->getFile(), $e->getLine());
        }

        if (app()->bound('sentry')) {
            app('sentry')->captureException($e);
        }

        Log::error('Erro capturado (producao)', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);
    }
}

if (! function_exists('processErrorBehaviourSimples')) {
    /**
     * Trata erros/excecoes de forma simples.
     */
    function processErrorBehaviourSimples(Throwable $e): void
    {
        if (isLocal()) {
            dd($e->getMessage(), $e->getFile(), $e->getLine());
        }

        if (app()->bound('sentry')) {
            app('sentry')->captureException($e);
        }
    }
}

if (! function_exists('defineStorageDisk')) {
    /**
     * Define o disco de storage conforme o ambiente.
     */
    function defineStorageDisk(string $disk = 's3'): string
    {
        return isLocal() ? 'local' : $disk;
    }
}

if (! function_exists('getQueries')) {
    /**
     * Retorna SQL do Query Builder (versao simples).
     */
    function getQueries(Builder $builder): string
    {
        $sql = str_replace('?', "'?'", $builder->toSql());

        foreach ($builder->getBindings() as $value) {
            $sql = preg_replace('/\?/', (string) $value, $sql, 1);
        }

        return $sql;
    }
}

if (! function_exists('getQueriesV2')) {
    /**
     * Retorna SQL do Query Builder (versao aprimorada).
     */
    function getQueriesV2(Builder $builder): string
    {
        $sql = $builder->toSql();

        foreach ($builder->getBindings() as $value) {
            if (is_string($value)) {
                $value = "'{$value}'";
            } elseif (is_null($value)) {
                $value = 'NULL';
            } elseif (is_bool($value)) {
                $value = $value ? '1' : '0';
            }

            $sql = preg_replace('/\?/', (string) $value, $sql, 1);
        }

        return $sql;
    }
}

if (! function_exists('formatBytes')) {
    /**
     * Converte bytes para unidade legivel (B, KB, MB, GB, TB).
     */
    function formatBytes(int $bytes): string
    {
        $bytes = max(0, $bytes);
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        if ($bytes === 0) {
            return '0 B';
        }

        $pow = (int) floor(log($bytes, 1024));
        $pow = min($pow, count($units) - 1);
        $value = $bytes / (1 << (10 * $pow));

        return round($value, 2).' '.$units[$pow];
    }
}
