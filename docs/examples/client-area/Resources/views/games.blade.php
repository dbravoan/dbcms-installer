@extends('client-area::layout')
@section('title', 'Panel privado')
@section('content')
    <h1>Hola, {{ auth('client_area')->user()->name }}</h1>
    <p>Elige un juego para empezar. Puedes jugar con el teclado o los controles en pantalla.</p>

    <div class="grid">
        @foreach ($games as $slug => $game)
            <article class="tile">
                <h2>{{ $game['title'] }}</h2>
                <p>{{ $game['description'] }}</p>
                <a class="action" href="{{ route('client-area.game', ['game' => $slug]) }}">Jugar a {{ $game['title'] }}</a>
            </article>
        @endforeach
    </div>
@endsection
