<?php
declare(strict_types=1);

try {
    require __DIR__ . '/app/bootstrap.php';
} catch (Throwable $exception) {
    error_log('Application bootstrap failed: ' . $exception->getMessage());
    http_response_code(500);
    $title = 'POSLEDNÍ SIGNÁL – dočasně nedostupné';
    $message = 'Herní systém se právě připravuje. Zkuste to prosím později.';
}
?>
<!doctype html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#101820">
    <title><?= htmlspecialchars($title ?? 'POSLEDNÍ SIGNÁL', ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>
    <main class="screen">
        <section class="panel">
            <p class="eyebrow">ZÁZNAM SYSTÉMU // 0001</p>
            <h1>POSLEDNÍ SIGNÁL</h1>
            <p class="subtitle">Komunikační uzel – nouzový režim</p>
            <div class="signal" aria-hidden="true"><span></span><span></span><span></span><span></span><span></span></div>
            <p><?= htmlspecialchars($message ?? 'Systém se spouští.', ENT_QUOTES, 'UTF-8') ?></p>
            <p class="status">STAV: <?= isset($message) ? 'PŘÍPRAVA' : 'ZÁKLADNÍ ROZHRANÍ ONLINE' ?></p>
        </section>
    </main>
</body>
</html>
