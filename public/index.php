<?php
declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';
boot_html();

// Used to keep login timing similar whether or not the email exists.
const DUMMY_HASH = '$2y$10$lQPMr8PtvT9wUqgp2CozfeEKr6W9WhSCcvCo/Ox8Z3o2LuBEh8feG';

$users = new UserRepository(db());
$notes = new NoteRepository(db());
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Old GET-based state changes are refused outright.
if ($method === 'GET' && (isset($_GET['delete']) || isset($_GET['logout']))) {
    http_response_code(405);
    header('Allow: POST');
    echo '<!doctype html><meta charset="utf-8"><p>That action requires a POST form. <a href="/">Back</a></p>';
    exit;
}

if ($method === 'POST') {
    // Every POST (including register and login) must carry the session's CSRF token.
    if (!csrf_valid($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        echo '<!doctype html><meta charset="utf-8"><p>Request rejected: invalid or missing security token. '
           . '<a href="/">Reload the page</a> and try again.</p>';
        exit;
    }

    $action = input_string($_POST, 'action');
    $uid = current_user_id();

    switch ($action) {
        case 'register':
            $email = trim(input_string($_POST, 'email'));
            $password = input_string($_POST, 'password');
            // Any "role" field in the request is ignored.
            $error = validate_registration($email, $password);
            if ($error !== null) {
                flash($error);
                redirect('/');
            }
            try {
                $users->create($email, $password);
                flash('Account created. Please sign in.');
            } catch (PDOException $e) {
                if ($e->getCode() !== '23000') {   // 23000 = duplicate email
                    throw $e;
                }
                flash('Could not create that account.');
            }
            redirect('/');

        case 'login':
            $email = trim(input_string($_POST, 'email'));
            $password = input_string($_POST, 'password');
            $user = $email !== '' ? $users->findByEmail($email) : null;
            $ok = password_verify($password, $user['password_hash'] ?? DUMMY_HASH) && $user !== null;
            if ($ok) {
                if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
                    $users->updatePasswordHash((int)$user['id'], $password);
                }
                login_user($user);
                redirect('/');
            }
            flash('Invalid email or password.');   // no reflected input, no hint which part was wrong
            redirect('/');

        case 'logout':
            logout_user();
            redirect('/');

        case 'save':
        case 'update':
        case 'delete':
            if ($uid === null) {
                http_response_code(401);
                echo '<!doctype html><meta charset="utf-8"><p>Please sign in. <a href="/">Back</a></p>';
                exit;
            }
            if ($action === 'delete') {
                $id = positive_int($_POST['id'] ?? null);
                $deleted = $id !== null && $notes->deleteForOwner($id, $uid);
                flash($deleted ? 'Note deleted.' : 'Note not found.');
                redirect('/');
            }
            [$error, $title, $body] = validate_note($_POST['title'] ?? null, $_POST['body'] ?? null);
            if ($error !== null) {
                flash($error);
                redirect('/');
            }
            if ($action === 'save') {
                // Owner always comes from the session, never from the form.
                $notes->create($uid, $title, $body);
                flash('Note saved.');
            } else {
                $id = positive_int($_POST['id'] ?? null);
                $updated = $id !== null && $notes->updateForOwner($id, $uid, $title, $body);
                flash($updated ? 'Note updated.' : 'Note not found.');
            }
            redirect('/');

        default:
            http_response_code(400);
            echo '<!doctype html><meta charset="utf-8"><p>Unknown action. <a href="/">Back</a></p>';
            exit;
    }
}

// ---- GET: render the page ----
$uid = current_user_id();
$list = [];
$editing = null;
if ($uid !== null) {
    $list = $notes->listForOwner($uid);
    if (isset($_GET['edit'])) {
        $editId = positive_int($_GET['edit']);
        $editing = $editId !== null ? $notes->findForOwner($editId, $uid) : null;
        if ($editing === null) {
            http_response_code(404);
            flash('Note not found.');
        }
    }
}

render('home', [
    'message' => flash(),
    'userId'  => $uid,
    'role'    => current_role(),
    'notes'   => $list,
    'editing' => $editing,
    'csrf'    => csrf_token(),
]);
