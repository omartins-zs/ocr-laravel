<?php

use App\Http\Controllers\Api\DocumentApiController;
use App\Http\Controllers\Api\ProcessingLogApiController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'status' => 'success',
        'status_code' => 200,
        'message' => 'API healthy',
        'data' => [
            'service' => config('app.name', 'ocr-laravel'),
            'timestamp' => now()->toIso8601String(),
        ],
        'errors' => [],
    ], 200);
})->name('api.health');

Route::prefix('v1')
    ->middleware(['auth', 'throttle:api'])
    ->group(function (): void {
        Route::get('/documents', [DocumentApiController::class, 'index'])->name('api.documents.index');
        Route::post('/documents', [DocumentApiController::class, 'store'])->name('api.documents.store');
        Route::get('/documents/{document}', [DocumentApiController::class, 'show'])->name('api.documents.show');
        Route::get('/documents/{document}/status', [DocumentApiController::class, 'status'])->name('api.documents.status');
        Route::get('/documents/{document}/text', [DocumentApiController::class, 'text'])->name('api.documents.text');
        Route::get('/documents/{document}/fields', [DocumentApiController::class, 'fields'])->name('api.documents.fields');
        Route::post('/documents/{document}/reprocess', [DocumentApiController::class, 'reprocess'])->name('api.documents.reprocess');

        Route::get('/processing-logs', [ProcessingLogApiController::class, 'index'])->name('api.processing-logs.index');
    });
