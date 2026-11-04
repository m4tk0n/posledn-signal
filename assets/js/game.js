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
    let playerX = Number(gameApp.dataset.playerX || 50);
    let playerY = Number(gameApp.dataset.playerY || 76);
    let savePositionTimer = 0;
    let positionSaveChain = Promise.resolve();
    let interactionBusy = false;
    let movementTimer = 0;

    const sceneObstacles = scene?.dataset.scene === 'cryo'
        ? [{left: 43, right: 57, top: 29, bottom: 61}]
        : [{left: 69, right: 77, top: 48, bottom: 69}];
    const INTERACTION_RANGE = 9;

    function persistPosition() {
        window.clearTimeout(savePositionTimer);
        savePositionTimer = window.setTimeout(() => {
            positionSaveChain = positionSaveChain
                .then(() => send({action: 'save_position', x: playerX, y: playerY, _csrf: csrf}))
                .catch((error) => setNotice(error.message));
        }, 350);
    }

    function isBlocked(x, y) {
        return sceneObstacles.some((obstacle) =>
            x >= obstacle.left && x <= obstacle.right &&
            y >= obstacle.top && y <= obstacle.bottom
        );
    }

    function setNotice(message) {
        if (!positionLabel) return;
        positionLabel.textContent = message;
        positionLabel.classList.add('movement-notice');
    }

    function updatePosition() {
        if (!character) return;
        character.style.left = playerX + '%';
        character.style.top = playerY + '%';
        character.classList.add('is-moving');
        window.clearTimeout(movementTimer);
        movementTimer = window.setTimeout(() => character.classList.remove('is-moving'), 220);
        if (positionLabel) {
            positionLabel.classList.remove('movement-notice');
            const horizontal = playerX < 35 ? 'levá část' : (playerX > 65 ? 'pravá část' : 'střed');
            const vertical = playerY < 48 ? 'zadní část' : (playerY > 70 ? 'přední část' : 'střed místnosti');
            positionLabel.textContent = 'Pozice: ' + horizontal + ' / ' + vertical;
        }
    }

    function movePlayer(dx, dy) {
        if (!character || interactionBusy) return false;
        const nextX = Math.max(7, Math.min(93, playerX + dx * 3.5));
        const nextY = Math.max(28, Math.min(89, playerY + dy * 4));
        if (nextX === playerX && nextY === playerY) return false;
        if (isBlocked(nextX, nextY)) {
            setNotice('V cestě je překážka. Zkus jiný směr.');
            return false;
        }
        playerX = nextX;
        playerY = nextY;
        updatePosition();
        persistPosition();
        return true;
    }

    function moveToScenePoint(event) {
        if (!scene || !character || interactionBusy || event.target.closest('button')) return;
        const bounds = scene.getBoundingClientRect();
        const x = Math.max(7, Math.min(93, ((event.clientX - bounds.left) / bounds.width) * 100));
        const y = Math.max(28, Math.min(89, ((event.clientY - bounds.top) / bounds.height) * 100));
        if (isBlocked(x, y)) {
            setNotice('Tudy cesta nevede. V prostoru je překážka.');
            return;
        }
        playerX = x;
        playerY = y;
        updatePosition();
        persistPosition();
    }

    moveButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const [dx, dy] = button.dataset.move.split(',').map(Number);
            movePlayer(dx, dy);
        });
    });

    if (scene) {
        scene.addEventListener('click', moveToScenePoint);
        scene.tabIndex = 0;
        scene.setAttribute('aria-label', scene.getAttribute('aria-label') + '. Pro pohyb použij W A S D, šipky nebo klepni na volné místo podlahy.');
    }

    document.addEventListener('keydown', (event) => {
        if (event.altKey || event.ctrlKey || event.metaKey) return;
        const active = document.activeElement;
        if (active && (active.matches('input, textarea, select') || active.isContentEditable)) return;
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

    updatePosition();

    gameApp.querySelectorAll('[data-command]').forEach((button) => {
        button.addEventListener('click', async () => {
            if (interactionBusy) return;
            const command = button.dataset.command;
            const targetX = Number(button.dataset.targetX);
            const targetY = Number(button.dataset.targetY);
            const hasTarget = Number.isFinite(targetX) && Number.isFinite(targetY);
            const matchingButtons = [...gameApp.querySelectorAll('[data-command]')]
                .filter((item) => item.dataset.command === command);
            const originalTexts = new Map(matchingButtons.map((item) => [item, item.textContent]));

            if (hasTarget && Math.hypot(targetX - playerX, targetY - playerY) > INTERACTION_RANGE) {
                if (isBlocked(targetX, targetY)) {
                    setNotice('K tomuto objektu se z této strany nedostaneš.');
                    return;
                }
                interactionBusy = true;
                moveButtons.forEach((item) => { item.disabled = true; });
                matchingButtons.forEach((item) => { item.disabled = true; });
                playerX = targetX;
                playerY = targetY;
                character.style.left = playerX + '%';
                character.style.top = playerY + '%';
                character.classList.add('is-moving');
                setNotice('Přistupuješ k objektu…');
                await new Promise((resolve) => window.setTimeout(resolve, 850));
                character.classList.remove('is-moving');
                try {
                    await send({action: 'save_position', x: playerX, y: playerY, _csrf: csrf});
                } catch (error) {
                    interactionBusy = false;
                    moveButtons.forEach((item) => { item.disabled = false; });
                    matchingButtons.forEach((item) => { item.disabled = false; });
                    setNotice(error.message);
                    return;
                }
                interactionBusy = false;
                moveButtons.forEach((item) => { item.disabled = false; });
                matchingButtons.forEach((item) => { item.disabled = false; });
                setNotice('Jsi u objektu. Znovu vyber stejnou akci.');
                return;
            }

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
                window.clearTimeout(savePositionTimer);
                await positionSaveChain;
                await send({action: 'save_position', x: playerX, y: playerY, _csrf: csrf});
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
                updatePosition();
            }
        });
    });
})();