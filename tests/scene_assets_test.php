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

echo "Scene asset checks passed (cryo chamber, maintenance corridor, game view references).\n";
