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
    gameApp.querySelectorAll('[data-command]').forEach((button) => {
        button.addEventListener('click', async () => {
            const command = button.dataset.command;
            const matchingButtons = [...gameApp.querySelectorAll('[data-command]')]
                .filter((item) => item.dataset.command === command);
            const originalTexts = new Map(matchingButtons.map((item) => [item, item.textContent]));
            matchingButtons.forEach((item) => {
                item.disabled = true;
                item.classList.add('is-busy');
                if (!item.classList.contains('scene-hotspot')) {
                    item.textContent = 'Probíhá…';
                }
            });

            try {
                if (character && button.dataset.targetX && button.dataset.targetY) {
                    character.style.left = button.dataset.targetX + '%';
                    character.style.top = button.dataset.targetY + '%';
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
                if (character) {
                    character.style.left = '50%';
                    character.style.top = '76%';
                    character.classList.remove('is-moving');
                }
            }
        });
    });
})();
