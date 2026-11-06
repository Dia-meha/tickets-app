<?php
declare(strict_types=1);

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function home_for(array $user): string
{
    return $user['role'] === 'admin' ? '/admin.php' : '/dashboard.php';
}

/** Exige une session ouverte. En mode JSON, répond 401 au lieu de rediriger. */
function require_login(bool $json = false): array
{
    $user = current_user();
    if ($user === null) {
        if ($json) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Authentification requise.']);
            exit;
        }
        redirect('/login.php');
    }
    return $user;
}

function require_admin(bool $json = false): array
{
    $user = require_login($json);
    if ($user['role'] !== 'admin') {
        if ($json) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Accès réservé aux administrateurs.']);
            exit;
        }
        abort_page(403, 'Cette page est réservée aux administrateurs.');
    }
    return $user;
}

function attempt_login(string $email, string $password): bool
{
    $stmt = Database::pdo()->prepare('SELECT id, nom, email, mot_de_passe, role FROM users WHERE email = :email');
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['mot_de_passe'])) {
        return false;
    }

    session_regenerate_id(true);   // évite la fixation de session
    unset($_SESSION['csrf']);      // nouveau jeton CSRF pour la nouvelle session
    $_SESSION['user'] = [
        'id'    => (int) $user['id'],
        'nom'   => $user['nom'],
        'email' => $user['email'],
        'role'  => $user['role'],
    ];
    return true;
}

function logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/**
 * Charge un ticket en vérifiant les droits côté serveur :
 * un utilisateur n'accède qu'à ses tickets, un admin à tous.
 * Même réponse pour « inexistant » et « pas à moi » (pas d'énumération d'identifiants).
 */
function load_ticket_or_abort(int $id, array $user): array
{
    $ticket = $id > 0 ? TicketRepository::find($id) : null;
    if ($ticket === null || ($user['role'] !== 'admin' && (int) $ticket['user_id'] !== $user['id'])) {
        abort_page(403, "Ticket introuvable ou vous n'avez pas le droit d'y accéder.");
    }
    return $ticket;
}
