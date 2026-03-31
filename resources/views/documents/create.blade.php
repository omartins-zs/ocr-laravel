@extends('layouts.app')

@section('page-title', 'Novo upload')

@section('content')
    <section class="page-header">
        <div class="page-title-wrap">
            <h2 class="page-title">
                <x-heroicon-o-cloud-arrow-up class="h-6 w-6 text-brand-600 dark:text-brand-300" />
                Enviar documento para OCR
            </h2>
            <p class="page-subtitle">Fluxo direto: selecione arquivos, envie e acompanhe o processamento.</p>
        </div>
        <a href="{{ route('history') }}" class="btn-secondary w-full sm:w-auto">Voltar</a>
    </section>

    <form
        method="POST"
        action="{{ route('documents.store') }}"
        enctype="multipart/form-data"
        class="card-surface grid gap-5 p-4 sm:p-6"
        x-data="{ uploading: false }"
        @submit="uploading = true; window.dispatchEvent(new CustomEvent('ocr-status:pause'))"
        @invalid.capture="uploading = false; window.dispatchEvent(new CustomEvent('ocr-status:resume'))">
        @csrf

        <div class="rounded-2xl border border-dashed border-slate-300/80 bg-slate-50/70 p-3 dark:border-slate-700 dark:bg-slate-900/60">
            <label class="label-control">Arquivos</label>
            <input type="file" name="files[]" multiple class="w-full max-w-full" data-filepond data-max-file-size="50MB" required>
            <p class="mt-1 text-xs text-slate-500">Formatos aceitos: PDF, PNG, JPG e JPEG (maximo 50MB por arquivo, ate 20 arquivos por envio). Arquivos invalidos do lote sao ignorados e listados em aviso.</p>
        </div>

        <div>
            <label class="label-control">Metadados extras (JSON opcional)</label>
            <textarea
                name="metadata"
                class="input-control min-h-32"
                placeholder='{"origem":"portal_cliente","lote":"2026-03"}'>{{ is_array(old('metadata')) ? json_encode(old('metadata'), JSON_UNESCAPED_UNICODE) : old('metadata') }}</textarea>
        </div>

        <div class="grid gap-3 rounded-2xl border border-slate-200 bg-slate-50/70 p-3 text-xs text-slate-600 sm:grid-cols-3 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-300">
            <p class="flex items-center gap-1.5">
                <x-heroicon-o-document-text class="h-4 w-4 text-brand-600 dark:text-brand-300" />
                PDF, PNG, JPG e JPEG
            </p>
            <p class="flex items-center gap-1.5">
                <x-heroicon-o-scale class="h-4 w-4 text-brand-600 dark:text-brand-300" />
                Maximo 50MB por arquivo
            </p>
            <p class="flex items-center gap-1.5">
                <x-heroicon-o-queue-list class="h-4 w-4 text-brand-600 dark:text-brand-300" />
                Ate 20 arquivos por lote
            </p>
        </div>

        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <a href="{{ route('history') }}" class="btn-secondary w-full sm:w-auto" x-show="!uploading">Cancelar</a>
            <button type="submit" class="btn-primary w-full sm:w-auto" :disabled="uploading">
                <template x-if="!uploading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-bolt class="h-4 w-4" />
                        <span>Enviar e processar</span>
                    </div>
                </template>
                <template x-if="uploading">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-circle-notch fa-spin"></i>
                        <span>Enviando arquivos...</span>
                    </div>
                </template>
            </button>
        </div>
    </form>
@endsection
