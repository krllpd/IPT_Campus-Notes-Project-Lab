# Campus Notes: security review project (repaired)

This started as intentionally vulnerable educational material. It has been repaired for the IPT security code review lab, but it is still a teaching app: run it only on an isolated machine you control, with synthetic data and a disposable local database. Never expose it to the internet or connect it to real accounts.

## Project layout

```
Insecure-PHP-Notes-Lab/
├── public/               <- web root (the only folder the server exposes)
│   ├── index.php         <- web app controller (forms, PRG redirects)
│   ├── api.php           <- JSON API (GET list, POST update)
│   └── style.css         <- moved out of the page so the CSP can forbid inline styles
├── src/                  <- not web-reachable
│   ├── bootstrap.php     <- error handling, security headers, loads everything
│   ├── config.php        <- reads settings from environment variables
│   ├── db.php            <- PDO connection (real prepared statements)
│   ├── session.php       <- hardened session start, login, logout, flash messages
│   ├── csrf.php          <- CSRF token create/verify
│   ├── validation.php    <- input helpers and note/registration validation
│   ├── view.php          <- e() escaping, render(), redirect()
│   ├── UserRepository.php
│   └── NoteRepository.php <- every note query is scoped to the owner
├── templates/home.php    <- HTML only, all output escaped
├── tools/set_password.php <- CLI tool to reset a synthetic account to bcrypt
├── schema.sql
└── README.md
```

No Composer packages are used.

## Local setup

1. Install PHP 8.1+ with `pdo_mysql` and a local MySQL 8 server. Confirm `pdo_mysql` appears in `php -m`.
2. As a local MySQL administrator, import `schema.sql` into a fresh disposable server. It creates `notes_lab`; the seed accounts now use bcrypt hashes. If you already imported the **old** schema, either drop and re-create `notes_lab`, or reset each account with `php tools/set_password.php alex@example.test` (old MD5 hashes will not work with `password_verify()`).
3. Create a least-privilege account, for example:
   ```sql
   CREATE USER 'notes_app'@'localhost' IDENTIFIED BY 'choose-a-local-password';
   GRANT SELECT, INSERT, UPDATE, DELETE ON notes_lab.* TO 'notes_app'@'localhost';
   ```
   Then set the variables in the same shell you start PHP from. PowerShell:
   ```powershell
   $env:LAB_DB_USER = 'notes_app'
   $env:LAB_DB_PASSWORD = 'choose-a-local-password'
   ```
   The app refuses to start without them (there is no `root`/empty-password fallback). Optional: `LAB_DB_HOST`, `LAB_DB_PORT`, `LAB_DB_NAME`, `LAB_ALLOWED_ORIGINS` (comma-separated, default `http://127.0.0.1:8000,http://localhost:8000`). Never commit credentials.
4. From this folder run:
   ```
   php -S 127.0.0.1:8000 -t public
   ```
   and visit http://127.0.0.1:8000. Stop with Ctrl+C.
5. Synthetic accounts (public throwaway passwords, lab only):
   - `alex@example.test` / `LabOnly-Alex-123`
   - `sam@example.test` / `LabOnly-Sam-456`

   Use separate browser profiles for separate sessions. New registrations need a 12-72 byte password and always get the `student` role.

## API

Both endpoints need a signed-in session cookie and only return or change the signed-in user's notes.

- `GET /api.php` lists your notes. `?user_id=` is optional; any value other than your own ID returns 403.
- `POST /api.php` with `Content-Type: application/json`, header `X-CSRF-Token: <token>` (the token is in the page's `<meta name="csrf-token">`), and a body like `{"id": 1, "title": "...", "body": "..."}`. Returns 404 for notes that are missing or not yours, 400 for malformed JSON or bad fields.
- Requests with an `Origin` not in `LAB_ALLOWED_ORIGINS` get 403. No CORS headers are sent.

## Production notes

Local HTTP is for the exercise only. Production needs HTTPS (the session cookie automatically becomes `Secure` over HTTPS), HSTS, secrets from a secret store, central logging, login rate limiting, and a real web server instead of `php -S`.
