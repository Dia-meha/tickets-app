<?php
declare(strict_types=1);

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    $known = $_SESSION['csrf'] ?? '';
    $sent = $_POST['csrf_token'] ?? '';
    if ($known === '' || !is_string($sent) || !hash_equals($known, $sent)) {
        abort_page(403, 'Jeton de sécurité (CSRF) absent ou invalide. Rechargez la page et réessayez.');
    }
}

/** Exige une requête POST avec un jeton CSRF valide (actions qui modifient des données). */
function require_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        header('Allow: POST');
        abort_page(405, 'Méthode non autorisée : cette action doit être envoyée en POST.');
    }
    csrf_check();
}
