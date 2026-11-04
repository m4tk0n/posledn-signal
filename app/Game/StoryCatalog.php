<?php
declare(strict_types=1);

namespace App\Game;

final class StoryCatalog
{
    private const ROOMS = [
        'cryo_chamber' => [
            'name' => 'Kryokomora',
            'sector' => 'SEKTOR A // MEDICÍNA',
            'title' => 'Probuzení',
            'description' => 'Studený vzduch se drží při zemi. Řada kryokomor mizí ve tmě a jediná funkční kontrolka bliká nad tvou hlavou. Dveře na východ blokuje elektromagnetický zámek.',
            'visual' => 'cryo',
        ],
        'maintenance_corridor' => [
            'name' => 'Servisní chodba',
            'sector' => 'SEKTOR A // TECHNICKÉ ZÁZEMÍ',
            'title' => 'Nouzové napájení',
            'description' => 'Úzká chodba je plná kabelových žlabů a servisních panelů. Nouzová světla sotva prořezávají tmu. Na konci chodby stojí generátor a vedle něj bliká rozvaděč.',
            'visual' => 'corridor',
        ],
    ];

    public static function room(string $id): array
    {
        return self::ROOMS[$id] ?? self::ROOMS['cryo_chamber'];
    }

    public static function map(array $state): array
    {
        $flags = $state['flags'] ?? [];
        return [
            ['id' => 'cryo_chamber', 'name' => 'Kryokomora', 'status' => 'prozkoumáno', 'known' => true],
            ['id' => 'maintenance_corridor', 'name' => 'Servisní chodba', 'status' => !empty($flags['door_opened']) ? 'přístupná' : 'uzamčeno', 'known' => !empty($flags['door_opened'])],
            ['id' => 'generator', 'name' => 'Nouzový generátor', 'status' => !empty($flags['generator_inspected']) ? 'prozkoumáno' : (!empty($flags['fuse_installed']) ? 'přístupný' : 'bez napájení'), 'known' => !empty($flags['fuse_installed'])],
            ['id' => 'north_sector', 'name' => 'Severní sektor', 'status' => 'neznámá oblast', 'known' => false],
        ];
    }

    private function __construct() {}
}
