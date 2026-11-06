<?php
declare(strict_types=1);

/**
 * Returns the shared PDO connection. Real prepared statements only
 * (emulation off) and exceptions on error; exceptions are caught by the
 * global handler in bootstrap.php and never shown to the user.
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $cfg = app_config()['db'];
    $isSqlite = str_starts_with($cfg['dsn'], 'sqlite:');

    if (!$isSqlite && ($cfg['user'] === false || $cfg['user'] === '' || $cfg['password'] === false)) {
        throw new RuntimeException('Database credentials are not configured (set LAB_DB_USER and LAB_DB_PASSWORD).');
    }

    $pdo = new PDO(
        $cfg['dsn'],
        $isSqlite ? null : $cfg['user'],
        $isSqlite ? null : $cfg['password'],
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
    return $pdo;
}
