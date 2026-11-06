<?php
declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';
boot_api();

const API_MAX_BODY_BYTES = 65536;

function json_out(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// ---- Cross-origin protection (cookie-authenticated API, same-origin only) ----
$origin = $_SERVER['HTTP_ORIGIN'] ?? null;
if ($origin !== null && !in_array($origin, app_config()['allowed_origins'], true)) {
    json_out(403, ['error' => 'Origin not allowed']);
}
if (($_SERVER['HTTP_SEC_FETCH_SITE'] ?? '') === 'cross-site') {
    json_out(403, ['error' => 'Cross-site request rejected']);
}

if ($method !== 'GET' && $method !== 'POST') {
    header('Allow: GET, POST');
    json_out(405, ['error' => 'Method not supported']);
}

// ---- Authentication ----
$uid = current_user_id();
if ($uid === null) {
    json_out(401, ['error' => 'Authentication required']);
}

$notes = new NoteRepository(db());

// ---- GET: list the signed-in user's notes ----
if ($method === 'GET') {
    if (isset($_GET['user_id'])) {
        $requested = positive_int($_GET['user_id']);
        if ($requested === null) {
            json_out(400, ['error' => 'user_id must be a positive integer']);
        }
        if ($requested !== $uid) {
            json_out(403, ['error' => 'Forbidden']);
        }
    }
    json_out(200, ['notes' => $notes->listForOwner($uid)]);
}

// ---- POST: update one of the signed-in user's notes ----
$contentType = strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0]));
if ($contentType !== 'application/json') {
    json_out(415, ['error' => 'Content-Type must be application/json']);
}
if (!csrf_valid($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
    json_out(403, ['error' => 'Invalid or missing CSRF token']);
}

$raw = file_get_contents('php://input', false, null, 0, API_MAX_BODY_BYTES + 1);
if ($raw === false || strlen($raw) > API_MAX_BODY_BYTES) {
    json_out(413, ['error' => 'Request body too large']);
}

try {
    $data = json_decode($raw, true, 8, JSON_THROW_ON_ERROR);
} catch (JsonException) {
    json_out(400, ['error' => 'Malformed JSON']);
}

if (!is_array($data) || array_is_list($data)) {
    json_out(400, ['error' => 'Body must be a JSON object']);
}
$unknown = array_diff(array_keys($data), ['id', 'title', 'body']);
if ($unknown !== []) {
    json_out(400, ['error' => 'Unexpected fields', 'fields' => array_values($unknown)]);
}

$id = is_int($data['id'] ?? null) ? positive_int($data['id']) : null;
if ($id === null) {
    json_out(400, ['error' => 'id must be a positive integer']);
}
[$error, $title, $body] = validate_note($data['title'] ?? null, $data['body'] ?? null);
if ($error !== null) {
    json_out(422, ['error' => $error]);
}

// Same 404 for "missing" and "someone else's", so IDs of other users' notes are not confirmed.
if (!$notes->updateForOwner($id, $uid, $title, $body)) {
    json_out(404, ['error' => 'Note not found']);
}
json_out(200, ['updated' => 1, 'note' => $notes->findForOwner($id, $uid)]);
