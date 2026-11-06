<?php
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';

$user = require_login();
$admin = $user['role'] === 'admin';

// Indicateurs calculés depuis la base
$stats = TicketRepository::stats($admin ? null : $user['id']);
$cards = [
    ['Tickets ouverts', $stats['ouvert'], 'primary'],
    ['Tickets en cours', $stats['en_cours'], 'warning'],
    ['Tickets résolus', $stats['resolu'], 'success'],
];
if ($admin) {
    $cards[] = ['Priorité élevée non résolus', TicketRepository::countHighUnresolved(), 'danger'];
}

$pageTitle = 'Tableau de bord';
require __DIR__ . '/../src/views/header.php';
?>
<h1 class="h3 mb-4">Bonjour <?= e($user['nom']) ?></h1>

<div class="row g-3 row-cols-1 row-cols-sm-2 row-cols-xl-<?= count($cards) ?> mb-4">
    <?php foreach ($cards as [$label, $count, $color]): ?>
        <div class="col">
            <div class="card stat-card border-<?= e($color) ?> h-100">
                <div class="card-body">
                    <p class="text-muted mb-1"><?= e($label) ?></p>
                    <p class="display-6 mb-0 text-<?= e($color) ?>"><?= (int) $count ?></p>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<h2 class="h5">Actions rapides</h2>
<div class="d-flex flex-wrap gap-2">
    <?php if ($admin): ?>
        <a class="btn btn-primary" href="/admin.php">Traiter les tickets</a>
    <?php else: ?>
        <a class="btn btn-primary" href="/ticket_new.php">Créer un ticket</a>
        <a class="btn btn-outline-primary" href="/tickets.php">Voir mes tickets</a>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../src/views/footer.php'; ?>
