<?php
declare(strict_types=1);
namespace App\Game;

use App\Core\Database;
use PDO;
use RuntimeException;

final class GameService
{
    public static function isInstalled(): bool
    {
        try {
            $q = Database::connection()->prepare('SELECT 1 FROM ' . Database::table('schema_migrations') . ' WHERE version = :v LIMIT 1');
            $q->execute(['v' => '0001_initial']);
            return $q->fetchColumn() !== false;
        } catch (\Throwable) {
            return false;
        }
    }

    public static function createNewGame(string $name): array
    {
        $name = trim($name);
        if ($name === '' || mb_strlen($name) > 40) {
            throw new RuntimeException('Jméno postavy musí mít 1 až 40 znaků.');
        }
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $q = $pdo->prepare('INSERT INTO ' . Database::table('players') . ' (display_name) VALUES (:name)');
            $q->execute(['name' => $name]);
            $playerId = (int) $pdo->lastInsertId();
            $state = [
                'schema_version' => 1, 'chapter' => 1,
                'location' => 'underground_complex', 'room' => 'cryo_chamber',
                'player' => ['health' => 100, 'energy' => 70, 'hunger' => 10, 'thirst' => 10],
                'inventory' => [], 'flags' => [], 'journal' => [],
                'story_stage' => 'awakening',
            ];
            $q = $pdo->prepare('INSERT INTO ' . Database::table('game_saves') . ' (player_id, save_name, chapter, game_state) VALUES (:player, :name, 1, :state)');
            $q->execute(['player' => $playerId, 'name' => 'První probuzení', 'state' => json_encode($state, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)]);
            $saveId = (int) $pdo->lastInsertId();
            self::event($pdo, $saveId, 'game_started', ['location' => 'underground_complex']);
            $pdo->commit();
            return ['player_id' => $playerId, 'save_id' => $saveId, 'display_name' => $name, 'state' => $state,
                'message' => 'Nouzový systém obnovil napájení kryokomory. Probouzíš se do ticha.'];
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    public static function loadGame(int $playerId, int $saveId): array
    {
        $q = Database::connection()->prepare(
            'SELECT s.id, s.chapter, s.game_state, p.display_name FROM ' . Database::table('game_saves') .
            ' s INNER JOIN ' . Database::table('players') . ' p ON p.id = s.player_id WHERE s.id = :save AND s.player_id = :player LIMIT 1'
        );
        $q->execute(['save' => $saveId, 'player' => $playerId]);
        $row = $q->fetch();
        if (!$row) throw new RuntimeException('Uložená hra nebyla nalezena.');
        $state = json_decode((string) $row['game_state'], true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($state)) throw new RuntimeException('Uložený stav hry je poškozený.');
        return ['player_id' => $playerId, 'save_id' => (int) $row['id'], 'display_name' => $row['display_name'], 'chapter' => (int) $row['chapter'], 'state' => $state];
    }

    public static function performAction(int $playerId, int $saveId, string $action): array
    {
        $messages = [
            'inspect_room' => 'Rozhlédneš se po komoře. Na podlaze leží prázdná injekční ampule a vedle dveří bliká nouzové světlo.',
            'read_terminal' => 'Terminál ožije až na třetí pokus. Poslední záznam končí před 73 lety: EVAKUACE ZRUŠENA. SEKTOR SEVER UZAVŘÍT.',
            'listen_radio' => 'Z reproduktoru zapraská hlas. „Pokud mě někdo slyší… nechoďte na sever. Oni stále žijí.“ Signál se okamžitě ztratí.',
            'open_door' => 'Terminál uvolní elektromagnetický zámek. Těžké dveře se s kovovým skřípěním otevřou do úzké servisní chodby.',
            'inspect_power_panel' => 'V rozvaděči najdeš jednu nepoškozenou náhradní pojistku. Opatrně ji uložíš do inventáře.',
        ];
        if (!isset($messages[$action])) throw new RuntimeException('Neznámá herní akce.');
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $q = $pdo->prepare('SELECT game_state FROM ' . Database::table('game_saves') . ' WHERE id = :save AND player_id = :player FOR UPDATE');
            $q->execute(['save' => $saveId, 'player' => $playerId]);
            $raw = $q->fetchColumn();
            if ($raw === false) throw new RuntimeException('Uložená hra nebyla nalezena.');
            $state = json_decode((string) $raw, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($state)) throw new RuntimeException('Uložený stav hry je poškozený.');
            if ($action === 'open_door' && (empty($state['flags']['room_inspected']) || empty($state['flags']['terminal_read']))) {
                throw new RuntimeException('Nejdříve prozkoumej komoru a přečti nouzový terminál.');
            }
            if ($action === 'inspect_power_panel' && ($state['room'] ?? '') !== 'maintenance_corridor') {
                throw new RuntimeException('Rozvaděč se v této místnosti nenachází.');
            }
            $flag = [
                'inspect_room' => 'room_inspected',
                'read_terminal' => 'terminal_read',
                'listen_radio' => 'radio_warning_heard',
                'open_door' => 'door_opened',
                'inspect_power_panel' => 'power_panel_checked',
            ][$action];
            if (!empty($state['flags'][$flag])) throw new RuntimeException('Tuto část už jsi prozkoumal.');
            $state['flags'][$flag] = true;
            $state['journal'][] = ['type' => $action, 'text' => $messages[$action], 'at' => gmdate('c')];
            if ($action === 'listen_radio') $state['story_stage'] = 'signal_received';
            if ($action === 'open_door') {
                $state['room'] = 'maintenance_corridor';
                $state['story_stage'] = 'entered_corridor';
            }
            if ($action === 'inspect_power_panel') {
                $state['inventory'][] = ['id' => 'spare_fuse', 'name' => 'Náhradní pojistka', 'quantity' => 1];
            }
            $q = $pdo->prepare('UPDATE ' . Database::table('game_saves') . ' SET game_state = :state, chapter = :chapter WHERE id = :save AND player_id = :player');
            $q->execute(['state' => json_encode($state, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE), 'chapter' => (int) ($state['chapter'] ?? 1), 'save' => $saveId, 'player' => $playerId]);
            self::event($pdo, $saveId, $action, ['message' => $messages[$action]]);
            $pdo->commit();
            return ['state' => $state, 'message' => $messages[$action]];
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    private static function event(PDO $pdo, int $saveId, string $type, array $data): void
    {
        $q = $pdo->prepare('INSERT INTO ' . Database::table('game_events') . ' (save_id, event_type, event_data) VALUES (:save, :type, :data)');
        $q->execute(['save' => $saveId, 'type' => $type, 'data' => json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)]);
    }
    private function __construct() {}
}
