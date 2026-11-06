<?php
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';

$user = require_login();
if ($user['role'] === 'admin') {
    abort_page(403, 'Les administrateurs ne créent pas de tickets : utilisez un compte utilisateur.');
}

$values = ['titre' => '', 'description' => '', 'categorie' => ''];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    // Validation serveur (la validation JavaScript n'est qu'un confort)
    [$values, $errors] = validate_ticket(str_param('titre'), str_param('description'), str_param('categorie'));
    if (!$errors) {
        $id = TicketRepository::create($user['id'], $values);   // statut « ouvert », priorité « moyenne » imposés
        flash('success', 'Ticket créé avec succès.');
        redirect('/ticket_view.php?id=' . $id);
    }
}

$action = '/ticket_new.php';
$submitLabel = 'Créer le ticket';
$ticketId = 0;
$pageTitle = 'Nouveau ticket';
require __DIR__ . '/../src/views/header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <h1 class="h3 mb-3">Nouveau ticket</h1>
        <?php if ($errors): ?>
            <div class="alert alert-danger" role="alert">Le formulaire contient des erreurs, merci de les corriger.</div>
        <?php endif; ?>
        <div class="card card-body">
            <?php require __DIR__ . '/../src/views/ticket_form.php'; ?>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../src/views/footer.php'; ?>
