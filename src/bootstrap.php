<?php
declare(strict_types=1);

// Never show errors to the client; log them instead.
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

require __DIR__ . '/config.php';
require __DIR__ . '/db.php';
require __DIR__ . '/session.php';
require __DIR__ . '/csrf.php';
require __DIR__ . '/validation.php';
require __DIR__ . '/view.php';
require __DIR__ . '/UserRepository.php';
require __DIR__ . '/NoteRepository.php';

/** Turn PHP warnings into exceptions so they reach the generic handler. */
set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

function log_exception(Throwable $e): string
{
    $ref = bin2hex(random_bytes(6));
    // Log a diagnostic without request data (which may contain passwords).
    error_log(sprintf('[campus-notes %s] %s: %s at %s:%d', $ref, $e::class, $e->getMessage(), $e->getFile(), $e->getLine()));
    return $ref;
}

function boot_html(): void
{
    set_exception_handler(static function (Throwable $e): void {
        $ref = log_exception($e);
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/html; charset=utf-8');
        }
        echo '<!doctype html><meta charset="utf-8"><title>Error</title>'
           . '<p>Something went wrong. Please try again later. (Reference: ' . e($ref) . ')</p>';
    });

    header('Content-Type: text/html; charset=utf-8');
    header("Content-Security-Policy: default-src 'self'; script-src 'none'; object-src 'none'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'");
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: same-origin');
    header('Cache-Control: no-store');

    start_secure_session();
}

function boot_api(): void
{
    set_exception_handler(static function (Throwable $e): void {
        log_exception($e);
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode(['error' => 'Internal server error']);
    });

    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: no-store');
    header("Content-Security-Policy: default-src 'none'; frame-ancestors 'none'");
    // Deliberately no Access-Control-Allow-* headers: the API is same-origin only.

    start_secure_session();
}
