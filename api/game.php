<?php
declare(strict_types=1);

use App\Game\GameService;
use App\Security\Session;

require_once dirname(__DIR__) . '/app/bootstrap.php';
require_once BASE_PATH . '/app/Game/GameService.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

function respond(int $status, array $data): never
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    respond(405, ['ok' => false, 'message' => 'Nepovolená metoda.']);
}

if (!Session::verifyCsrf(isset($_POST['_csrf']) && is_string($_POST['_csrf']) ? $_POST['_csrf'] : null)) {
    respond(403, ['ok' => false, 'message' => 'Platnost formuláře vypršela. Obnov stránku.']);
}

try {
    $action = $_POST['action'] ?? '';
    if ($action === 'start') {
        if (!GameService::isInstalled()) {
            respond(409, ['ok' => false, 'message' => 'Databáze není nainstalována. Nejprve spusť instalátor.']);
        }
        $result = GameService::createNewGame(is_string($_POST['name'] ?? null) ? $_POST['name'] : 'Přeživší');
        $_SESSION['player_id'] = $result['player_id'];
        $_SESSION['active_save_id'] = $result['save_id'];
        respond(200, ['ok' => true, 'game' => $result]);
    }

    $playerId = (int) ($_SESSION['player_id'] ?? 0);
    $saveId = (int) ($_SESSION['active_save_id'] ?? 0);
    if ($playerId < 1 || $saveId < 1) {
        respond(401, ['ok' => false, 'message' => 'Aktivní hra nebyla nalezena.']);
    }

    if ($action === 'interact') {
        $command = $_POST['command'] ?? '';
        if (!is_string($command)) {
            respond(422, ['ok' => false, 'message' => 'Neplatná herní akce.']);
        }
        $result = GameService::performAction($playerId, $saveId, $command);
        respond(200, ['ok' => true, 'game' => $result]);
    }

    respond(422, ['ok' => false, 'message' => 'Neznámý požadavek.']);
} catch (RuntimeException $exception) {
    respond(422, ['ok' => false, 'message' => $exception->getMessage()]);
} catch (Throwable $exception) {
    error_log('Game API failed: ' . $exception->getMessage());
    respond(500, ['ok' => false, 'message' => 'Herní systém je dočasně nedostupný.']);
}
