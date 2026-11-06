<?php
declare(strict_types=1);

// Aucune erreur PHP n'est affichée au visiteur : elles sont journalisées.
ini_set('display_errors', '0');
ini_set('log_errors', '1');

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/validation.php';
require_once __DIR__ . '/TicketRepository.php';

// Gestion globale des erreurs : message générique, détails dans les logs uniquement.
set_exception_handler(function (Throwable $e): void {
    error_log('[tickets] ' . get_class($e) . ': ' . $e->getMessage());
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, 'Erreur : ' . $e->getMessage() . PHP_EOL);
        exit(1);
    }
    http_response_code(500);
    if (str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/api/')) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Erreur interne du serveur.']);
    } else {
        $pageTitle = 'Erreur';
        $errorCode = 500;
        $errorMessage = 'Une erreur est survenue. Réessayez plus tard.';
        try {
            require __DIR__ . '/views/error.php';
        } catch (Throwable) {
            echo 'Une erreur est survenue.';
        }
    }
});

if (PHP_SAPI !== 'cli') {
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
