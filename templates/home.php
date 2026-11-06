<?php
/** Page template. Every dynamic value is passed through e(). */
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="csrf-token" content="<?= e($csrf) ?>">
<title>Campus Notes</title>
<link rel="stylesheet" href="/style.css">
</head>
<body>
<h1>Campus Notes</h1>
<?php if ($message !== null && $message !== ''): ?>
<p class="message"><?= e($message) ?></p>
<?php endif; ?>

<?php if ($userId === null): ?>
<h2>Register or sign in</h2>
<form method="post">
  <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
  <label>Email <input name="email" type="email" maxlength="254" required autocomplete="username"></label>
  <label>Password <input name="password" type="password" required autocomplete="current-password"></label>
  <button name="action" value="register">Register</button>
  <button name="action" value="login">Sign in</button>
</form>
<p><small>New accounts are created with the student role.</small></p>

<?php else: ?>
<form method="post" class="inline">
  <span>User ID: <?= e($userId) ?> | Role: <?= e($role) ?></span>
  <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
  <button name="action" value="logout">Log out</button>
</form>

<?php if ($editing !== null): ?>
<h2>Edit note</h2>
<form method="post">
  <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
  <input type="hidden" name="id" value="<?= e($editing['id']) ?>">
  <label>Title <input name="title" value="<?= e($editing['title']) ?>" maxlength="120" required></label>
  <label>Body <textarea name="body" required><?= e($editing['body']) ?></textarea></label>
  <button name="action" value="update">Update</button>
  <a href="/">Cancel</a>
</form>
<?php endif; ?>

<h2>Create a note</h2>
<form method="post">
  <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
  <label>Title <input name="title" maxlength="120" required></label>
  <label>Body <textarea name="body" required></textarea></label>
  <button name="action" value="save">Save</button>
</form>

<h2>Your notes</h2>
<?php if ($notes === []): ?>
<p>No notes yet.</p>
<?php endif; ?>
<?php foreach ($notes as $note): ?>
<article>
  <h3><?= e($note['title']) ?></h3>
  <p class="body"><?= e($note['body']) ?></p>
  <a href="/?edit=<?= e($note['id']) ?>">Edit</a>
  <form method="post" class="inline">
    <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
    <input type="hidden" name="id" value="<?= e($note['id']) ?>">
    <button name="action" value="delete">Delete</button>
  </form>
</article>
<?php endforeach; ?>
<?php endif; ?>
</body>
</html>
