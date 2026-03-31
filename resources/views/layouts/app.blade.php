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
<body class="app-shell h-full" x-data="{ sidebarOpen: false }">
    <div class="min-h-screen lg:flex">
        <aside
            class="fixed inset-y-0 left-0 z-40 w-72 -translate-x-full border-r border-slate-200/80 bg-white/95 p-5 shadow-xl shadow-slate-200/30 backdrop-blur transition-transform duration-300 dark:border-slate-700/60 dark:bg-slate-950/90 dark:shadow-black/20 lg:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">
            <div class="flex items-center gap-3 rounded-2xl border border-slate-200/80 bg-slate-50/70 p-3 dark:border-slate-700/80 dark:bg-slate-900/80">
                <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-700 via-brand-600 to-sky-500 text-white shadow-md">
                    <x-heroicon-o-cpu-chip class="h-5 w-5" />
                </div>
                <div>
                    <p class="text-[11px] font-semibold tracking-wider text-slate-500 uppercase dark:text-slate-400">OCR Suite</p>
                    <p class="text-sm font-bold text-slate-800 dark:text-slate-100">Control Center</p>
                </div>
            </div>

            <nav class="mt-8 space-y-1.5 text-sm">
                @php
                    $isUploadRoute = request()->routeIs('documents.create') || request()->routeIs('documents.store');
                    $isHistoryRoute = request()->routeIs('documents.*') && ! $isUploadRoute;
                    $role = auth()->user()?->role;
                    $roleValue = $role instanceof \App\Enums\UserRole ? $role->value : (string) $role;
                    $canManageUsers = in_array($roleValue, [\App\Enums\UserRole::Admin->value, \App\Enums\UserRole::Manager->value], true);
                @endphp
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2.5 {{ request()->routeIs('dashboard') ? 'bg-brand-600 text-white shadow-sm shadow-brand-600/30' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800' }}">
                    <x-heroicon-o-home class="h-4 w-4" /> Dashboard
                </a>
                <a href="{{ route('upload') }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2.5 {{ $isUploadRoute ? 'bg-brand-600 text-white shadow-sm shadow-brand-600/30' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800' }}">
                    <x-heroicon-o-cloud-arrow-up class="h-4 w-4" /> Upload
                </a>
                <a href="{{ route('history') }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2.5 {{ $isHistoryRoute ? 'bg-brand-600 text-white shadow-sm shadow-brand-600/30' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800' }}">
                    <x-heroicon-o-archive-box class="h-4 w-4" /> Historico
                </a>
                @if ($canManageUsers)
                    <a href="{{ route('users.index') }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2.5 {{ request()->routeIs('users.*') ? 'bg-brand-600 text-white shadow-sm shadow-brand-600/30' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800' }}">
                        <x-heroicon-o-users class="h-4 w-4" /> Usuarios
                    </a>
                @endif
                <a href="{{ route('queue-status.index') }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2.5 {{ request()->routeIs('queue-status.*') ? 'bg-brand-600 text-white shadow-sm shadow-brand-600/30' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800' }}">
                    <x-heroicon-o-bolt class="h-4 w-4" /> Fila e status
                </a>
                <a href="{{ route('processing-logs.index') }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2.5 {{ request()->routeIs('processing-logs.*') ? 'bg-brand-600 text-white shadow-sm shadow-brand-600/30' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800' }}">
                    <x-heroicon-o-command-line class="h-4 w-4" /> Logs
                </a>
                <a href="{{ route('reports.index') }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2.5 {{ request()->routeIs('reports.*') ? 'bg-brand-600 text-white shadow-sm shadow-brand-600/30' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800' }}">
                    <x-heroicon-o-chart-bar class="h-4 w-4" /> Relatorios
                </a>
                <a href="{{ route('settings.index') }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2.5 {{ request()->routeIs('settings.*') ? 'bg-brand-600 text-white shadow-sm shadow-brand-600/30' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800' }}">
                    <x-heroicon-o-cog-6-tooth class="h-4 w-4" /> Configuracoes
                </a>
            </nav>
        </aside>

        <div class="flex min-h-screen flex-1 flex-col lg:pl-72">
            <header class="sticky top-0 z-30 border-b border-slate-200/80 bg-white/85 backdrop-blur dark:border-slate-700/70 dark:bg-slate-950/85">
                <div class="mx-auto flex w-full max-w-[1600px] items-center justify-between gap-4 px-4 py-3 sm:px-6">
                    <div class="flex items-center gap-2">
                        <button class="btn-secondary lg:hidden" @click="sidebarOpen = !sidebarOpen">
                            <x-heroicon-o-bars-3 class="h-4 w-4" />
                        </button>
                        <div>
                            <p class="text-xs uppercase tracking-widest text-slate-500 dark:text-slate-400">{{ now()->format('d/m/Y H:i') }}</p>
                            <h1 class="text-base font-semibold text-slate-800 dark:text-slate-100">@yield('page-title', 'OCR Platform')</h1>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <a href="{{ route('upload') }}" class="btn-primary hidden md:inline-flex">
                            <x-heroicon-o-cloud-arrow-up class="h-4 w-4" />
                            Upload rapido
                        </a>
                        <div class="hidden overflow-hidden rounded-full border border-slate-200 bg-slate-50 text-slate-600 transition-colors dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 md:inline-flex">
                            <span
                                data-ocr-status-badge
                                data-ocr-status-url="{{ route('ocr.status') }}"
                                data-ocr-status-poll-ms="{{ (int) config('ocr.status_poll_ms', 30000) }}"
                                data-ocr-status-request-timeout-ms="{{ (int) config('ocr.status_request_timeout_ms', 8000) }}"
                                data-ocr-status-failure-threshold="{{ (int) config('ocr.status_failure_threshold', 2) }}"
                                data-ocr-status-auto-poll="{{ request()->routeIs('upload') ? '0' : '1' }}"
                                data-ocr-status-console-log="{{ config('app.debug') ? '1' : '0' }}"
                                class="inline-flex items-center gap-1 border-r border-slate-200/70 px-2.5 py-1 text-xs font-medium dark:border-slate-700/70"
                                data-tippy-content="Verificando OCR externo...">
                                <span data-ocr-status-dot class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                                <x-heroicon-o-cpu-chip class="h-3.5 w-3.5" />
                                <span data-ocr-status-label>OCR verificando...</span>
                            </span>
                            <button
                                type="button"
                                class="inline-flex items-center justify-center px-2.5 text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-slate-400 dark:hover:bg-slate-700/60 dark:hover:text-slate-100"
                                data-ocr-status-refresh
                                data-tippy-content="Verificar OCR agora"
                                aria-label="Verificar OCR agora">
                                <x-heroicon-o-arrow-path class="h-4 w-4" data-ocr-status-refresh-icon />
                            </button>
                        </div>
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
                        <a href="{{ route('profile.edit') }}" class="btn-secondary">
                            <x-heroicon-o-user-circle class="h-4 w-4" />
                            <span class="hidden sm:inline">{{ auth()->user()->name }}</span>
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn-secondary">
                                <x-heroicon-o-arrow-right-start-on-rectangle class="h-4 w-4" />
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            <main class="mx-auto w-full max-w-[1600px] flex-1 px-4 py-6 sm:px-6">
                @yield('content')
            </main>
        </div>
    </div>

    @if (session('success'))
        <script>
            window.dispatchEvent(new CustomEvent('toast', {
                detail: { type: 'success', message: @js(session('success')) }
            }));
        </script>
    @endif

    @if (session('warning'))
        <script>
            window.dispatchEvent(new CustomEvent('toast', {
                detail: { type: 'warning', message: @js(session('warning')) }
            }));
        </script>
    @endif

    @if ($errors->any())
        <script>
            window.dispatchEvent(new CustomEvent('toast', {
                detail: { type: 'error', message: @js($errors->first()) }
            }));
        </script>
    @endif
</body>
</html>
