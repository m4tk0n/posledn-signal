<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Game/GameRules.php';
require_once dirname(__DIR__) . '/app/Game/StoryCatalog.php';

use App\Game\GameRules;
use App\Game\StoryCatalog;

function expect(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function expectFailure(callable $callback, string $message): void
{
    try {
        $callback();
    } catch (RuntimeException) {
        return;
    }
    throw new RuntimeException($message);
}

$state = GameRules::initialState();
expect($state['room'] === 'cryo_chamber', 'New game must start in the cryo chamber.');
expect(count(GameRules::availableActions($state)) === 3, 'Cryo chamber must initially offer three actions.');
expect(StoryCatalog::room('cryo_chamber')['name'] === 'Kryokomora', 'Cryo room metadata is missing.');

expectFailure(
    static fn() => GameRules::apply($state, 'open_door'),
    'Door must not open before the chamber is inspected and terminal read.'
);

$state = GameRules::apply($state, 'inspect_room')['state'];
expect(($state['flags']['room_inspected'] ?? false) === true, 'Room inspection flag was not saved.');
expect(($state['inventory'][0]['id'] ?? '') === 'scrap_metal', 'Room inspection must add scrap metal.');
expectFailure(
    static fn() => GameRules::apply($state, 'inspect_room'),
    'A one-time action must not be repeatable.'
);

$state = GameRules::apply($state, 'read_terminal')['state'];
$state = GameRules::apply($state, 'listen_radio')['state'];
expect(($state['story_stage'] ?? '') === 'signal_received', 'Radio action must advance the story stage.');
$state = GameRules::apply($state, 'open_door')['state'];
expect(($state['room'] ?? '') === 'maintenance_corridor', 'Opening the door must move the player to the corridor.');

$state = GameRules::apply($state, 'inspect_power_panel')['state'];
expect(($state['inventory'][1]['id'] ?? '') === 'spare_fuse', 'Power panel must add the spare fuse.');
$available = array_column(GameRules::availableActions($state), 'id');
expect(in_array('install_fuse', $available, true), 'Installing the fuse must become available after inspection.');
$state = GameRules::apply($state, 'install_fuse')['state'];
expect(($state['story_stage'] ?? '') === 'emergency_power_restored', 'Installing the fuse must restore emergency power.');
expect(count($state['inventory']) === 1, 'The installed fuse must be removed from inventory.');
expect(($state['inventory'][0]['id'] ?? '') === 'scrap_metal', 'Unrelated inventory items must be preserved.');

$state = GameRules::apply($state, 'inspect_generator')['state'];
expect(($state['flags']['complex_map_unlocked'] ?? false) === true, 'Generator inspection must unlock the complex map.');
expect(($state['story_stage'] ?? '') === 'complex_map_unlocked', 'Generator inspection must advance the story.');
expectFailure(
    static fn() => GameRules::apply($state, 'install_fuse'),
    'An already installed fuse must not be installable twice.'
);

echo "Game rules checks passed (new game, prerequisites, inventory, room transition, power restoration, map unlock, duplicate prevention).\n";
