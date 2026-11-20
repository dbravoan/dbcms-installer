<script nonce="{{ Vite::cspNonce() }}">
    (() => {
        const root = document.querySelector('[data-game]');
        const canvas = root.querySelector('canvas');
        const paint = canvas.getContext('2d');
        const score = root.querySelector('[data-score]');
        const status = root.querySelector('[data-status]');
        const catchPoint = root.dataset.game === 'atrapa-el-punto';
        const columns = 4;
        const rows = 3;
        const cellWidth = canvas.width / columns;
        const cellHeight = canvas.height / rows;
        const lanes = 5;
        const laneWidth = canvas.width / lanes;
        const targetColors = ['#6ef2d4', '#a8a0ff', '#ffb685'];
        let running = false;
        let startedAt = 0;
        let lastObstacle = 0;
        let frame = 0;
        let points = 0;
        let cursor = 0;
        let target = 5;
        let lane = 2;
        let obstacles = [];

        function rectangle(x, y, width, height, color) {
            paint.fillStyle = color;
            paint.fillRect(x, y, width, height);
        }

        function render(age = 0) {
            rectangle(0, 0, canvas.width, canvas.height, '#0c172a');
            if (catchPoint) {
                for (let index = 0; index < columns * rows; index++) {
                    const left = (index % columns) * cellWidth;
                    const top = Math.floor(index / columns) * cellHeight;
                    rectangle(left + 4, top + 4, cellWidth - 8, cellHeight - 8, '#182943');
                    if (index === cursor) rectangle(left + 12, top + 12, cellWidth - 24, cellHeight - 24, '#294972');
                    if (index === target) {
                        paint.fillStyle = targetColors[points % targetColors.length];
                        paint.beginPath();
                        paint.arc(left + cellWidth / 2, top + cellHeight / 2, 21, 0, Math.PI * 2);
                        paint.fill();
                    }
                }
                return;
            }
            for (let column = 1; column < lanes; column++) {
                rectangle(column * laneWidth, 0, 1, canvas.height, '#243a57');
            }
            for (const obstacle of obstacles) {
                rectangle(obstacle.lane * laneWidth + 22, (age - obstacle.at) * 112, 52, 36, '#b1a4ff');
            }
            rectangle(lane * laneWidth + 29, canvas.height - 33, 38, 23, '#6ef2d4');
        }

        function stop(message) {
            running = false;
            status.textContent = message;
        }

        function showScore() {
            score.textContent = points + (points === 1 ? ' punto' : ' puntos');
        }

        function step(now) {
            if (!running) return;
            const age = (now - startedAt) / 1000;
            if (catchPoint) {
                if (age >= 20) {
                    stop('Tiempo agotado. Puntuación final: ' + points + '.');
                    return;
                }
            } else {
                if (age - lastObstacle >= .82) {
                    lastObstacle = age;
                    obstacles.push({ lane: Math.floor(Math.random() * lanes), at: age });
                }
                obstacles = obstacles.filter(obstacle => (age - obstacle.at) * 112 < canvas.height);
                points = Math.floor(age * 10);
                showScore();
                if (obstacles.some(obstacle => {
                    const top = (age - obstacle.at) * 112;
                    return obstacle.lane === lane && top + 36 >= canvas.height - 33 && top <= canvas.height - 10;
                })) {
                    stop('Has chocado. Puntuación final: ' + points + '.');
                    return;
                }
                if (age >= 30) {
                    stop('¡Has completado la partida! Puntuación: ' + points + '.');
                    return;
                }
            }
            render(age);
            frame = requestAnimationFrame(step);
        }

        function hit(index) {
            if (!running || index !== target) return;
            points++;
            showScore();
            target = (target + 1 + Math.floor(Math.random() * (columns * rows - 1))) % (columns * rows);
        }

        root.querySelector('[data-start]').addEventListener('click', () => {
            cancelAnimationFrame(frame);
            points = 0;
            cursor = 0;
            target = 5;
            lane = 2;
            lastObstacle = 0;
            obstacles = [];
            score.textContent = '0 puntos';
            status.textContent = 'Partida en marcha.';
            startedAt = performance.now();
            running = true;
            canvas.focus();
            frame = requestAnimationFrame(step);
        });

        canvas.addEventListener('pointerdown', event => {
            if (!catchPoint) return;
            const rect = canvas.getBoundingClientRect();
            const col = Math.floor((event.clientX - rect.left) / (rect.width / columns));
            const row = Math.floor((event.clientY - rect.top) / (rect.height / rows));
            hit(row * columns + col);
        });

        canvas.addEventListener('keydown', event => {
            if (!running) return;
            if (event.key === 'Enter' && catchPoint) {
                event.preventDefault();
                hit(cursor);
                return;
            }
            const move = { ArrowLeft: -1, ArrowRight: 1, ArrowUp: -columns, ArrowDown: columns }[event.key];
            if (move === undefined) return;
            event.preventDefault();
            if (catchPoint) cursor = Math.max(0, Math.min(columns * rows - 1, cursor + move));
            else if (Math.abs(move) === 1) lane = Math.max(0, Math.min(lanes - 1, lane + move));
        });

        root.querySelectorAll('[data-move]').forEach(control => {
            control.addEventListener('click', () => {
                if (running) lane = Math.max(0, Math.min(lanes - 1, lane + Number(control.dataset.move)));
            });
        });
        render();
    })();
</script>
