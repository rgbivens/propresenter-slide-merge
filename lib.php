<?php
declare(strict_types=1);

define('SM_APP', true);

function sm_config(): array
{
    static $config = null;
    if ($config === null) {
        $file = __DIR__ . '/config.php';
        // Before setup there is only config.example.php; treat that as "not configured".
        $config = is_file($file) ? require $file : ['db' => [], 'stats_password' => ''];
    }
    return $config;
}

function sm_configured(): bool
{
    $db = sm_config()['db'] ?? [];
    foreach (['name', 'user', 'password'] as $key) {
        $value = (string) ($db[$key] ?? '');
        if ($value === '' || preg_match('/^DATABASE_/', $value)) {
            return false;
        }
    }
    return true;
}

function sm_db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $c = sm_config()['db'];
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $c['host'], (int) ($c['port'] ?? 3306), $c['name']);
        $pdo = new PDO($dsn, $c['user'], $c['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    return $pdo;
}

function sm_create_table(PDO $db): void
{
    $db->exec('CREATE TABLE IF NOT EXISTS sm_merges (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        created_at DATETIME NOT NULL,
        presentations INT UNSIGNED NOT NULL,
        KEY created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
}

/** Runs $fn, creating the table first if it doesn't exist yet. */
function sm_with_table(callable $fn)
{
    $db = sm_db();
    try {
        return $fn($db);
    } catch (PDOException $e) {
        if ($e->getCode() !== '42S02') {
            throw $e;
        }
        sm_create_table($db);
        return $fn($db);
    }
}
