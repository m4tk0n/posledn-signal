<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Core/Env.php';

use App\Core\Env;

function assertEnv(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$path = tempnam(sys_get_temp_dir(), 'lastsignal-env-');
if ($path === false) {
    throw new RuntimeException('Unable to create temporary environment fixture.');
}

try {
    $contents = implode("\n", [
        '# test configuration',
        'APP_TIMEZONE=Europe/Prague',
        'DB_PREFIX="test_prefix_"',
        'APP_KEY='test-key-with-quotes'',
        'INVALID KEY=ignored',
        'NO_SEPARATOR',
        '',
    ]);
    if (file_put_contents($path, $contents) === false) {
        throw new RuntimeException('Unable to write temporary environment fixture.');
    }

    Env::load($path);
    assertEnv(Env::get('APP_TIMEZONE') === 'Europe/Prague', 'Plain environment value was not loaded.');
    assertEnv(Env::get('DB_PREFIX') === 'test_prefix_', 'Double-quoted environment value was not normalized.');
    assertEnv(Env::get('APP_KEY') === 'test-key-with-quotes', 'Single-quoted environment value was not normalized.');
    assertEnv(Env::get('MISSING_KEY', 'fallback') === 'fallback', 'Environment fallback value was not returned.');
    assertEnv(Env::get('INVALID_KEY') === null, 'Invalid environment key must be ignored.');

    echo "Environment configuration checks passed.\n";
} finally {
    @unlink($path);
}
