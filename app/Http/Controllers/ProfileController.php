<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function edit(): View
    {
        return view('profile.edit');
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $payload = $request->validated();

        $user->name = $payload['name'];
        $user->email = $payload['email'];

        if (! empty($payload['password'])) {
            $user->password = Hash::make($payload['password']);
        }

        $user->save();

        return redirect()
            ->route('profile.edit')
            ->with('success', 'Perfil atualizado com sucesso.');
    }
}
