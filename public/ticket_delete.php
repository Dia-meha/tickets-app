<?php
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';

$user = require_login();
require_post();   // POST + jeton CSRF obligatoires

$id = int_param('id', 'POST');
$ticket = load_ticket_or_abort($id, $user);

if ((int) $ticket['user_id'] !== $user['id'] || $ticket['statut'] !== 'ouvert') {
    abort_page(403, "Seul l'auteur peut supprimer son ticket, et uniquement s'il est encore ouvert.");
}
if (!TicketRepository::delete($id, $user['id'])) {
    abort_page(403, "Ce ticket n'est plus ouvert : il ne peut plus être supprimé.");
}

flash('success', 'Ticket supprimé.');
redirect('/tickets.php');
