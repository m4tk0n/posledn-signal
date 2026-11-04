<?php
declare(strict_types=1);

namespace App\Game;

use RuntimeException;

final class GameRules
{
    private const ACTIONS = [
        'inspect_room' => [
            'flag' => 'room_inspected',
            'room' => 'cryo_chamber',
            'message' => 'Prozkoumáš kryokomoru. Pod převráceným vozíkem najdeš kovovou schránku a z podlahy sebereš několik použitelných součástek.',
        ],
        'read_terminal' => [
            'flag' => 'terminal_read',
            'room' => 'cryo_chamber',
            'message' => 'Terminál se po několika pokusech probudí. Poslední záznam: EVAKUACE ZRUŠENA. SEKTOR SEVER UZAVŘÍT. Čas záznamu je 73 let starý.',
        ],
        'listen_radio' => [
            'flag' => 'radio_warning_heard',
            'room' => 'cryo_chamber',
            'message' => 'Z reproduktoru zapraská cizí hlas: „Pokud mě někdo slyší… nechoďte na sever. Oni stále žijí.“ Signál se okamžitě ztratí.',
        ],
        'open_door' => [
            'flag' => 'door_opened',
            'room' => 'cryo_chamber',
            'requires' => ['room_inspected', 'terminal_read'],
            'message' => 'Terminál uvolní elektromagnetický zámek. Těžké dveře se pomalu otevřou do servisní chodby.',
        ],
        'inspect_power_panel' => [
            'flag' => 'power_panel_checked',
            'room' => 'maintenance_corridor',
            'message' => 'Otevřeš kryt rozvaděče. Uvnitř je náhradní pojistka a několik kabelů, které někdo úmyslně přerušil.',
        ],
        'install_fuse' => [
            'flag' => 'fuse_installed',
            'room' => 'maintenance_corridor',
            'requires' => ['power_panel_checked'],
            'item' => 'spare_fuse',
            'message' => 'Zasunuješ náhradní pojistku do rozvaděče. Nouzové osvětlení chodby se rozsvítí a ze vzdálené části komplexu se ozve rozběhnutý mechanismus.',
        ],
        'inspect_generator' => [
            'flag' => 'generator_inspected',
            'room' => 'maintenance_corridor',
            'requires' => ['fuse_installed'],
            'message' => 'Nouzový generátor běží, ale jeho výkon kolísá. Na panelu se objeví mapa komplexu a označení dalších uzavřených sektorů.',
        ],
    ];

    public static function initialState(): array
    {
        return [
            'schema_version' => 1,
            'chapter' => 1,
            'location' => 'underground_complex',
            'room' => 'cryo_chamber',
            'player' => ['health' => 100, 'energy' => 70, 'hunger' => 10, 'thirst' => 10],
            'inventory' => [],
            'flags' => [],
            'journal' => [],
            'story_stage' => 'awakening',
        ];
    }

    public static function availableActions(array $state): array
    {
        $available = [];
        foreach (self::ACTIONS as $id => $definition) {
            if (($state['room'] ?? '') !== $definition['room'] || !empty($state['flags'][$definition['flag']])) {
                continue;
            }
            $allowed = true;
            foreach ($definition['requires'] ?? [] as $requiredFlag) {
                if (empty($state['flags'][$requiredFlag])) {
                    $allowed = false;
                    break;
                }
            }
            if (isset($definition['item']) && !self::hasItem($state, $definition['item'])) {
                $allowed = false;
            }
            if ($allowed) {
                $available[] = ['id' => $id, 'label' => self::actionLabel($id)];
            }
        }
        return $available;
    }

    public static function apply(array $state, string $action): array
    {
        if (!isset(self::ACTIONS[$action])) {
            throw new RuntimeException('Neznámá herní akce.');
        }
        $definition = self::ACTIONS[$action];
        if (($state['room'] ?? '') !== $definition['room']) {
            throw new RuntimeException('Tuto akci v aktuální místnosti nelze provést.');
        }
        if (!empty($state['flags'][$definition['flag']])) {
            throw new RuntimeException('Tuto část už jsi prozkoumal.');
        }
        foreach ($definition['requires'] ?? [] as $requiredFlag) {
            if (empty($state['flags'][$requiredFlag])) {
                throw new RuntimeException('Nejdříve musíš splnit předchozí úkol.');
            }
        }
        if (isset($definition['item']) && !self::hasItem($state, $definition['item'])) {
            throw new RuntimeException('V inventáři nemáš potřebný předmět.');
        }

        $state['flags'][$definition['flag']] = true;
        $state['journal'][] = [
            'type' => $action,
            'text' => $definition['message'],
            'at' => gmdate('c'),
        ];

        if ($action === 'inspect_room') {
            self::addItem($state, 'scrap_metal', 'Kovové součástky', 2);
        } elseif ($action === 'open_door') {
            $state['room'] = 'maintenance_corridor';
            $state['story_stage'] = 'entered_corridor';
        } elseif ($action === 'inspect_power_panel') {
            self::addItem($state, 'spare_fuse', 'Náhradní pojistka', 1);
        } elseif ($action === 'install_fuse') {
            self::removeItem($state, 'spare_fuse', 1);
            $state['story_stage'] = 'emergency_power_restored';
        } elseif ($action === 'listen_radio') {
            $state['story_stage'] = 'signal_received';
        } elseif ($action === 'inspect_generator') {
            $state['story_stage'] = 'complex_map_unlocked';
            $state['flags']['complex_map_unlocked'] = true;
        }

        return ['state' => $state, 'message' => $definition['message']];
    }

    public static function actionLabel(string $id): string
    {
        return [
            'inspect_room' => 'Prozkoumat kryokomoru',
            'read_terminal' => 'Přečíst nouzový terminál',
            'listen_radio' => 'Poslouchat rádiové vysílání',
            'open_door' => 'Otevřít dveře do chodby',
            'inspect_power_panel' => 'Prozkoumat rozvaděč',
            'install_fuse' => 'Instalovat náhradní pojistku',
            'inspect_generator' => 'Prozkoumat nouzový generátor',
        ][$id] ?? $id;
    }

    private static function hasItem(array $state, string $id): bool
    {
        foreach ($state['inventory'] ?? [] as $item) {
            if (($item['id'] ?? '') === $id && (int) ($item['quantity'] ?? 0) > 0) {
                return true;
            }
        }
        return false;
    }

    private static function addItem(array &$state, string $id, string $name, int $quantity): void
    {
        foreach ($state['inventory'] as &$item) {
            if (($item['id'] ?? '') === $id) {
                $item['quantity'] = (int) ($item['quantity'] ?? 0) + $quantity;
                unset($item);
                return;
            }
        }
        unset($item);
        $state['inventory'][] = ['id' => $id, 'name' => $name, 'quantity' => $quantity];
    }

    private static function removeItem(array &$state, string $id, int $quantity): void
    {
        foreach ($state['inventory'] as $index => $item) {
            if (($item['id'] ?? '') !== $id) {
                continue;
            }
            $remaining = (int) ($item['quantity'] ?? 0) - $quantity;
            if ($remaining > 0) {
                $state['inventory'][$index]['quantity'] = $remaining;
            } else {
                unset($state['inventory'][$index]);
                $state['inventory'] = array_values($state['inventory']);
            }
            return;
        }
    }

    private function __construct() {}
}
