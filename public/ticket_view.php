<?php
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';

$user = require_login();
$id = int_param('id');
$ticket = load_ticket_or_abort($id, $user);   // 403 si le ticket d'un autre utilisateur
$replies = TicketRepository::replies($id);

$isAdmin = $user['role'] === 'admin';
$canEdit = !$isAdmin && (int) $ticket['user_id'] === $user['id'] && $ticket['statut'] === 'ouvert';
$nextStatus = STATUT_SUIVANT[$ticket['statut']] ?? null;

$pageTitle = 'Ticket #' . $id;
require __DIR__ . '/../src/views/header.php';
?>
<a class="btn btn-link px-0 mb-2" href="<?= $isAdmin ? '/admin.php' : '/tickets.php' ?>">← Retour à la liste</a>

<div class="row g-4">
    <div class="col-lg-8">
        <article class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                    <h1 class="h4 mb-0"><span class="text-muted">#<?= (int) $id ?></span> <?= e($ticket['titre']) ?></h1>
                    <div class="d-flex gap-1"><?= badge('priorite', $ticket['priorite']) ?> <?= badge('statut', $ticket['statut']) ?></div>
                </div>
                <p class="text-muted small">
                    <?= e(CATEGORIES[$ticket['categorie']] ?? $ticket['categorie']) ?> ·
                    par <?= e($ticket['auteur']) ?> ·
                    créé le <?= e(format_date($ticket['created_at'])) ?> ·
                    modifié le <?= e(format_date($ticket['updated_at'])) ?>
                </p>
                <p class="ticket-description"><?= e($ticket['description']) ?></p>

                <?php if ($canEdit): ?>
                    <div class="d-flex flex-wrap gap-2">
                        <a class="btn btn-outline-primary" href="/ticket_edit.php?id=<?= (int) $id ?>">Modifier</a>
                        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal"
                                data-ticket-id="<?= (int) $id ?>" data-ticket-title="<?= e($ticket['titre']) ?>">Supprimer</button>
                    </div>
                <?php elseif (!$isAdmin && $ticket['statut'] !== 'ouvert'): ?>
                    <p class="text-muted small mb-0">Ce ticket n'est plus ouvert : il ne peut plus être modifié ni supprimé.</p>
                <?php endif; ?>
            </div>
        </article>

        <section class="mt-4" aria-labelledby="reponses-titre">
            <h2 class="h5" id="reponses-titre">Réponses (<?= count($replies) ?>)</h2>
            <?php if (!$replies): ?>
                <p class="text-muted">Aucune réponse pour le moment.</p>
            <?php endif; ?>
            <?php foreach ($replies as $r): ?>
                <div class="card mb-2 reply">
                    <div class="card-body py-2">
                        <p class="small text-muted mb-1"><?= e($r['auteur']) ?> · <?= e(format_date($r['created_at'])) ?></p>
                        <p class="ticket-description mb-0"><?= e($r['contenu']) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </section>
    </div>

    <?php if ($isAdmin): ?>
    <aside class="col-lg-4" aria-label="Traitement du ticket">
        <div class="card mb-3">
            <div class="card-body">
                <h2 class="h6">Priorité</h2>
                <form method="post" action="/ticket_admin.php" class="d-flex gap-2">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int) $id ?>">
                    <input type="hidden" name="action" value="priorite">
                    <label for="priorite" class="visually-hidden">Priorité</label>
                    <select class="form-select" id="priorite" name="priorite">
                        <?php foreach (PRIORITES as $k => $l): ?>
                            <option value="<?= e($k) ?>"<?= $ticket['priorite'] === $k ? ' selected' : '' ?>><?= e($l) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-primary" type="submit">Enregistrer</button>
                </form>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <h2 class="h6">Statut</h2>
                <?php if ($nextStatus): ?>
                    <form method="post" action="/ticket_admin.php">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int) $id ?>">
                        <input type="hidden" name="action" value="statut">
                        <button class="btn btn-warning w-100" type="submit">Passer à « <?= e(STATUTS[$nextStatus]) ?> »</button>
                    </form>
                <?php else: ?>
                    <p class="text-muted small mb-0">Ticket résolu : plus de changement de statut possible.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h2 class="h6">Ajouter une réponse</h2>
                <form method="post" action="/ticket_admin.php" data-validate novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int) $id ?>">
                    <input type="hidden" name="action" value="reponse">
                    <div class="mb-2">
                        <label for="contenu" class="form-label visually-hidden">Réponse</label>
                        <textarea class="form-control" id="contenu" name="contenu" rows="5" required
                                  data-label="Réponse" data-min="2" data-max="2000"
                                  aria-describedby="contenu-feedback contenu-counter"></textarea>
                        <div class="form-text" id="contenu-counter" data-counter-for="contenu" data-max="2000">0 / 2000 caractères</div>
                        <div class="invalid-feedback" id="contenu-feedback"></div>
                    </div>
                    <button class="btn btn-primary w-100" type="submit">Envoyer la réponse</button>
                </form>
            </div>
        </div>
    </aside>
    <?php endif; ?>
</div>

<?php
if ($canEdit) {
    require __DIR__ . '/../src/views/delete_modal.php';
}
require __DIR__ . '/../src/views/footer.php';
