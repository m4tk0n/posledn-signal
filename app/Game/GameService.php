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
        $nameLength = preg_match_all('/./us', $name);
        if ($name === '' || $nameLength === false || $nameLength > 40) {
            throw new RuntimeException('Jméno postavy musí mít 1 až 40 znaků.');
        }
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $q = $pdo->prepare('INSERT INTO ' . Database::table('players') . ' (display_name) VALUES (:name)');
            $q->execute(['name' => $name]);
            $playerId = (int) $pdo->lastInsertId();
            $state = GameRules::initialState();
            $q = $pdo->prepare('INSERT INTO ' . Database::table('game_saves') . ' (player_id, save_name, chapter, game_state) VALUES (:player, :name, 1, :state)');
            $q->execute([
                'player' => $playerId,
                'name' => 'První probuzení',
                'state' => json_encode($state, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            ]);
            $saveId = (int) $pdo->lastInsertId();
            self::event($pdo, $saveId, 'game_started', ['location' => $state['location']]);
            $pdo->commit();
            return self::presentGame($playerId, $saveId, $name, 1, $state, 'Nouzový systém obnovil napájení kryokomory. Probouzíš se do ticha.');
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
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
        if (!$row) {
            throw new RuntimeException('Uložená hra nebyla nalezena.');
        }
        $state = json_decode((string) $row['game_state'], true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($state)) {
            throw new RuntimeException('Uložený stav hry je poškozený.');
        }
        return self::presentGame($playerId, (int) $row['id'], (string) $row['display_name'], (int) $row['chapter'], $state);
    }


    public static function savePosition(int $playerId, int $saveId, float $x, float $y): array
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $q = $pdo->prepare('SELECT game_state, chapter FROM ' . Database::table('game_saves') . ' WHERE id = :save AND player_id = :player FOR UPDATE');
            $q->execute(['save' => $saveId, 'player' => $playerId]);
            $row = $q->fetch();
            if (!$row) {
                throw new RuntimeException('Uložená hra nebyla nalezena.');
            }
            $state = json_decode((string) $row['game_state'], true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($state)) {
                throw new RuntimeException('Uložený stav hry je poškozený.');
            }
            $state['player_position'] = GameRules::validatePosition((string) ($state['room'] ?? 'cryo_chamber'), $x, $y);
            $q = $pdo->prepare('UPDATE ' . Database::table('game_saves') . ' SET game_state = :state WHERE id = :save AND player_id = :player');
            $q->execute(['state' => json_encode($state, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE), 'save' => $saveId, 'player' => $playerId]);
            $pdo->commit();
            return self::presentGame($playerId, $saveId, '', (int) ($row['chapter'] ?? 1), $state);
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public static function performAction(int $playerId, int $saveId, string $action): array
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $q = $pdo->prepare(
                'SELECT game_state, chapter FROM ' . Database::table('game_saves') .
                ' WHERE id = :save AND player_id = :player FOR UPDATE'
            );
            $q->execute(['save' => $saveId, 'player' => $playerId]);
            $row = $q->fetch();
            if (!$row) {
                throw new RuntimeException('Uložená hra nebyla nalezena.');
            }
            $state = json_decode((string) $row['game_state'], true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($state)) {
                throw new RuntimeException('Uložený stav hry je poškozený.');
            }

            GameRules::validateInteractionPosition($state, $action);
            $result = GameRules::apply($state, $action);
            $state = $result['state'];
            $q = $pdo->prepare(
                'UPDATE ' . Database::table('game_saves') .
                ' SET game_state = :state, chapter = :chapter WHERE id = :save AND player_id = :player'
            );
            $q->execute([
                'state' => json_encode($state, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                'chapter' => (int) ($state['chapter'] ?? 1),
                'save' => $saveId,
                'player' => $playerId,
            ]);
            self::event($pdo, $saveId, $action, ['message' => $result['message']]);
            $pdo->commit();

            return self::presentGame($playerId, $saveId, '', (int) ($state['chapter'] ?? 1), $state, $result['message']);
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    private static function presentGame(
        int $playerId,
        int $saveId,
        string $displayName,
        int $chapter,
        array $state,
        ?string $message = null
    ): array {
        $roomId = (string) ($state['room'] ?? 'cryo_chamber');
        return [
            'player_id' => $playerId,
            'save_id' => $saveId,
            'display_name' => $displayName,
            'chapter' => $chapter,
            'state' => $state,
            'room' => StoryCatalog::room($roomId),
            'actions' => GameRules::availableActions($state),
            'map' => StoryCatalog::map($state),
            'message' => $message,
        ];
    }

    private static function event(PDO $pdo, int $saveId, string $type, array $data): void
    {
        $q = $pdo->prepare(
            'INSERT INTO ' . Database::table('game_events') .
            ' (save_id, event_type, event_data) VALUES (:save, :type, :data)'
        );
        $q->execute([
            'save' => $saveId,
            'type' => $type,
            'data' => json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
        ]);
    }

    private function __construct() {}
}
