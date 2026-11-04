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

    gameApp.querySelectorAll('[data-command]').forEach((button) => {
        button.addEventListener('click', async () => {
            button.disabled = true;
            const originalText = button.textContent;
            button.textContent = 'Probíhá…';
            try {
                await send({action: 'interact', command: button.dataset.command, _csrf: csrf});
                window.location.reload();
            } catch (error) {
                window.alert(error.message);
                button.disabled = false;
                button.textContent = originalText;
            }
        });
    });
})();
