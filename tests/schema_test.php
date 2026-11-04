<?php
declare(strict_types=1);

$schemaPath = dirname(__DIR__) . '/install/schema.sql';
$schema = file_get_contents($schemaPath);
if ($schema === false || $schema === '') {
    throw new RuntimeException('Initial database schema is missing or empty.');
}
$prefix = 'ci_validation_';
$expanded = str_replace('{DB_PREFIX}', $prefix, $schema);
if (str_contains($expanded, '{DB_PREFIX}')) {
    throw new RuntimeException('Unresolved DB_PREFIX placeholder remains.');
}
$expanded = preg_replace('/^--.*$/m', '', $expanded) ?? $expanded;
$statements = preg_split('/;\\s*(?:\\r?\\n|$)/', trim($expanded));
$statements = array_values(array_filter(array_map('trim', $statements ?: [])));
$expected = [
    $prefix . 'schema_migrations',
    $prefix . 'players',
    $prefix . 'game_saves',
    $prefix . 'game_events',
];
preg_match_all('/CREATE TABLE IF NOT EXISTS .([a-zA-Z0-9_]+)./i', $expanded, $matches);
$actual = $matches[1] ?? [];
if ($actual !== $expected) {
    throw new RuntimeException('Unexpected table list or order: ' . implode(', ', $actual));
}
if (count($statements) !== count($expected)) {
    throw new RuntimeException('Unexpected number of SQL statements: ' . count($statements));
}
if (!str_contains($expanded, 'REFERENCES ' . $prefix . 'players')) {
    throw new RuntimeException('Game save foreign key does not use the configured prefix.');
}
if (!str_contains($expanded, 'REFERENCES ' . $prefix . 'game_saves')) {
    throw new RuntimeException('Game event foreign key does not use the configured prefix.');
}
echo "Database schema checks passed (" . count($expected) . " tables).\\n";
