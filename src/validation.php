<?php
declare(strict_types=1);

const NOTE_TITLE_MAX = 120;     // matches VARCHAR(120)
const NOTE_BODY_MAX  = 10000;
const PASSWORD_MIN   = 12;
const PASSWORD_MAX_BYTES = 72;  // bcrypt ignores bytes past 72

/** Returns a string field from an array, or '' if missing or not a string (e.g. title[]=x). */
function input_string(array $source, string $key): string
{
    $v = $source[$key] ?? '';
    return is_string($v) ? $v : '';
}

/** Accepts a positive integer (int, or a string of digits). Returns null otherwise. */
function positive_int(mixed $v): ?int
{
    if (is_int($v)) {
        return $v > 0 ? $v : null;
    }
    if (is_string($v) && preg_match('/^[1-9][0-9]{0,9}$/', $v)) {
        return (int)$v;
    }
    return null;
}

/** @return array{0: ?string, 1: string, 2: string} [error, title, body] */
function validate_note(mixed $title, mixed $body): array
{
    if (!is_string($title) || !is_string($body)) {
        return ['Title and body must be text.', '', ''];
    }
    $title = trim($title);
    $body = trim($body);
    if ($title === '' || $body === '') {
        return ['Title and body are required.', $title, $body];
    }
    if (!mb_check_encoding($title, 'UTF-8') || !mb_check_encoding($body, 'UTF-8')) {
        return ['Text must be valid UTF-8.', '', ''];
    }
    if (mb_strlen($title) > NOTE_TITLE_MAX) {
        return ['Title must be at most ' . NOTE_TITLE_MAX . ' characters.', $title, $body];
    }
    if (mb_strlen($body) > NOTE_BODY_MAX) {
        return ['Body must be at most ' . NOTE_BODY_MAX . ' characters.', $title, $body];
    }
    return [null, $title, $body];
}

function validate_registration(string $email, string $password): ?string
{
    if (strlen($email) > 254 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        return 'Enter a valid email address.';
    }
    if (mb_strlen($password) < PASSWORD_MIN || strlen($password) > PASSWORD_MAX_BYTES) {
        return 'Password must be ' . PASSWORD_MIN . ' to ' . PASSWORD_MAX_BYTES . ' bytes long.';
    }
    return null;
}
