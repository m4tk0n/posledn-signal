(() => {
    const startForm = document.getElementById('start-game-form');
    const gameApp = document.getElementById('game-app');

    async function send(data) {
        const body = new URLSearchParams(data);
        const response = await fetch('api/game.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'},
            body,
            credentials: 'same-origin'
        });
        const result = await response.json();
        if (!response.ok || !result.ok) {
            throw new Error(result.message || 'Požadavek se nepodařilo dokončit.');
        }
        return result.game;
    }

    if (startForm) {
        startForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const button = startForm.querySelector('button');
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
                alert(error.message);
                button.disabled = false;
                button.textContent = 'Začít příběh';
            }
        });
    }

    if (!gameApp) return;
    const csrf = gameApp.dataset.csrf;
    const message = document.getElementById('story-message');
    const journal = document.getElementById('journal-list');

    gameApp.querySelectorAll('[data-command]').forEach((button) => {
        button.addEventListener('click', async () => {
            const command = button.dataset.command;
            button.disabled = true;
            try {
                const result = await send({action: 'interact', command, _csrf: csrf});
                message.textContent = result.message;
                const item = document.createElement('li');
                item.textContent = result.message;
                journal.appendChild(item);
            } catch (error) {
                alert(error.message);
                button.disabled = false;
            }
        });
    });
})();
