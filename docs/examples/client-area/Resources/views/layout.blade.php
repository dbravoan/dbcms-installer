<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · Zona de juegos</title>
    <style nonce="{{ Vite::cspNonce() }}">
        :root { color-scheme: dark; font: 16px/1.55 system-ui, sans-serif; background: #0c1425; color: #f6f9ff; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; background: radial-gradient(ellipse at 85% 10%, #153659, transparent 48%); }
        a { color: #8de9df; text-underline-offset: 4px; }
        header { border-bottom: 1px solid #2a3b55; }
        nav, main { margin-inline: auto; max-width: 1060px; padding: 1rem 1.5rem; }
        nav, .toolbar { display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; }
        nav a { color: inherit; text-decoration: none; font-weight: 800; }
        main { padding-block: clamp(2rem, 7vw, 5rem); }
        h1 { font-size: clamp(2.1rem, 5vw, 3.8rem); line-height: 1.08; margin: 0 0 1rem; max-width: 18ch; }
        h2 { margin: 0 0 .65rem; font-size: 1.5rem; }
        p { max-width: 62ch; color: #becde2; }
        .panel, .tile { border: 1px solid #354a69; background: #17253b; border-radius: 1.15rem; padding: 1.5rem; }
        .panel { max-width: 32rem; margin-top: 1.8rem; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 18rem), 1fr)); gap: 1.25rem; margin-top: 2rem; }
        .tile::before { content: ""; display: block; width: 2.4rem; height: .25rem; border-radius: .35rem; background: #64ded0; margin-bottom: 1.4rem; }
        .tile:nth-child(2)::before { background: #bfb6ff; }
        .field { display: grid; gap: .35rem; margin: .8rem 0 1.1rem; font-weight: 650; }
        input { border: 1px solid #507093; border-radius: .55rem; background: #101a2d; color: inherit; padding: .72rem; font: inherit; width: 100%; }
        button, .action { display: inline-block; border: 0; border-radius: .6rem; background: #75e5d6; color: #102437; padding: .75rem 1rem; font: inherit; font-weight: 800; cursor: pointer; text-decoration: none; }
        .toolbar button, nav button { background: #233855; color: #f6f9ff; }
        :focus-visible { outline: 3px solid #bab0ff; outline-offset: 3px; }
        .errors { color: #ffc4bf; background: #542e46; padding: .75rem 1rem .75rem 2rem; border-radius: .6rem; }
        .game { max-width: 680px; margin-top: 1.6rem; }
        canvas { display: block; width: 100%; aspect-ratio: 480 / 300; border-radius: .65rem; background: #0c172a; margin: 1rem 0; }
        output { font-variant-numeric: tabular-nums; }
        .controls { display: flex; gap: .6rem; }
    </style>
</head>
<body>
    <header>
        <nav aria-label="Zona de juegos">
            <a href="{{ route('client-area.games') }}">Zona de juegos</a>
            @auth('client_area')
                <form method="post" action="{{ route('client-area.logout') }}">
                    @csrf
                    <button type="submit">Cerrar sesión</button>
                </form>
            @endauth
        </nav>
    </header>
    <main>@yield('content')</main>
</body>
</html>
