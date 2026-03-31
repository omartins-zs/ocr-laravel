@extends('layouts.guest')

@section('content')
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-slate-800 dark:text-slate-100">Entrar no painel OCR</h2>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Use as credenciais do usuario administrador para iniciar.</p>
    </div>

    <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
        @csrf
        <div>
            <label for="email" class="label-control">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" class="input-control" required autofocus>
        </div>
        <div>
            <label for="password" class="label-control">Senha</label>
            <input id="password" type="password" name="password" class="input-control" required>
        </div>
        <label class="inline-flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
            <input type="checkbox" name="remember" value="1" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
            Lembrar de mim
        </label>
        <button type="submit" class="btn-primary w-full">
            <x-heroicon-o-lock-closed class="h-4 w-4" />
            Entrar
        </button>
    </form>

    <p class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-3 text-xs text-slate-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400">
        Usuario inicial: <strong>admin@ocr.local</strong> | Senha: <strong>password</strong>
    </p>
@endsection
