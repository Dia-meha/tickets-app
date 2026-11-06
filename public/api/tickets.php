<?php
declare(strict_types=1);
require __DIR__ . '/../../src/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

// Mêmes règles d'accès que les pages classiques, contrôlées côté serveur.
$user = require_login(true);

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    http_response_code(405);
    header('Allow: GET');
    echo json_encode(['error' => 'Méthode non autorisée.']);
    exit;
}

$filters = [
    'q'         => str_param('q', 'GET'),
    'statut'    => str_param('statut', 'GET'),
    'priorite'  => str_param('priorite', 'GET'),
    'categorie' => str_param('categorie', 'GET'),
];

// Un utilisateur ne reçoit que SES tickets, quel que soit le paramètre envoyé.
$tickets = TicketRepository::search($user['role'] === 'admin' ? null : $user['id'], $filters);

$out = array_map(static fn (array $t): array => [
    'id'              => (int) $t['id'],
    'titre'           => $t['titre'],
    'categorie_label' => CATEGORIES[$t['categorie']] ?? $t['categorie'],
    'priorite_label'  => PRIORITES[$t['priorite']] ?? $t['priorite'],
    'priorite_badge'  => PRIORITE_BADGES[$t['priorite']] ?? 'text-bg-light',
    'statut_label'    => STATUTS[$t['statut']] ?? $t['statut'],
    'statut_badge'    => STATUT_BADGES[$t['statut']] ?? 'text-bg-light',
    'auteur'          => $t['auteur'],
    'updated_at'      => format_date($t['updated_at']),
    'can_edit'        => $user['role'] !== 'admin' && $t['statut'] === 'ouvert',
], $tickets);

echo json_encode(['tickets' => $out], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
