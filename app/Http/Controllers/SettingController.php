<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\UpdateSettingsRequest;
use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingController extends Controller
{
    private function roleValue(Request $request): string
    {
        $role = $request->user()->role;

        return $role instanceof UserRole ? $role->value : (string) $role;
    }

    public function index(Request $request): View
    {
        $this->authorizeAdmin($request);

        $settings = Setting::query()
            ->orderBy('group')
            ->orderBy('key')
            ->get()
            ->groupBy('group');

        return view('settings.index', [
            'settings' => $settings,
        ]);
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $this->authorizeAdmin($request);

        DB::transaction(function () use ($request): void {
            foreach ($request->validated('settings') as $payload) {
                Setting::query()
                    ->whereKey($payload['id'])
                    ->update([
                        'value' => $payload['value'] ?? null,
                        'updated_by' => $request->user()->id,
                    ]);
            }
        });

        return redirect()
            ->route('settings.index')
            ->with('success', 'Configurações atualizadas com sucesso.');
    }

    private function authorizeAdmin(Request $request): void
    {
        if (! in_array($this->roleValue($request), [UserRole::Admin->value, UserRole::Manager->value], true)) {
            abort(403);
        }
    }
}
