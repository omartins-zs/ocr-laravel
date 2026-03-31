@extends('layouts.app')

@section('page-title', 'Usuarios')

@section('content')
    <section class="page-header">
        <div class="page-title-wrap">
            <h2 class="page-title">
                <x-heroicon-o-users class="h-6 w-6 text-brand-600 dark:text-brand-300" />
                Gestao de usuarios
            </h2>
            <p class="page-subtitle">Controle de perfis e acesso operacional sem friccao.</p>
        </div>
    </section>

    <section class="mb-6 grid gap-3 sm:grid-cols-3">
        <article class="stat-card">
            <p class="stat-label">Total</p>
            <p class="stat-value">{{ number_format($stats['total']) }}</p>
        </article>
        <article class="stat-card">
            <p class="stat-label">Ativos</p>
            <p class="stat-value text-emerald-600 dark:text-emerald-300">{{ number_format($stats['active']) }}</p>
        </article>
        <article class="stat-card">
            <p class="stat-label">Inativos</p>
            <p class="stat-value text-amber-600 dark:text-amber-300">{{ number_format($stats['inactive']) }}</p>
        </article>
    </section>

    <section class="grid gap-6">
        <article class="card-surface">
            <div class="border-b border-slate-200/70 p-4 dark:border-slate-800">
                <form method="GET" class="grid gap-3 md:grid-cols-4">
                    <div class="md:col-span-2">
                        <label class="label-control">Buscar</label>
                        <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="input-control" placeholder="Nome ou email">
                    </div>
                    <div>
                        <label class="label-control">Perfil</label>
                        <select name="role" class="w-full">
                            <option value="">Todos</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->value }}" @selected(($filters['role'] ?? null) === $role->value)>
                                    {{ $role->label() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label-control">Status</label>
                        <select name="status" class="w-full">
                            <option value="">Todos</option>
                            <option value="active" @selected(($filters['status'] ?? null) === 'active')>Ativo</option>
                            <option value="inactive" @selected(($filters['status'] ?? null) === 'inactive')>Inativo</option>
                        </select>
                    </div>
                    <div class="md:col-span-4 flex justify-end gap-2">
                        <a href="{{ route('users.index') }}" class="btn-secondary">Limpar</a>
                        <button type="submit" class="btn-primary">Filtrar</button>
                    </div>
                </form>
            </div>

            <div class="overflow-x-auto p-3">
                <table class="table-enterprise min-w-full text-sm">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Perfil</th>
                            <th>Status</th>
                            <th>Ultimo login</th>
                            <th class="text-right">Acao</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            <tr>
                                <td>
                                    <p class="font-semibold text-slate-900 dark:text-slate-100">{{ $user->name }}</p>
                                    <p class="text-xs text-slate-500">{{ $user->email }}</p>
                                </td>
                                <td>
                                    <span class="badge-status badge-status-info">{{ $user->role?->label() ?? '-' }}</span>
                                </td>
                                <td>
                                    @if ($user->is_active)
                                        <span class="badge-status badge-status-success">Ativo</span>
                                    @else
                                        <span class="badge-status badge-status-warning">Inativo</span>
                                    @endif
                                </td>
                                <td class="text-xs text-slate-500">{{ $user->last_login_at?->diffForHumans() ?? 'Nunca' }}</td>
                                <td class="text-right">
                                    <button
                                        type="button"
                                        class="btn-secondary text-xs"
                                        data-user-manage
                                        data-user-name="{{ $user->name }}"
                                        data-user-email="{{ $user->email }}"
                                        data-user-role="{{ $user->role?->value ?? $user->role }}"
                                        data-user-active="{{ $user->is_active ? '1' : '0' }}"
                                        data-user-update-url="{{ route('users.update', $user) }}">
                                        Gerenciar
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <x-empty-state title="Nenhum usuario encontrado" description="Ajuste os filtros para localizar usuarios." />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-4 pb-4">
                {{ $users->links() }}
            </div>
        </article>
    </section>

    <div id="user-manage-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 p-4">
        <div class="card-surface w-full max-w-xl p-5">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Editar usuario</h3>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Atualize perfil e status somente quando necessario.</p>
                </div>
                <button type="button" class="btn-secondary" data-modal-close>
                    <x-heroicon-o-x-mark class="h-4 w-4" />
                </button>
            </div>

            <form method="POST" action="" class="mt-5 space-y-4" id="user-manage-form">
                @csrf
                @method('PATCH')

                @if (request()->filled('q'))
                    <input type="hidden" name="q" value="{{ request()->query('q') }}">
                @endif
                @if (request()->filled('role'))
                    <input type="hidden" name="role_filter" value="{{ request()->query('role') }}">
                @endif
                @if (request()->filled('status'))
                    <input type="hidden" name="status_filter" value="{{ request()->query('status') }}">
                @endif
                @if (request()->filled('page'))
                    <input type="hidden" name="page_filter" value="{{ request()->query('page') }}">
                @endif

                <div>
                    <label class="label-control">Nome</label>
                    <input type="text" value="" class="input-control" id="modal-user-name" disabled>
                </div>

                <div>
                    <label class="label-control">Email</label>
                    <input type="text" value="" class="input-control" id="modal-user-email" disabled>
                </div>

                <div>
                    <label class="label-control">Perfil</label>
                    <select name="role" class="w-full" id="modal-user-role">
                        @foreach ($roles as $role)
                            <option value="{{ $role->value }}">
                                {{ $role->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="label-control">Status</label>
                    <select name="is_active" class="w-full" id="modal-user-active">
                        <option value="1">Ativo</option>
                        <option value="0">Inativo</option>
                    </select>
                </div>

                <div class="flex flex-col-reverse gap-2 pt-2 sm:flex-row sm:justify-end">
                    <button type="button" class="btn-secondary" data-modal-close>Cancelar</button>
                    <button type="submit" class="btn-primary">
                        <x-heroicon-o-check class="h-4 w-4" />
                        Salvar alteracoes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (() => {
            const modal = document.getElementById('user-manage-modal');
            const form = document.getElementById('user-manage-form');
            const nameField = document.getElementById('modal-user-name');
            const emailField = document.getElementById('modal-user-email');
            const roleField = document.getElementById('modal-user-role');
            const activeField = document.getElementById('modal-user-active');

            if (!modal || !form || !nameField || !emailField || !roleField || !activeField) {
                return;
            }

            const closeModal = () => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.classList.remove('overflow-hidden');
            };

            const openModal = (trigger) => {
                form.action = trigger.dataset.userUpdateUrl || '';
                nameField.value = trigger.dataset.userName || '';
                emailField.value = trigger.dataset.userEmail || '';
                roleField.value = trigger.dataset.userRole || '';
                activeField.value = trigger.dataset.userActive || '1';
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                document.body.classList.add('overflow-hidden');
            };

            document.querySelectorAll('[data-user-manage]').forEach((button) => {
                button.addEventListener('click', () => openModal(button));
            });

            modal.querySelectorAll('[data-modal-close]').forEach((button) => {
                button.addEventListener('click', closeModal);
            });

            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal();
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                    closeModal();
                }
            });
        })();
    </script>
@endsection
