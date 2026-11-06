<?php
declare(strict_types=1);

/**
 * Données de démonstration : 1 administrateur, 2 utilisateurs, 14 tickets, 6 réponses.
 * Usage : php db/seed.php          (refuse de s'exécuter si des utilisateurs existent déjà)
 *         php db/seed.php --reset  (vide les tables puis recrée les données)
 * Les mots de passe ci-dessous sont des mots de passe de DÉMONSTRATION, sans valeur réelle.
 */
require __DIR__ . '/../src/bootstrap.php';

if (PHP_SAPI !== 'cli') {
    exit('Script réservé à la ligne de commande.');
}

$pdo = Database::pdo();
$reset = in_array('--reset', $argv ?? [], true);

if ($reset) {
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    foreach (['reponses', 'tickets', 'users'] as $table) {
        $pdo->exec("TRUNCATE TABLE $table");
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
} elseif ((int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0) {
    fwrite(STDERR, "La base contient déjà des données. Utilisez --reset pour la réinitialiser.\n");
    exit(1);
}

$accounts = [
    ['Administrateur Démo', 'admin@example.test', 'Admin-Demo-2026!', 'admin'],
    ['Alice Martin',        'alice@example.test', 'Alice-Demo-2026!', 'utilisateur'],
    ['Bruno Lefebvre',      'bruno@example.test', 'Bruno-Demo-2026!', 'utilisateur'],
];

$pdo->beginTransaction();
$ids = [];
$insUser = $pdo->prepare('INSERT INTO users (nom, email, mot_de_passe, role) VALUES (:n, :e, :p, :r)');
foreach ($accounts as [$nom, $email, $pwd, $role]) {
    $insUser->execute([':n' => $nom, ':e' => $email, ':p' => password_hash($pwd, PASSWORD_DEFAULT), ':r' => $role]);
    $ids[$email] = (int) $pdo->lastInsertId();
}
$admin = $ids['admin@example.test'];
$alice = $ids['alice@example.test'];
$bruno = $ids['bruno@example.test'];

// [auteur, titre, description, catégorie, priorité, statut]
$tickets = [
    [$alice, "Écran qui scintille en salle B12", "L'écran du poste 4 de la salle B12 scintille depuis lundi et provoque des maux de tête. Le câble HDMI a déjà été changé sans amélioration.", 'materiel', 'moyenne', 'ouvert'],
    [$alice, "Impossible d'imprimer depuis le portail", "Depuis ce matin, toute impression lancée depuis le portail pédagogique reste bloquée dans la file d'attente de l'imprimante du couloir.", 'logiciel', 'faible', 'ouvert'],
    [$alice, "Wi-Fi instable au CDI", "La connexion Wi-Fi du CDI se coupe toutes les dix minutes environ, surtout entre 10 h et 12 h quand la salle est pleine.", 'reseau', 'elevee', 'en_cours'],
    [$alice, "Licence logiciel de montage expirée", "Le logiciel de montage vidéo affiche un message de licence expirée au démarrage sur les trois postes de la salle multimédia.", 'logiciel', 'moyenne', 'en_cours'],
    [$alice, "Souris sans fil défectueuse", "La souris du poste du secrétariat ne répond plus qu'une fois sur deux, même après remplacement de la pile.", 'materiel', 'faible', 'resolu'],
    [$alice, "Mot de passe de la messagerie bloqué", "Mon compte de messagerie est verrouillé après plusieurs essais. J'ai besoin d'y accéder pour envoyer les convocations de demain.", 'autre', 'elevee', 'resolu'],
    [$alice, "Projecteur de la salle 204 sans image", "Le projecteur s'allume mais n'affiche aucune image, ni en HDMI ni en VGA. Le voyant de la lampe clignote en rouge.", 'materiel', 'elevee', 'ouvert'],
    [$bruno, "Accès refusé au dossier partagé", "Je n'arrive plus à ouvrir le dossier partagé du service comptabilité alors que j'y accédais normalement la semaine dernière.", 'reseau', 'elevee', 'ouvert'],
    [$bruno, "Mise à jour du navigateur", "Le navigateur du poste de la salle des professeurs est trop ancien et certains sites pédagogiques ne s'affichent plus correctement.", 'logiciel', 'faible', 'en_cours'],
    [$bruno, "Clavier avec touches bloquées", "Plusieurs touches du clavier de la salle 101 restent enfoncées, ce qui rend la saisie impossible pour les élèves.", 'materiel', 'moyenne', 'resolu'],
    [$bruno, "VPN inaccessible depuis l'extérieur", "Impossible de me connecter au VPN depuis mon domicile : la connexion expire après l'authentification. Je dois consulter des documents internes.", 'reseau', 'elevee', 'en_cours'],
    [$bruno, "Demande d'installation d'un tableur", "Je souhaite l'installation d'un tableur sur le poste de la salle 302 pour les travaux pratiques de la semaine prochaine.", 'autre', 'moyenne', 'ouvert'],
    [$bruno, "Écran tactile qui ne répond plus", "L'écran tactile interactif de la salle 105 ne réagit plus au toucher dans la moitié droite de la dalle depuis hier après-midi.", 'materiel', 'moyenne', 'resolu'],
    [$bruno, "Alerte antivirus sur un poste", "Une alerte antivirus apparaît chaque matin sur le poste du laboratoire de langues. Le message mentionne un fichier temporaire suspect.", 'logiciel', 'faible', 'ouvert'],
];

$insTicket = $pdo->prepare(
    'INSERT INTO tickets (titre, description, categorie, priorite, statut, user_id, created_at, updated_at)
     VALUES (:t, :d, :c, :p, :s, :u, :ts1, :ts2)'
);
$ticketIds = [];
foreach ($tickets as $i => [$auteur, $titre, $desc, $cat, $prio, $statut]) {
    $ts = gmdate('Y-m-d H:i:s', time() - (count($tickets) - $i) * 3600 * 6);   // dates échelonnées, en UTC
    $insTicket->execute([':t' => $titre, ':d' => $desc, ':c' => $cat, ':p' => $prio, ':s' => $statut, ':u' => $auteur, ':ts1' => $ts, ':ts2' => $ts]);
    $ticketIds[] = (int) $pdo->lastInsertId();
}

// [index du ticket, contenu]
$replies = [
    [2, "Nous avons identifié une borne Wi-Fi surchargée au CDI. Un technicien intervient jeudi pour la remplacer."],
    [3, "Le renouvellement de la licence est en cours auprès de l'éditeur, retour prévu sous 48 h."],
    [4, "Souris remplacée par un modèle neuf, merci de confirmer que tout fonctionne."],
    [5, "Votre compte a été déverrouillé. Pensez à changer votre mot de passe à la prochaine connexion."],
    [10, "Le certificat du VPN a expiré. Nous le renouvelons et vous prévenons dès que c'est rétabli."],
    [12, "Dalle tactile recalibrée, le problème était logiciel. Le ticket est clos."],
];
$insReply = $pdo->prepare('INSERT INTO reponses (ticket_id, user_id, contenu) VALUES (:t, :u, :c)');
foreach ($replies as [$index, $contenu]) {
    $insReply->execute([':t' => $ticketIds[$index], ':u' => $admin, ':c' => $contenu]);
}
$pdo->commit();

echo count($accounts) . ' comptes, ' . count($tickets) . ' tickets et ' . count($replies) . " réponses créés.\n";
