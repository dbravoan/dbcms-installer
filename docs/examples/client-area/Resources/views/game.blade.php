@extends('client-area::layout')
@section('title', $title)
@section('content')
    <p><a href="{{ route('client-area.games') }}">Volver al panel</a></p>
    <h1>{{ $title }}</h1>
    <p id="game-rules">{{ $description }} @if ($game === 'atrapa-el-punto') Con el teclado, usa las flechas para seleccionar una casilla y pulsa Intro. @endif</p>

    <div class="panel game" data-game="{{ $game }}">
        <div class="toolbar">
            <button type="button" data-start>Empezar partida</button>
            <output data-score aria-label="Puntuación">0 puntos</output>
        </div>
        <canvas width="480" height="300" tabindex="0" aria-label="{{ $title }}" aria-describedby="game-rules">
            Este navegador necesita Canvas para mostrar el juego.
        </canvas>
        @if ($game === 'esquiva-bloques')
            <div class="controls">
                <button type="button" data-move="-1">← Izquierda</button>
                <button type="button" data-move="1">Derecha →</button>
            </div>
        @endif
        <p data-status role="status">Listo para empezar.</p>
    </div>
    @include('client-area::game-script')
@endsection
