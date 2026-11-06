<?php
declare(strict_types=1);

/**
 * CLI only: reset a synthetic account's password to a bcrypt hash.
 * Use this if your database still has the old MD5 hashes.
 *
 *   php tools/set_password.php alex@example.test
 *   (then type the new password and press Enter)
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/../src/bootstrap.php';

$email = $argv[1] ?? '';
if ($email === '') {
    fwrite(STDERR, "Usage: php tools/set_password.php <email>\n");
    exit(1);
}

fwrite(STDOUT, 'New password (input is visible): ');
$password = rtrim((string)fgets(STDIN), "\r\n");
if (strlen($password) < PASSWORD_MIN || strlen($password) > PASSWORD_MAX_BYTES) {
    fwrite(STDERR, 'Password must be ' . PASSWORD_MIN . ' to ' . PASSWORD_MAX_BYTES . " bytes.\n");
    exit(1);
}

$users = new UserRepository(db());
$user = $users->findByEmail($email);
if ($user === null) {
    fwrite(STDERR, "No such user.\n");
    exit(1);
}
$users->updatePasswordHash((int)$user['id'], $password);
fwrite(STDOUT, "Password updated.\n");
