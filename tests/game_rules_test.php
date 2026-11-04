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
expect(count(GameRules::availableActions($state)) === 4, 'Cryo chamber must initially offer four actions.');
expect(StoryCatalog::room('cryo_chamber')['name'] === 'Kryokomora', 'Cryo room metadata is missing.');
$initialMap = StoryCatalog::map($state);
expect(count($initialMap) === 4, 'The initial complex map must contain four locations.');
expect($initialMap[1]['known'] === false && $initialMap[1]['status'] === 'uzamčeno', 'The corridor must initially be locked.');

expectFailure(
    static fn() => GameRules::apply($state, 'open_door'),
    'Door must not open before the chamber is inspected and terminal read.'
);

$survival = GameRules::initialState();
$survivalResult = GameRules::apply($survival, 'search_emergency_kit');
$survival = $survivalResult['state'];
expect(($survival['player']['hunger'] ?? -1) === 13, 'A successful action must increase hunger by three.');
expect(($survival['player']['thirst'] ?? -1) === 14, 'A successful action must increase thirst by four.');
expect(($survival['player']['energy'] ?? -1) === 68, 'A successful action must reduce energy by two.');
expect(count($survival['inventory']) === 2, 'The emergency kit must contain water and food.');
$survival = GameRules::apply($survival, 'drink_water')['state'];
expect(($survival['player']['thirst'] ?? -1) === 4, 'Drinking must reduce thirst before the action time advances.');
expect(($survival['inventory'][0]['quantity'] ?? 0) === 1, 'Drinking must consume exactly one bottle.');
$survival = GameRules::apply($survival, 'eat_ration')['state'];
expect(($survival['player']['hunger'] ?? -1) === 3, 'Eating must reduce hunger before the action time advances.');
expectFailure(
    static fn() => GameRules::apply($survival, 'eat_ration'),
    'Eating without a remaining ration must be rejected.'
);
$critical = GameRules::initialState();
$critical['player']['thirst'] = 77;
$critical['player']['health'] = 100;
$critical = GameRules::apply($critical, 'search_emergency_kit')['state'];
expect(($critical['player']['health'] ?? 0) === 92, 'Critical thirst must cause health damage after a successful action.');
$unchanged = GameRules::initialState();
expectFailure(
    static fn() => GameRules::apply($unchanged, 'drink_water'),
    'An unavailable survival action must fail.'
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
$map = StoryCatalog::map($state);
expect($map[1]['known'] === true && $map[1]['status'] === 'přístupná', 'The corridor must be discovered after opening the door.');
expect($map[2]['known'] === true && $map[2]['status'] === 'prozkoumáno', 'The generator must be marked explored after inspection.');
expect($map[3]['known'] === false, 'The northern sector must remain undiscovered.');
expectFailure(
    static fn() => GameRules::apply($state, 'install_fuse'),
    'An already installed fuse must not be installable twice.'
);

echo "Game rules checks passed (new game, prerequisites, inventory, room transition, power restoration, map unlock, duplicate prevention, hunger, thirst, energy, food and water consumption).\n";
