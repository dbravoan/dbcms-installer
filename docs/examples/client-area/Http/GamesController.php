<?php

declare(strict_types=1);

namespace DavidBravo\ClientArea\Http;

use Illuminate\Contracts\View\View;

final class GamesController
{
    private const GAMES = [
        'atrapa-el-punto' => ['title' => 'Atrapa el punto', 'description' => 'Pulsa la casilla luminosa tantas veces como puedas en 20 segundos.'],
        'esquiva-bloques' => ['title' => 'Esquiva bloques', 'description' => 'Mueve la ficha a izquierda y derecha para evitar los bloques que caen.'],
    ];

    public function index(): View
    {
        return view('client-area::games', ['games' => self::GAMES]);
    }

    public function show(string $game): View
    {
        $details = self::GAMES[$game] ?? abort(404);

        return view('client-area::game', ['game' => $game, ...$details]);
    }
}
