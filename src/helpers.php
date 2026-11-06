<?php
declare(strict_types=1);

const CATEGORIES = ['materiel' => 'Matériel', 'logiciel' => 'Logiciel', 'reseau' => 'Réseau', 'autre' => 'Autre'];
const PRIORITES  = ['faible' => 'Faible', 'moyenne' => 'Moyenne', 'elevee' => 'Élevée'];
const STATUTS    = ['ouvert' => 'Ouvert', 'en_cours' => 'En cours', 'resolu' => 'Résolu'];

const PRIORITE_BADGES = ['faible' => 'text-bg-secondary', 'moyenne' => 'text-bg-info', 'elevee' => 'text-bg-danger'];
const STATUT_BADGES   = ['ouvert' => 'text-bg-primary', 'en_cours' => 'text-bg-warning', 'resolu' => 'text-bg-success'];

/** Transitions autorisées : ouvert -> en cours -> résolu (pas de réouverture). */
const STATUT_SUIVANT = ['ouvert' => 'en_cours', 'en_cours' => 'resolu'];

/** Échappement HTML pour tout ce qui est affiché. */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $message];
}

function pull_flashes(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

function badge(string $kind, string $value): string
{
    $labels = $kind === 'statut' ? STATUTS : PRIORITES;
    $classes = $kind === 'statut' ? STATUT_BADGES : PRIORITE_BADGES;
    return '<span class="badge ' . e($classes[$value] ?? 'text-bg-light') . '">' . e($labels[$value] ?? $value) . '</span>';
}

/** Date UTC (base) -> affichage Europe/Paris. */
function format_date(string $utc): string
{
    $dt = new DateTimeImmutable($utc, new DateTimeZone('UTC'));
    return $dt->setTimezone(new DateTimeZone('Europe/Paris'))->format('d/m/Y H:i');
}

/** Entier strictement positif lu dans $_GET / $_POST, 0 si invalide. */
function int_param(string $key, string $source = 'GET'): int
{
    $data = $source === 'POST' ? $_POST : $_GET;
    $value = $data[$key] ?? null;
    return (is_string($value) && ctype_digit($value)) ? (int) $value : 0;
}

function str_param(string $key, string $source = 'POST'): string
{
    $data = $source === 'POST' ? $_POST : $_GET;
    $value = $data[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

/** Affiche une page d'erreur (403, 405...) dans le gabarit et arrête le script. */
function abort_page(int $code, string $message): never
{
    http_response_code($code);
    $pageTitle = $code === 403 ? 'Accès refusé' : 'Erreur';
    $errorCode = $code;
    $errorMessage = $message;
    require __DIR__ . '/views/error.php';
    exit;
}
