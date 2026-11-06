<?php
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';

$user = require_login();
$id = int_param('id', $_SERVER['REQUEST_METHOD'] === 'POST' ? 'POST' : 'GET');
$ticket = load_ticket_or_abort($id, $user);

// Seul l'auteur peut modifier, et seulement tant que le ticket est ouvert
if ((int) $ticket['user_id'] !== $user['id']) {
    abort_page(403, "Seul l'auteur d'un ticket peut en modifier le contenu.");
}
if ($ticket['statut'] !== 'ouvert') {
    abort_page(403, "Ce ticket n'est plus ouvert : il ne peut plus être modifié.");
}

$values = ['titre' => $ticket['titre'], 'description' => $ticket['description'], 'categorie' => $ticket['categorie']];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    [$values, $errors] = validate_ticket(str_param('titre'), str_param('description'), str_param('categorie'));
    if (!$errors) {
        if (!TicketRepository::update($id, $user['id'], $values)) {
            abort_page(403, "Ce ticket n'est plus ouvert : il ne peut plus être modifié.");
        }
        flash('success', 'Ticket modifié.');
        redirect('/ticket_view.php?id=' . $id);
    }
}

$action = '/ticket_edit.php';
$submitLabel = 'Enregistrer les modifications';
$ticketId = $id;
$pageTitle = 'Modifier le ticket #' . $id;
require __DIR__ . '/../src/views/header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <h1 class="h3 mb-3">Modifier le ticket #<?= (int) $id ?></h1>
        <?php if ($errors): ?>
            <div class="alert alert-danger" role="alert">Le formulaire contient des erreurs, merci de les corriger.</div>
        <?php endif; ?>
        <div class="card card-body">
            <?php require __DIR__ . '/../src/views/ticket_form.php'; ?>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../src/views/footer.php'; ?>
