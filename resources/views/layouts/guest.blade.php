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
            class="btn-secondary"
            id="theme-toggle"
            data-tippy-content="Alternar tema">
            <i id="theme-toggle-dark-icon" class="fa-solid fa-moon hidden text-sm"></i>
            <i id="theme-toggle-light-icon" class="fa-solid fa-sun hidden text-sm"></i>
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
