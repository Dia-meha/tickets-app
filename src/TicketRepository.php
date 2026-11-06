<?php
declare(strict_types=1);

/** Toutes les requêtes SQL liées aux tickets et aux réponses (requêtes préparées PDO). */
final class TicketRepository
{
    public static function create(int $userId, array $d): int
    {
        $stmt = Database::pdo()->prepare(
            "INSERT INTO tickets (titre, description, categorie, priorite, statut, user_id)
             VALUES (:titre, :description, :categorie, 'moyenne', 'ouvert', :user_id)"
        );
        $stmt->execute([
            ':titre' => $d['titre'], ':description' => $d['description'],
            ':categorie' => $d['categorie'], ':user_id' => $userId,
        ]);
        return (int) Database::pdo()->lastInsertId();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT t.*, u.nom AS auteur FROM tickets t JOIN users u ON u.id = t.user_id WHERE t.id = :id'
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * @param ?int  $userId null = tous les tickets (admin)
     * @param array $f      filtres : q, statut, priorite, categorie
     */
    public static function search(?int $userId, array $f): array
    {
        $where = [];
        $params = [];

        if ($userId !== null) {
            $where[] = 't.user_id = :uid';
            $params[':uid'] = $userId;
        }
        if (($f['q'] ?? '') !== '') {
            $where[] = 't.titre LIKE :q';
            $params[':q'] = '%' . addcslashes($f['q'], '%_\\') . '%';
        }
        // Filtres appliqués uniquement s'ils font partie des valeurs autorisées.
        if (isset(STATUTS[$f['statut'] ?? ''])) {
            $where[] = 't.statut = :statut';
            $params[':statut'] = $f['statut'];
        }
        if (isset(PRIORITES[$f['priorite'] ?? ''])) {
            $where[] = 't.priorite = :priorite';
            $params[':priorite'] = $f['priorite'];
        }
        if (isset(CATEGORIES[$f['categorie'] ?? ''])) {
            $where[] = 't.categorie = :categorie';
            $params[':categorie'] = $f['categorie'];
        }

        $sql = 'SELECT t.*, u.nom AS auteur FROM tickets t JOIN users u ON u.id = t.user_id'
             . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
             . ' ORDER BY t.updated_at DESC, t.id DESC LIMIT 200';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Modification réservée à l'auteur ET tant que le ticket est ouvert (vérifié dans le WHERE). */
    public static function update(int $id, int $userId, array $d): bool
    {
        $stmt = Database::pdo()->prepare(
            "UPDATE tickets SET titre = :titre, description = :description, categorie = :categorie
             WHERE id = :id AND user_id = :user_id AND statut = 'ouvert'"
        );
        $stmt->execute([
            ':titre' => $d['titre'], ':description' => $d['description'], ':categorie' => $d['categorie'],
            ':id' => $id, ':user_id' => $userId,
        ]);
        return $stmt->rowCount() === 1;
    }

    public static function delete(int $id, int $userId): bool
    {
        $stmt = Database::pdo()->prepare("DELETE FROM tickets WHERE id = :id AND user_id = :user_id AND statut = 'ouvert'");
        $stmt->execute([':id' => $id, ':user_id' => $userId]);
        return $stmt->rowCount() === 1;
    }

    public static function setPriority(int $id, string $priorite): bool
    {
        $stmt = Database::pdo()->prepare('UPDATE tickets SET priorite = :p WHERE id = :id');
        $stmt->execute([':p' => $priorite, ':id' => $id]);
        return $stmt->rowCount() === 1;
    }

    /** Fait avancer le statut d'un cran (ouvert -> en cours -> résolu), de façon atomique. */
    public static function advanceStatus(int $id, string $current): bool
    {
        $next = STATUT_SUIVANT[$current] ?? null;
        if ($next === null) {
            return false;
        }
        $stmt = Database::pdo()->prepare('UPDATE tickets SET statut = :next WHERE id = :id AND statut = :current');
        $stmt->execute([':next' => $next, ':id' => $id, ':current' => $current]);
        return $stmt->rowCount() === 1;
    }

    public static function replies(int $ticketId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT r.*, u.nom AS auteur FROM reponses r JOIN users u ON u.id = r.user_id
             WHERE r.ticket_id = :id ORDER BY r.created_at, r.id'
        );
        $stmt->execute([':id' => $ticketId]);
        return $stmt->fetchAll();
    }

    public static function addReply(int $ticketId, int $userId, string $contenu): void
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('INSERT INTO reponses (ticket_id, user_id, contenu) VALUES (:t, :u, :c)');
        $stmt->execute([':t' => $ticketId, ':u' => $userId, ':c' => $contenu]);
        $pdo->prepare('UPDATE tickets SET updated_at = CURRENT_TIMESTAMP WHERE id = :id')->execute([':id' => $ticketId]);
        $pdo->commit();
    }

    /** @return array{ouvert:int,en_cours:int,resolu:int} calculé depuis la base */
    public static function stats(?int $userId): array
    {
        $sql = 'SELECT statut, COUNT(*) AS n FROM tickets' . ($userId !== null ? ' WHERE user_id = :uid' : '') . ' GROUP BY statut';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($userId !== null ? [':uid' => $userId] : []);
        $stats = ['ouvert' => 0, 'en_cours' => 0, 'resolu' => 0];
        foreach ($stmt->fetchAll() as $row) {
            $stats[$row['statut']] = (int) $row['n'];
        }
        return $stats;
    }

    public static function countHighUnresolved(): int
    {
        return (int) Database::pdo()->query("SELECT COUNT(*) FROM tickets WHERE priorite = 'elevee' AND statut <> 'resolu'")->fetchColumn();
    }
}
