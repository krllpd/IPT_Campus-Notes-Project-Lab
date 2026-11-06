<?php
declare(strict_types=1);

/**
 * Application configuration, read from environment variables only.
 * No credentials are stored in code and there is no fallback to root/empty.
 */
function app_config(): array
{
    static $config = null;
    if ($config !== null) {
        return $config;
    }

    $host = getenv('LAB_DB_HOST') ?: 'localhost';
    $port = getenv('LAB_DB_PORT') ?: '3306';
    $name = getenv('LAB_DB_NAME') ?: 'notes_lab';

    $origins = getenv('LAB_ALLOWED_ORIGINS') ?: 'http://127.0.0.1:8000,http://localhost:8000';

    $config = [
        'db' => [
            // LAB_DB_DSN is optional and only meant for local testing.
            'dsn'      => getenv('LAB_DB_DSN') ?: "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4",
            'user'     => getenv('LAB_DB_USER'),
            'password' => getenv('LAB_DB_PASSWORD'),
        ],
        'allowed_origins' => array_values(array_filter(array_map('trim', explode(',', $origins)))),
        'session_idle_seconds' => 1800,
    ];
    return $config;
}
