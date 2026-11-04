(() => {
    const startForm = document.getElementById('start-game-form');
    const gameApp = document.getElementById('game-app');

    async function send(data) {
        const response = await fetch('api/game.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'},
            body: new URLSearchParams(data),
            credentials: 'same-origin',
            cache: 'no-store'
        });

        let result;
        try {
            result = await response.json();
        } catch {
            throw new Error('Server vrátil neplatnou odpověď.');
        }
        if (!response.ok || !result.ok) {
            throw new Error(result.message || 'Požadavek se nepodařilo dokončit.');
        }
        return result.game;
    }

    if (startForm) {
        startForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const button = startForm.querySelector('button[type="submit"]');
            button.disabled = true;
            button.textContent = 'Navazuji spojení…';
            try {
                await send({
                    action: 'start',
                    name: startForm.elements.name.value,
                    _csrf: startForm.elements._csrf.value
                });
                window.location.reload();
            } catch (error) {
                window.alert(error.message);
                button.disabled = false;
                button.textContent = 'Začít příběh';
            }
        });
    }

    if (!gameApp) return;
    const csrf = gameApp.dataset.csrf;
    const character = gameApp.querySelector('#scene-character');
    const scene = gameApp.querySelector('.scene-visual');
    const moveButtons = [...gameApp.querySelectorAll('[data-move]')];
    const positionLabel = gameApp.querySelector('#movement-position');
    let playerX = 50;
    let playerY = 76;
    let interactionBusy = false;
    let movementTimer = 0;

    function updatePosition() {
        if (!character) return;
        character.style.left = playerX + '%';
        character.style.top = playerY + '%';
        character.classList.add('is-moving');
        window.clearTimeout(movementTimer);
        movementTimer = window.setTimeout(() => character.classList.remove('is-moving'), 220);
        if (positionLabel) {
            const horizontal = playerX < 35 ? 'levá část' : (playerX > 65 ? 'pravá část' : 'střed');
            const vertical = playerY < 48 ? 'zadní část' : (playerY > 70 ? 'přední část' : 'střed místnosti');
            positionLabel.textContent = 'Pozice: ' + horizontal + ' / ' + vertical;
        }
    }

    function movePlayer(dx, dy) {
        if (!character || interactionBusy) return;
        const nextX = Math.max(7, Math.min(93, playerX + dx * 3.5));
        const nextY = Math.max(28, Math.min(89, playerY + dy * 4));
        if (nextX === playerX && nextY === playerY) return;
        playerX = nextX;
        playerY = nextY;
        updatePosition();
    }

    moveButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const [dx, dy] = button.dataset.move.split(',').map(Number);
            movePlayer(dx, dy);
        });
    });

    if (scene) {
        scene.addEventListener('keydown', (event) => {
            const key = event.key.toLowerCase();
            const directions = {
                arrowup: [0, -1], w: [0, -1],
                arrowdown: [0, 1], s: [0, 1],
                arrowleft: [-1, 0], a: [-1, 0],
                arrowright: [1, 0], d: [1, 0]
            };
            if (!directions[key]) return;
            event.preventDefault();
            movePlayer(...directions[key]);
        });
        scene.tabIndex = 0;
        scene.setAttribute('aria-label', scene.getAttribute('aria-label') + '. Pro pohyb použij W A S D nebo šipky.');
    }

    gameApp.querySelectorAll('[data-command]').forEach((button) => {
        button.addEventListener('click', async () => {
            if (interactionBusy) return;
            const command = button.dataset.command;
            const matchingButtons = [...gameApp.querySelectorAll('[data-command]')]
                .filter((item) => item.dataset.command === command);
            const originalTexts = new Map(matchingButtons.map((item) => [item, item.textContent]));
            interactionBusy = true;
            moveButtons.forEach((item) => { item.disabled = true; });
            matchingButtons.forEach((item) => {
                item.disabled = true;
                item.classList.add('is-busy');
                if (!item.classList.contains('scene-hotspot')) {
                    item.textContent = 'Probíhá…';
                }
            });

            try {
                if (character && button.dataset.targetX && button.dataset.targetY) {
                    playerX = Number(button.dataset.targetX);
                    playerY = Number(button.dataset.targetY);
                    character.style.left = playerX + '%';
                    character.style.top = playerY + '%';
                    character.classList.add('is-moving');
                    await new Promise((resolve) => window.setTimeout(resolve, 850));
                }
                await send({action: 'interact', command, _csrf: csrf});
                window.location.reload();
            } catch (error) {
                window.alert(error.message);
                matchingButtons.forEach((item) => {
                    item.disabled = false;
                    item.classList.remove('is-busy');
                    item.textContent = originalTexts.get(item);
                });
                interactionBusy = false;
                moveButtons.forEach((item) => { item.disabled = false; });
                if (character) {
                    playerX = 50;
                    playerY = 76;
                    updatePosition();
                    character.classList.remove('is-moving');
                }
            }
        });
    });
})();