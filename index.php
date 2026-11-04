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
    require_once BASE_PATH . '/app/Game/GameRules.php';
    require_once BASE_PATH . '/app/Game/StoryCatalog.php';
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
    <meta name="theme-color" content="#0b1117">
    <meta name="description" content="Sci-fi survival RPG POSLEDNÍ SIGNÁL">
    <title>POSLEDNÍ SIGNÁL</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/game.css">
</head>
<body>
<main class="game-shell">
    <header class="game-header">
        <div>
            <p class="eyebrow">ZÁZNAM SYSTÉMU // 0001</p>
            <h1>POSLEDNÍ SIGNÁL</h1>
            <p class="subtitle">Rok 2199 // Podzemní výzkumný komplex</p>
        </div>
        <div class="header-status"><span class="status-light"></span> NOUZOVÝ REŽIM</div>
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
        <section class="intro-layout">
            <div class="intro-scene">
                <div class="scene-visual cryo-art" data-scene="cryo" aria-label="Ilustrovaný pohled do kryokomory">
                    <img class="scene-art" src="assets/images/scenes/cryo-chamber.svg" alt="Kryokomora s řadou vysokých kryogenních modulů a nouzovým osvětlením">
                </div>
                <div class="scene-caption"><span>OBJEKT // PODZEMNÍ KOMPLEX</span><span>ČAS // NEZNÁMÝ</span></div>
            </div>
            <section class="game-panel intro-panel">
                <p class="system-label">PŘÍCHOZÍ ZÁZNAM // PROBUZENÍ</p>
                <h2>Signál ze tmy</h2>
                <p>Studený vzduch tě udeří do tváře. Za sklem kryokomory bliká nouzové světlo. Nevíš, kde jsi ani jak dlouho jsi spal. Na panelu se opakuje jediné hlášení:</p>
                <blockquote>OBNOVA VĚDOMÍ DOKONČENA. IDENTITA PACIENTA NENALEZENA.</blockquote>
                <form id="start-game-form" class="start-form">
                    <input type="hidden" name="_csrf" value="<?= h($csrf) ?>">
                    <label for="player-name">Jméno postavy</label>
                    <input id="player-name" name="name" maxlength="40" value="Přeživší" required>
                    <button class="game-button" type="submit">Začít příběh</button>
                </form>
            </section>
        </section>
    <?php else: ?>
        <?php
        $sceneTargets = [
            'inspect_room' => ['x' => 19, 'y' => 67, 'label' => 'Prozkoumat okolí'],
            'search_emergency_kit' => ['x' => 51, 'y' => 70, 'label' => 'Nouzový balíček'],
            'read_terminal' => ['x' => 77, 'y' => 62, 'label' => 'Terminál'],
            'listen_radio' => ['x' => 35, 'y' => 34, 'label' => 'Rádio'],
            'open_door' => ['x' => 88, 'y' => 47, 'label' => 'Dveře'],
            'drink_water' => ['x' => 47, 'y' => 82, 'label' => 'Napít se'],
            'eat_ration' => ['x' => 56, 'y' => 82, 'label' => 'Najíst se'],
            'inspect_power_panel' => ['x' => 82, 'y' => 63, 'label' => 'Rozvaděč'],
            'install_fuse' => ['x' => 82, 'y' => 63, 'label' => 'Instalovat pojistku'],
            'inspect_generator' => ['x' => 55, 'y' => 43, 'label' => 'Generátor'],
        ];
        ?>
        <div class="game-topline">
            <span>KAPITOLA <?= (int) $game['chapter'] ?> // <?= h($game['room']['sector']) ?></span>
            <span>ULOŽENÁ HRA // AUTOMATICKY</span>
        </div>
        <section class="game-layout" id="game-app" data-csrf="<?= h($csrf) ?>" data-player-x="<?= h((string) ($game['state']['player_position']['x'] ?? 50)) ?>" data-player-y="<?= h((string) ($game['state']['player_position']['y'] ?? 76)) ?>">
            <aside class="status-panel">
                <div class="identity-block">
                    <p class="system-label">OSOBNÍ ZÁZNAM</p>
                    <h2><?= h($game['display_name']) ?></h2>
                    <span class="identity-rank">PŘEŽIVŠÍ // ČLOVĚK</span>
                </div>
                <div class="stats-block">
                    <div class="stat-row"><span>Zdraví</span><strong><?= (int) ($game['state']['player']['health'] ?? 100) ?>%</strong></div>
                    <div class="stat-track"><i style="width:<?= max(0, min(100, (int) ($game['state']['player']['health'] ?? 100))) ?>%"></i></div>
                    <div class="stat-row"><span>Energie</span><strong><?= (int) ($game['state']['player']['energy'] ?? 70) ?>%</strong></div>
                    <div class="stat-track"><i style="width:<?= max(0, min(100, (int) ($game['state']['player']['energy'] ?? 70))) ?>%"></i></div>
                    <div class="stat-row"><span>Hlad</span><strong><?= (int) ($game['state']['player']['hunger'] ?? 10) ?>%</strong></div>
                    <div class="stat-track"><i style="width:<?= max(0, min(100, (int) ($game['state']['player']['hunger'] ?? 10))) ?>%"></i></div>
                    <div class="stat-row"><span>Žízeň</span><strong><?= (int) ($game['state']['player']['thirst'] ?? 10) ?>%</strong></div>
                    <div class="stat-track"><i style="width:<?= max(0, min(100, (int) ($game['state']['player']['thirst'] ?? 10))) ?>%"></i></div>
                </div>
                <div class="location-block">
                    <p class="system-label">AKTUÁLNÍ LOKACE</p>
                    <strong><?= h($game['room']['name']) ?></strong>
                    <span>Podzemní výzkumný komplex</span>
                </div>
                <?php if (!empty($game['state']['flags']['complex_map_unlocked'])): ?>
                    <div class="map-panel">
                        <p class="system-label">MAPA KOMPLEXU</p>
                        <div class="map-track">
                        <?php foreach ($game['map'] as $node): ?>
                            <div class="map-node <?= $node['known'] ? 'known' : 'unknown' ?> <?= ($game['state']['room'] ?? '') === $node['id'] ? 'current' : '' ?>">
                                <span class="map-dot"></span>
                                <div><strong><?= h($node['name']) ?></strong><small><?= h($node['status']) ?></small></div>
                            </div>
                        <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="inventory-panel">
                    <p class="system-label">INVENTÁŘ</p>
                    <?php if (empty($game['state']['inventory'])): ?>
                        <p class="empty-state">Zatím nic neneseš.</p>
                    <?php else: ?>
                        <ul>
                        <?php foreach ($game['state']['inventory'] as $item): ?>
                            <li><span><?= h((string) ($item['name'] ?? 'Předmět')) ?></span><b>× <?= (int) ($item['quantity'] ?? 1) ?></b></li>
                        <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </aside>

            <div class="main-column">
                <section class="scene-panel">
                    <div class="scene-heading">
                        <div><p class="system-label"><?= h($game['room']['sector']) ?></p><h2><?= h($game['room']['name']) ?></h2></div>
                        <span class="scene-coordinates">A-01 / <?= $game['room']['visual'] === 'cryo' ? '01' : '02' ?></span>
                    </div>
                    <div class="scene-visual <?= $game['room']['visual'] === 'cryo' ? 'cryo-art' : 'corridor-art' ?>" data-scene="<?= h($game['room']['visual']) ?>" aria-label="<?= h($game['room']['name']) ?>">
                        <img class="scene-art" src="assets/images/scenes/<?= $game['room']['visual'] === 'cryo' ? 'cryo-chamber' : 'maintenance-corridor' ?>.svg" alt="<?= h($game['room']['name']) ?> — ilustrované herní prostředí">
                        <div class="scene-character" id="scene-character" aria-label="Tvoje postava" style="left:<?= h((string) ($game['state']['player_position']['x'] ?? 50)) ?>%;top:<?= h((string) ($game['state']['player_position']['y'] ?? 76)) ?>%">
                            <img src="assets/images/characters/survivor.svg" alt="">
                        </div>
                        <?php foreach ($game['actions'] as $action): ?>
                            <?php $target = $sceneTargets[$action['id']] ?? ['x' => 50, 'y' => 55, 'label' => $action['label']]; ?>
                            <button type="button" class="scene-hotspot" data-command="<?= h($action['id']) ?>" data-target-x="<?= (int) $target['x'] ?>" data-target-y="<?= (int) $target['y'] ?>" style="--hotspot-x:<?= (int) $target['x'] ?>%;--hotspot-y:<?= (int) $target['y'] ?>%" aria-label="<?= h($target['label']) ?>">
                                <span class="hotspot-ring"></span><span class="hotspot-label"><?= h($target['label']) ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <div class="movement-panel" aria-label="Ovládání pohybu postavy">
                        <div class="movement-help"><strong>POHYB POSTAVY</strong><span>W A S D / šipky, tlačítka nebo klepnutí na podlahu</span></div>
                        <div class="movement-controls">
                            <button type="button" class="move-button move-up" data-move="0,-1" aria-label="Jít nahoru">▲</button>
                            <button type="button" class="move-button move-left" data-move="-1,0" aria-label="Jít doleva">◀</button>
                            <button type="button" class="move-button move-down" data-move="0,1" aria-label="Jít dolů">▼</button>
                            <button type="button" class="move-button move-right" data-move="1,0" aria-label="Jít doprava">▶</button>
                        </div>
                        <span class="movement-position" id="movement-position" aria-live="polite">Pozice: střed místnosti</span>
                    </div>
                    <div class="scene-footer"><span><i class="status-light"></i> PROSTŘEDÍ ZMAPOVÁNO ČÁSTEČNĚ</span><span><?= h($game['room']['sector']) ?></span></div>
                </section>

                <section class="game-panel story-panel">
                    <div class="story-title"><p class="system-label">PŘÍBĚH // <?= h($game['room']['title']) ?></p><h2><?= h($game['room']['title']) ?></h2></div>
                    <p class="scene-description"><?= h($game['room']['description']) ?></p>
                    <?php if (!empty($game['message'])): ?>
                        <div class="latest-message"><span>POSLEDNÍ UDÁLOST</span><p><?= h($game['message']) ?></p></div>
                    <?php endif; ?>
                    <?php if ((int) ($game['state']['player']['health'] ?? 100) <= 0): ?>
                        <div class="latest-message game-over-message">
                            <span>STAV // KONEC HRY</span>
                            <h3>Životní funkce zastaveny</h3>
                            <p>Postava zemřela. Tento průchod skončil. Uložený záznam zůstává zachován.</p>
                            <form id="start-game-form" class="start-form">
                                <input type="hidden" name="_csrf" value="<?= h($csrf) ?>">
                                <label for="player-name">Jméno nové postavy</label>
                                <input id="player-name" name="name" maxlength="40" value="Přeživší" required>
                                <button class="game-button" type="submit">Začít nový průchod</button>
                            </form>
                        </div>
                    <?php endif; ?>
                    <div class="action-section">
                        <p class="system-label"><?= (int) ($game['state']['player']['health'] ?? 100) <= 0 ? 'KONEC HRY' : 'DOSTUPNÉ AKCE' ?></p>
                        <div class="action-list">
                            <?php foreach ($game['actions'] as $action): ?>
                                <?php $target = $sceneTargets[$action['id']] ?? ['x' => 50, 'y' => 55]; ?>
                                <button class="game-button action-button" data-command="<?= h($action['id']) ?>" data-target-x="<?= (int) $target['x'] ?>" data-target-y="<?= (int) $target['y'] ?>"><?= h($action['label']) ?><span>→</span></button>
                            <?php endforeach; ?>
                            <?php if (empty($game['actions'])): ?>
                                <p class="empty-state">V této místnosti už nejsou dostupné žádné nové akce.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="journal-panel">
                        <p class="system-label">OSOBNÍ DENÍK // ZÁZNAMY <?= count($game['state']['journal'] ?? []) ?></p>
                        <ol>
                        <?php foreach (array_reverse($game['state']['journal'] ?? []) as $entry): ?>
                            <li><?= h((string) ($entry['text'] ?? '')) ?></li>
                        <?php endforeach; ?>
                        </ol>
                    </div>
                </section>
            </div>
        </section>
    <?php endif; ?>
    <footer><span>POSLEDNÍ SIGNÁL</span><span>BUILD // 0.2.0</span></footer>
</main>
<script src="assets/js/game.js" defer></script>
</body>
</html>
