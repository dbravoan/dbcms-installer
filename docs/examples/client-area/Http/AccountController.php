<?php

declare(strict_types=1);

namespace DavidBravo\ClientArea\Http;

use DavidBravo\ClientArea\Models\Player;
use Illuminate\Contracts\View\View;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class AccountController
{
    public function registration(): View
    {
        return view('client-area::account', ['register' => true]);
    }

    public function loginForm(): View
    {
        return view('client-area::account', ['register' => false]);
    }

    public function register(Request $request): RedirectResponse
    {
        $this->normalizeEmail($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:client_area_players,email'],
            'password' => ['required', 'string', 'min:10', 'max:72', 'confirmed'],
        ]);

        try {
            $player = Player::query()->create($data);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['email' => 'Ya existe una cuenta con ese correo.']);
        }

        Auth::guard('client_area')->login($player);
        $request->session()->regenerate(true);

        return redirect()->route('client-area.games');
    }

    public function login(Request $request): RedirectResponse
    {
        $this->normalizeEmail($request);
        $credentials = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:72'],
        ]);

        if (! Auth::guard('client_area')->attempt($credentials)) {
            throw ValidationException::withMessages(['email' => 'El correo o la contraseña no son válidos.']);
        }

        $request->session()->regenerate(true);

        return redirect()->route('client-area.games');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('client_area')->logout();
        $request->session()->regenerate(true);

        return redirect()->route('client-area.login');
    }

    private function normalizeEmail(Request $request): void
    {
        $request->merge(['email' => Str::lower($request->string('email')->trim()->toString())]);
    }
}
