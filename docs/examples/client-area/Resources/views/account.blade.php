@extends('client-area::layout')
@section('title', $register ? 'Crear cuenta' : 'Iniciar sesión')
@section('content')
    <h1>{{ $register ? 'Crea tu cuenta' : 'Entra a la zona de juegos' }}</h1>
    <p>{{ $register ? 'Regístrate para abrir el panel privado y jugar desde el navegador.' : 'Usa el correo y la contraseña de tu cuenta de jugador.' }}</p>

    <form class="panel" method="post" action="{{ route($register ? 'client-area.register' : 'client-area.login') }}">
        @csrf
        @if ($errors->any())
            <ul class="errors" role="alert">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif
        @if ($register)
            <label class="field">Nombre
                <input name="name" value="{{ old('name') }}" autocomplete="name" maxlength="100" required>
            </label>
        @endif
        <label class="field">Correo electrónico
            <input type="email" name="email" value="{{ old('email') }}" autocomplete="email" maxlength="255" required>
        </label>
        <label class="field">Contraseña
            <input type="password" name="password" autocomplete="{{ $register ? 'new-password' : 'current-password' }}" maxlength="72" @if ($register) minlength="10" @endif required>
        </label>
        @if ($register)
            <label class="field">Confirma la contraseña
                <input type="password" name="password_confirmation" autocomplete="new-password" minlength="10" maxlength="72" required>
            </label>
        @endif
        <button type="submit">{{ $register ? 'Crear mi cuenta' : 'Entrar' }}</button>
        <p><a href="{{ route($register ? 'client-area.login' : 'client-area.register') }}">{{ $register ? 'Ya tengo cuenta' : 'Crear una cuenta' }}</a></p>
    </form>
@endsection
