<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeAdmin($request);

        $roleValues = collect(UserRole::cases())->map->value->all();
        $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'role' => ['nullable', Rule::in($roleValues)],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        $users = User::query()
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = $request->string('q')->toString();
                $query->where(function ($inner) use ($term): void {
                    $inner->where('name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%");
                });
            })
            ->when(
                $request->filled('role'),
                fn ($query) => $query->where('role', $request->string('role')->toString()),
            )
            ->when(
                $request->string('status')->toString() === 'active',
                fn ($query) => $query->where('is_active', true),
            )
            ->when(
                $request->string('status')->toString() === 'inactive',
                fn ($query) => $query->where('is_active', false),
            )
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'total' => User::query()->count(),
            'active' => User::query()->where('is_active', true)->count(),
            'inactive' => User::query()->where('is_active', false)->count(),
        ];

        return view('users.index', [
            'users' => $users,
            'roles' => UserRole::cases(),
            'filters' => $request->only(['q', 'role', 'status']),
            'stats' => $stats,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $payload = $request->validate([
            'role' => ['required', Rule::in(collect(UserRole::cases())->map->value->all())],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $isActive = (bool) ($payload['is_active'] ?? false);

        if ($request->user()->id === $user->id && ! $isActive) {
            return redirect()
                ->back()
                ->withErrors(['is_active' => 'Voce nao pode desativar seu proprio usuario.'])
                ->withInput();
        }

        $user->update([
            'role' => $payload['role'],
            'is_active' => $isActive,
        ]);

        return redirect()
            ->route('users.index', array_filter([
                'q' => $request->input('q', $request->query('q')),
                'role' => $request->input('role_filter', $request->query('role')),
                'status' => $request->input('status_filter', $request->query('status')),
                'page' => $request->input('page_filter', $request->query('page')),
            ]))
            ->with('success', 'Usuario atualizado com sucesso.');
    }

    private function authorizeAdmin(Request $request): void
    {
        $role = $request->user()->role;
        $roleValue = $role instanceof UserRole ? $role->value : (string) $role;

        if (! in_array($roleValue, [UserRole::Admin->value, UserRole::Manager->value], true)) {
            abort(403);
        }
    }
}
