<?php
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';

$admin = require_admin();
require_post();

$id = int_param('id', 'POST');
$ticket = load_ticket_or_abort($id, $admin);
$back = '/ticket_view.php?id=' . $id;

switch (str_param('action')) {
    case 'priorite':
        $priorite = str_param('priorite');
        if (!isset(PRIORITES[$priorite])) {
            flash('danger', 'Priorité invalide.');
        } else {
            TicketRepository::setPriority($id, $priorite);
            flash('success', 'Priorité mise à jour.');
        }
        break;

    case 'statut':
        // Le statut cible est déduit côté serveur : ouvert -> en cours -> résolu
        if (TicketRepository::advanceStatus($id, $ticket['statut'])) {
            flash('success', 'Statut mis à jour.');
        } else {
            flash('danger', "Ce changement de statut n'est pas possible.");
        }
        break;

    case 'reponse':
        [$contenu, $error] = validate_reply(str_param('contenu'));
        if ($error !== null) {
            flash('danger', $error);
        } else {
            TicketRepository::addReply($id, $admin['id'], $contenu);
            flash('success', 'Réponse ajoutée.');
        }
        break;

    default:
        flash('danger', 'Action inconnue.');
}

redirect($back);
