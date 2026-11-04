<?php
declare(strict_types=1);

use App\Game\GameService;
use App\Security\Session;

$error = '';
$setupRequired = false;
$game = null;
$csrf = '';

try {
    require __DIR__ . '/app/bootstrap.php';
    require_once BASE_PATH . '/app/Game/GameService.php';
    $csrf = Session::csrfToken();
    $setupRequired = !GameService::isInstalled();

    if (!$setupRequired && !empty($_SESSION['player_id']) && !empty($_SESSION['active_save_id'])) {
        try {
            $game = GameService::loadGame((int) $_SESSION['player_id'], (int) $_SESSION['active_save_id']);
        } catch (Throwable $exception) {
            unset($_SESSION['player_id'], $_SESSION['active_save_id']);
            error_log('Active save could not be loaded: ' . $exception->getMessage());
        }
    }
} catch (Throwable $exception) {
    error_log('Application startup failed: ' . $exception->getMessage());
    $error = 'Aplikaci se nepodařilo spustit. Zkontroluj serverovou konfiguraci .env a databázové připojení.';
    http_response_code(500);
}

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>
<!doctype html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#101820">
    <title>POSLEDNÍ SIGNÁL</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/game.css">
</head>
<body>
<main class="game-shell">
    <header class="game-header">
        <p class="eyebrow">ZÁZNAM SYSTÉMU // 0001</p>
        <h1>POSLEDNÍ SIGNÁL</h1>
        <p class="subtitle">Podzemní výzkumný komplex // Nouzový režim</p>
    </header>

    <?php if ($error !== ''): ?>
        <section class="game-panel"><p class="error-message"><?= h($error) ?></p></section>
    <?php elseif ($setupRequired): ?>
        <section class="game-panel">
            <p class="system-label">SYSTÉM NENÍ INICIALIZOVÁN</p>
            <h2>Databáze čeká na instalaci</h2>
            <p>Nejdříve nastav na serveru soubor <code>.env</code> podle <code>.env.example</code>, včetně databázových údajů, <code>DB_PREFIX</code> a unikátního <code>APP_KEY</code>.</p>
            <a class="game-button" href="install/install.php">Otevřít instalátor</a>
        </section>
    <?php elseif ($game === null): ?>
        <section class="game-panel intro-panel">
            <p class="system-label">PŘÍCHOZÍ ZÁZNAM // ČAS NEZNÁMÝ</p>
            <h2>Signál ze tmy</h2>
            <p>Nejdřív slyšíš hluboké dunění. Potom pravidelné pípání. Otevřeš oči. Za sklem kryokomory bliká nouzové světlo a na panelu se opakuje jediné hlášení:</p>
            <blockquote>OBNOVA VĚDOMÍ DOKONČENA. IDENTITA PACIENTA NENALEZENA.</blockquote>
            <form id="start-game-form" class="start-form">
                <input type="hidden" name="_csrf" value="<?= h($csrf) ?>">
                <label for="player-name">Jméno postavy</label>
                <input id="player-name" name="name" maxlength="40" value="Přeživší" required>
                <button class="game-button" type="submit">Začít příběh</button>
            </form>
        </section>
    <?php else: ?>
        <section class="game-layout" id="game-app" data-csrf="<?= h($csrf) ?>">
            <aside class="status-panel">
                <p class="system-label">OSOBNÍ ZÁZNAM</p>
                <h2 id="player-name"><?= h($game['display_name']) ?></h2>
                <p>Kapitola <span id="chapter-number"><?= (int) $game['chapter'] ?></span></p>
                <div class="stat-row"><span>Zdraví</span><strong><?= (int) ($game['state']['player']['health'] ?? 100) ?> %</strong></div>
                <div class="stat-row"><span>Energie</span><strong><?= (int) ($game['state']['player']['energy'] ?? 70) ?> %</strong></div>
                <div class="stat-row"><span>Hlad</span><strong><?= (int) ($game['state']['player']['hunger'] ?? 10) ?> %</strong></div>
                <div class="stat-row"><span>Žízeň</span><strong><?= (int) ($game['state']['player']['thirst'] ?? 10) ?> %</strong></div>
                <p class="system-label location-label">AKTUÁLNÍ LOKACE</p>
                <p>Podzemní komplex<br><strong>Kryokomora</strong></p>
            </aside>
            <section class="game-panel story-panel">
                <p class="system-label">ZÁZNAM // PROBUZENÍ</p>
                <h2>Nouzový režim</h2>
                <p id="story-message" aria-live="polite">Nouzový systém obnovil napájení kryokomory. Probouzíš se do ticha.</p>
                <div class="action-list">
                    <button class="game-button action-button" data-command="inspect_room" <?= !empty($game['state']['flags']['room_inspected']) ? 'disabled' : '' ?>>Prozkoumat komoru</button>
                    <button class="game-button action-button" data-command="read_terminal" <?= !empty($game['state']['flags']['terminal_read']) ? 'disabled' : '' ?>>Přečíst nouzový terminál</button>
                    <button class="game-button action-button" data-command="listen_radio" <?= !empty($game['state']['flags']['radio_warning_heard']) ? 'disabled' : '' ?>>Poslouchat rádio</button>
                    <?php if (($game['state']['room'] ?? '') === 'cryo_chamber' && !empty($game['state']['flags']['room_inspected']) && !empty($game['state']['flags']['terminal_read'])): ?>
                        <button class="game-button action-button" data-command="open_door">Otevřít dveře do chodby</button>
                    <?php elseif (($game['state']['room'] ?? '') === 'maintenance_corridor'): ?>
                        <button class="game-button action-button" data-command="inspect_power_panel" <?= !empty($game['state']['flags']['power_panel_checked']) ? 'disabled' : '' ?>>Prozkoumat rozvaděč</button>
                    <?php endif; ?>
                </div>
                <div class="inventory-panel"><p class="system-label">INVENTÁŘ</p><ul id="inventory-list">
                    <?php foreach (($game['state']['inventory'] ?? []) as $item): ?>
                        <li><?= h((string) ($item['name'] ?? 'Předmět')) ?> × <?= (int) ($item['quantity'] ?? 1) ?></li>
                    <?php endforeach; ?>
                </ul></div>
                <div class="journal-panel">
                    <p class="system-label">OSOBNÍ DENÍK</p>
                    <ol id="journal-list">
                    <?php foreach (($game['state']['journal'] ?? []) as $entry): ?>
                        <li><?= h((string) ($entry['text'] ?? '')) ?></li>
                    <?php endforeach; ?>
                    </ol>
                </div>
            </section>
        </section>
    <?php endif; ?>
    <footer><span>POSLEDNÍ SIGNÁL</span><span>BUILD // 0.1.0</span></footer>
</main>
<script src="assets/js/game.js" defer></script>
</body>
</html>
