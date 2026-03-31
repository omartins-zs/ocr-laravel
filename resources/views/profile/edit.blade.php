@extends('layouts.app')

@section('page-title', 'Perfil')

@section('content')
    <section class="page-header">
        <div class="page-title-wrap">
            <h2 class="page-title">
                <x-heroicon-o-user-circle class="h-6 w-6 text-brand-600 dark:text-brand-300" />
                Meu perfil
            </h2>
            <p class="page-subtitle">Atualize dados pessoais e senha de acesso.</p>
        </div>
    </section>

    <form method="POST" action="{{ route('profile.update') }}" class="card-surface max-w-3xl space-y-4 p-6">
        @csrf
        <div>
            <label class="label-control">Nome</label>
            <input type="text" name="name" required class="input-control" value="{{ old('name', auth()->user()->name) }}">
        </div>
        <div>
            <label class="label-control">Email</label>
            <input type="email" name="email" required class="input-control" value="{{ old('email', auth()->user()->email) }}">
        </div>
        <div>
            <label class="label-control">Nova senha</label>
            <input type="password" name="password" class="input-control" placeholder="Deixe em branco para manter">
        </div>
        <div>
            <label class="label-control">Confirmar senha</label>
            <input type="password" name="password_confirmation" class="input-control">
        </div>
        <div class="flex justify-end">
            <button type="submit" class="btn-primary">Salvar perfil</button>
        </div>
    </form>
@endsection
