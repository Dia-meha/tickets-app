<?php
/** @var string $pageTitle */
$user = current_user();
$current = basename($_SERVER['SCRIPT_NAME'] ?? '');
$links = [];
if ($user) {
    $links[] = ['/dashboard.php', 'Tableau de bord', 'dashboard.php'];
    if ($user['role'] === 'admin') {
        $links[] = ['/admin.php', 'Administration', 'admin.php'];
    } else {
        $links[] = ['/tickets.php', 'Mes tickets', 'tickets.php'];
        $links[] = ['/ticket_new.php', 'Nouveau ticket', 'ticket_new.php'];
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? 'Assistance') ?> – Assistance informatique</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body>
<a class="visually-hidden-focusable skip-link" href="#main">Aller au contenu</a>
<nav class="navbar navbar-expand-lg navbar-dark bg-brand" aria-label="Navigation principale">
    <div class="container">
        <a class="navbar-brand fw-semibold" href="<?= $user ? e(home_for($user)) : '/login.php' ?>">🛠 Assistance IT</a>
        <?php if ($user): ?>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
                    aria-controls="mainNav" aria-expanded="false" aria-label="Afficher le menu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <?php foreach ($links as [$href, $label, $file]): ?>
                        <li class="nav-item">
                            <a class="nav-link<?= $current === $file ? ' active' : '' ?>" href="<?= e($href) ?>"
                               <?= $current === $file ? 'aria-current="page"' : '' ?>><?= e($label) ?></a>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <form method="post" action="/logout.php" class="d-flex flex-wrap align-items-center gap-2">
                    <?= csrf_field() ?>
                    <span class="navbar-text text-white-50 small">
                        <?= e($user['nom']) ?> (<?= $user['role'] === 'admin' ? 'administrateur' : 'utilisateur' ?>)
                    </span>
                    <button type="submit" class="btn btn-outline-light btn-sm">Déconnexion</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</nav>
<main id="main" class="container py-4">
<?php foreach (pull_flashes() as $f): ?>
    <div class="alert alert-<?= e($f['type']) ?> alert-dismissible fade show" role="alert">
        <?= e($f['msg']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
    </div>
<?php endforeach; ?>
