<?php

return [
    'enabled' => (bool) env('OCR_SERVICE_ENABLED', true),
    'service_url' => env('OCR_SERVICE_URL', 'http://127.0.0.1:8001'),
    'health_path' => env('OCR_HEALTH_PATH', '/health'),
    'health_timeout' => (int) env('OCR_HEALTH_TIMEOUT', 3),
    'health_connect_timeout' => (int) env('OCR_HEALTH_CONNECT_TIMEOUT', 2),
    'health_cache_seconds' => (int) env('OCR_HEALTH_CACHE_SECONDS', 15),
    'status_poll_ms' => (int) env('OCR_STATUS_POLL_MS', 10000),
    'status_request_timeout_ms' => (int) env('OCR_STATUS_REQUEST_TIMEOUT_MS', 5000),
    'storage_disk' => env('OCR_STORAGE_DISK', env('FILESYSTEM_DISK', 'local')),
    'log_channel' => env('OCR_LOG_CHANNEL', 'ocr_pipeline'),
    'log_level' => env('OCR_LOG_LEVEL', 'debug'),
    'http_timeout' => (int) env('OCR_HTTP_TIMEOUT', 360),
    'connect_timeout' => (int) env('OCR_CONNECT_TIMEOUT', 10),
    'max_retries' => (int) env('OCR_HTTP_RETRIES', 2),
    'retry_sleep_ms' => (int) env('OCR_HTTP_RETRY_SLEEP_MS', 500),
    'default_language' => env('OCR_LANGUAGE', 'por'),
    'enable_paddle' => (bool) env('OCR_ENABLE_PADDLE', false),
];
