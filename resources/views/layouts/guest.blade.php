<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'OCR Platform') }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script>
        (() => {
            const mode = localStorage.getItem('color-theme');
            if (mode === 'dark' || (!mode && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="app-shell">
    <div class="fixed top-4 right-4 z-20">
        <button
            type="button"
            class="rounded-lg p-2.5 text-sm text-slate-500 transition-colors hover:bg-slate-100 focus:ring-4 focus:ring-slate-200 focus:outline-none dark:text-slate-400 dark:hover:bg-slate-700 dark:focus:ring-slate-700"
            id="theme-toggle"
            data-tippy-content="Alternar tema">
            <svg id="theme-toggle-dark-icon" class="hidden h-5 w-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path>
            </svg>
            <svg id="theme-toggle-light-icon" class="hidden h-5 w-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z"></path>
            </svg>
        </button>
    </div>
    <main class="flex min-h-screen items-center justify-center p-6">
        <div class="grid w-full max-w-6xl gap-6 lg:grid-cols-[1.1fr_0.9fr]">
            <section class="hidden rounded-3xl bg-gradient-to-br from-brand-700 via-brand-600 to-slate-900 p-10 text-white shadow-2xl lg:block">
                <p class="text-xs font-semibold tracking-[0.2em] uppercase text-brand-100">OCR Enterprise</p>
                <h1 class="mt-6 text-4xl leading-tight font-bold">Plataforma operacional para processamento documental inteligente.</h1>
                <p class="mt-4 text-sm text-brand-100">
                    Pipeline com OCRmyPDF, Tesseract, PaddleOCR opcional, aprovacao automatica e rastreabilidade completa.
                </p>
                <div class="mt-10 grid gap-3 text-sm">
                    <div class="rounded-xl bg-white/10 p-4">Fila assincrona com workers Laravel e monitoramento por logs.</div>
                    <div class="rounded-xl bg-white/10 p-4">Extracao estruturada com confianca por campo.</div>
                    <div class="rounded-xl bg-white/10 p-4">UI SaaS premium com dark mode real.</div>
                </div>
            </section>

            <section class="card-surface flex items-center p-6 sm:p-10">
                <div class="w-full">
                    @yield('content')
                </div>
            </section>
        </div>
    </main>
</body>
</html>
