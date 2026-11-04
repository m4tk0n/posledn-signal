<?php
declare(strict_types=1);

use App\Core\Database;
use App\Core\Env;
use App\Security\Session;

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/app/Core/Env.php';
require_once BASE_PATH . '/app/Core/Database.php';
require_once BASE_PATH . '/app/Security/Session.php';

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store');

$error = '';
$success = false;

try {
    Env::load(BASE_PATH . '/.env');
    date_default_timezone_set((string) Env::get('APP_TIMEZONE', 'Europe/Prague'));
    Session::start();

    $prefix = Env::get('DB_PREFIX');
    $appKey = Env::get('APP_KEY');
    if ($prefix === null || !preg_match('/^[a-zA-Z][a-zA-Z0-9_]{0,30}_$/', $prefix)) {
        throw new RuntimeException('DB_PREFIX chybí nebo nemá bezpečný formát (např. posledni_signal_).');
    }
    if ($appKey === null || strlen($appKey) < 32 || str_contains($appKey, 'CHANGE_ME')) {
        throw new RuntimeException('Nejdříve nastavte v .env unikátní APP_KEY o délce alespoň 32 znaků.');
    }

    $pdo = Database::connection();
    $lockFile = BASE_PATH . '/storage/installed.lock';
    $migrationTable = $prefix . 'schema_migrations';
    $installed = is_file($lockFile);
    try {
        $check = $pdo->query('SELECT version FROM `' . $migrationTable . '` WHERE version = ' . $pdo->quote('0001_initial'));
        $installed = $installed || $check->fetchColumn() !== false;
    } catch (Throwable) {
        // A missing migration table means that installation has not completed.
    }

    if ($installed) {
        http_response_code(403);
        throw new RuntimeException('Aplikace již byla nainstalována. Instalační skript je zablokován.');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['_csrf'] ?? null;
        $providedKey = $_POST['install_key'] ?? '';
        if (!Session::verifyCsrf(is_string($token) ? $token : null)) {
            throw new RuntimeException('Platnost formuláře vypršela. Obnovte stránku.');
        }
        if (!is_string($providedKey) || !hash_equals($appKey, $providedKey)) {
            throw new RuntimeException('Instalační klíč není správný.');
        }

        $schemaPath = BASE_PATH . '/install/schema.sql';
        $schema = file_get_contents($schemaPath);
        if ($schema === false || $schema === '') {
            throw new RuntimeException('Soubor databázového schématu nelze načíst.');
        }
        $schema = str_replace('{DB_PREFIX}', $prefix, $schema);
        if (str_contains($schema, '{DB_PREFIX}')) {
            throw new RuntimeException('V databázovém schématu zůstal nevyřešený prefix.');
        }

        // Remove full-line SQL comments before splitting statements.
        $schema = preg_replace('/^--.*$/m', '', $schema) ?? $schema;
        // MySQL DDL implicitly commits; write the migration record only after all DDL succeeds.
        $statements = preg_split('/;\s*(?:\r?\n|$)/', trim($schema));
        foreach ($statements ?: [] as $statement) {
            $statement = trim($statement);
            if ($statement !== '') {
                $pdo->exec($statement);
            }
        }

        $record = $pdo->prepare('INSERT INTO `' . $migrationTable . '` (version) VALUES (:version)');
        $record->execute(['version' => '0001_initial']);

        if (file_put_contents($lockFile, gmdate('c') . PHP_EOL, LOCK_EX) === false) {
            throw new RuntimeException('Databáze byla vytvořena, ale nelze uložit instalační zámek. Zablokujte install/install.php ručně.');
        }
        @chmod($lockFile, 0600);
        $success = true;
    }
} catch (Throwable $exception) {
    error_log('Installer: ' . $exception->getMessage());
    $error = 'Instalaci se nepodařilo dokončit. Zkontrolujte konfiguraci .env, oprávnění složky storage a databázové připojení. Podrobnosti jsou pouze v serverovém error logu.';
    if (http_response_code() < 400) {
        http_response_code(500);
    }
}

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>
<!doctype html>
<html lang="cs">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Instalace – POSLEDNÍ SIGNÁL</title>
<style>
:root{color-scheme:dark;font:16px system-ui,sans-serif;background:#101820;color:#d7e2df}
*{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;padding:20px}
main{width:min(100%,620px);padding:clamp(24px,5vw,48px);border:1px solid #385052;background:#091217}
h1{letter-spacing:.08em}p{line-height:1.6;color:#a9bfba}.notice{padding:14px;border:1px solid #547b68;background:#13251f;color:#bce5cf}
.error{padding:14px;border:1px solid #8b514b;background:#2b1717;color:#ffc4bc}
label{display:block;margin:20px 0 8px}input{width:100%;padding:13px;background:#101d22;border:1px solid #526a68;color:#fff}
button{margin-top:20px;padding:13px 20px;background:#79b9a4;border:0;color:#08120f;font-weight:700;cursor:pointer}
small{color:#91a7a4}
</style>
</head>
<body><main>
<p>INSTALAČNÍ KONZOLE // 0001</p>
<h1>POSLEDNÍ SIGNÁL</h1>
<?php if ($success): ?>
<div class="notice"><strong>Instalace dokončena.</strong><p>Databázové tabulky byly vytvořeny a instalace uzamčena. Nyní otevřete hlavní stránku hry.</p></div>
<?php elseif ($error !== ''): ?>
<div class="error"><?= h($error) ?></div>
<?php else: ?>
<p>Instalátor vytvoří základní databázové tabulky. Vyžaduje funkční databázové údaje a instalační klíč APP_KEY ze serverového souboru .env.</p>
<form method="post">
<input type="hidden" name="_csrf" value="<?= h(Session::csrfToken()) ?>">
<label for="install_key">Instalační klíč</label>
<input id="install_key" name="install_key" type="password" autocomplete="off" required>
<button type="submit">Nainstalovat databázi</button>
</form>
<p><small>Instalaci lze spustit pouze jednou. Skutečný .env nikdy nevkládejte do GitHubu ani do chatu.</small></p>
<?php endif; ?>
</main></body></html>
