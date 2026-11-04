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
    || !str_contains($index, 'data-target-y')) {
    throw new RuntimeException('The game view is missing the player sprite or interactive scene hotspots.');
}

$script = file_get_contents($root . '/assets/js/game.js');
$styles = file_get_contents($root . '/assets/css/game.css');
if (!is_string($script) || !str_contains($script, 'scene-character') || !str_contains($script, 'button.dataset.targetX')
    || !is_string($styles) || !str_contains($styles, '.scene-hotspot') || !str_contains($styles, 'animation:survivor-walk')) {
    throw new RuntimeException('Scene movement or interaction styling is missing.');
}

echo "Scene checks passed (illustrations, survivor sprite, clickable hotspots and movement).\\n";
