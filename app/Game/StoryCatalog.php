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

    public static function chapterTitle(int $chapter): string
    {
        return [1 => 'Probuzení'][max(1, $chapter)] ?? 'Neznámý sektor';
    }

    private function __construct() {}
}
