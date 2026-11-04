<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$scenes = [
    'cryo-chamber.svg' => 'Kryokomora výzkumného komplexu',
    'maintenance-corridor.svg' => 'Servisní chodba výzkumného komplexu',
];

foreach ($scenes as $file => $title) {
    $path = $root . '/assets/images/scenes/' . $file;
    if (!is_file($path)) {
        throw new RuntimeException('Missing scene asset: ' . $file);
    }

    $svg = file_get_contents($path);
    if (!is_string($svg) || !str_contains($svg, '<svg ') || !str_contains($svg, '<title') || !str_contains($svg, $title)) {
        throw new RuntimeException('Invalid or incomplete SVG scene asset: ' . $file);
    }
}

$index = file_get_contents($root . '/index.php');
if (!is_string($index)
    || !str_contains($index, 'cryo-chamber')
    || !str_contains($index, 'maintenance-corridor')
    || !str_contains($index, 'class="scene-art"')) {
    throw new RuntimeException('The game view does not render all illustrated scenes.');
}

$characterPath = $root . '/assets/images/characters/survivor.svg';
$character = is_file($characterPath) ? file_get_contents($characterPath) : false;
if (!is_string($character) || !str_contains($character, '<svg ') || !str_contains($character, 'Postava přeživšího')) {
    throw new RuntimeException('The survivor character sprite is missing or invalid.');
}

if (!str_contains($index, 'class="scene-character"')
    || !str_contains($index, 'class="scene-hotspot"')
    || !str_contains($index, 'data-target-x')
    || !str_contains($index, 'data-target-y')
    || !str_contains($index, 'data-move')
    || !str_contains($index, 'movement-controls')) {
    throw new RuntimeException('The game view is missing the player sprite or interactive scene hotspots.');
}

$script = file_get_contents($root . '/assets/js/game.js');
$styles = file_get_contents($root . '/assets/css/game.css');
if (!is_string($script) || !str_contains($script, 'scene-character') || !str_contains($script, 'button.dataset.targetX') || !str_contains($script, 'function movePlayer') || !str_contains($script, 'arrowup')
    || !str_contains($script, 'function isBlocked')
    || !str_contains($script, 'INTERACTION_RANGE')
    || !str_contains($script, 'moveToScenePoint')
    || !is_string($styles) || !str_contains($styles, '.scene-hotspot') || !str_contains($styles, 'animation:survivor-walk') || !str_contains($styles, '.movement-controls')
    || !str_contains($styles, '.movement-notice')) {
    throw new RuntimeException('Scene movement or interaction styling is missing.');
}

$rules = file_get_contents($root . '/app/Game/GameRules.php');
$service = file_get_contents($root . '/app/Game/GameService.php');
$api = file_get_contents($root . '/api/game.php');
$view = file_get_contents($root . '/index.php');
if (!is_string($rules) || !str_contains($rules, 'validatePosition') || !str_contains($rules, 'validateInteractionPosition')
    || !is_string($service) || !str_contains($service, 'savePosition') || !str_contains($service, 'validateInteractionPosition')
    || !is_string($api) || !str_contains($api, 'save_position')
    || !is_string($view) || !str_contains($view, 'data-player-x') || !str_contains($view, 'player_position')) {
    throw new RuntimeException('Server-side player position persistence or interaction validation is missing.');
}

echo "Scene checks passed (illustrations, survivor sprite, clickable hotspots and movement).\\n";
