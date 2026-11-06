<?php
declare(strict_types=1);

/**
 * All note queries are parameterised and scoped to the owner taken from the
 * session. There is deliberately no method that reads or changes a note
 * without an owner ID.
 */
final class NoteRepository
{
    public function __construct(private PDO $db) {}

    public function listForOwner(int $ownerId): array
    {
        $stmt = $this->db->prepare('SELECT id, user_id, title, body FROM notes WHERE user_id = ? ORDER BY id DESC');
        $stmt->execute([$ownerId]);
        return $stmt->fetchAll();
    }

    public function findForOwner(int $noteId, int $ownerId): ?array
    {
        $stmt = $this->db->prepare('SELECT id, user_id, title, body FROM notes WHERE id = ? AND user_id = ?');
        $stmt->execute([$noteId, $ownerId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(int $ownerId, string $title, string $body): void
    {
        $stmt = $this->db->prepare('INSERT INTO notes (user_id, title, body) VALUES (?, ?, ?)');
        $stmt->execute([$ownerId, $title, $body]);
    }

    /** Returns false when the note does not exist or belongs to someone else. */
    public function updateForOwner(int $noteId, int $ownerId, string $title, string $body): bool
    {
        if ($this->findForOwner($noteId, $ownerId) === null) {
            return false;
        }
        $stmt = $this->db->prepare('UPDATE notes SET title = ?, body = ? WHERE id = ? AND user_id = ?');
        $stmt->execute([$title, $body, $noteId, $ownerId]);
        return true;
    }

    public function deleteForOwner(int $noteId, int $ownerId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM notes WHERE id = ? AND user_id = ?');
        $stmt->execute([$noteId, $ownerId]);
        return $stmt->rowCount() === 1;
    }
}
