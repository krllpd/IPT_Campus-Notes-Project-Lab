-- Campus Notes (repaired). Import into a fresh, disposable local MySQL 8 server.
CREATE DATABASE IF NOT EXISTS notes_lab CHARACTER SET utf8mb4;
USE notes_lab;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(254) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role VARCHAR(20) NOT NULL DEFAULT 'student',
  CONSTRAINT chk_users_role CHECK (role IN ('student', 'admin'))
);

CREATE TABLE notes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  title VARCHAR(120) NOT NULL,
  body TEXT NOT NULL,
  INDEX idx_notes_user (user_id),
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Synthetic accounts for local use only (passwords are in the README).
-- Hashes were generated with PHP password_hash(..., PASSWORD_DEFAULT) (bcrypt).
INSERT INTO users (id, email, password_hash, role) VALUES
  (1, 'alex@example.test', '$2y$10$sRlNtpPLKhhmIyo6OYwTze/L3e7I1AtHNxkGpUbYMs/ihm6eEtIs.', 'student'),
  (2, 'sam@example.test',  '$2y$10$CtVH2ILce486BAKwrTjlXuvwypz2CWCx17qy/HVeMrmuwsMoqDbBe', 'student');

INSERT INTO notes (user_id, title, body) VALUES
  (1, 'Alex: course reminder', 'Review the security reading before class.'),
  (1, 'Alex: project idea', 'Build a small student directory.'),
  (2, 'Sam: study plan', 'Compare authentication and authorization.'),
  (2, 'Sam: meeting', 'Prepare questions for the next group discussion.');
