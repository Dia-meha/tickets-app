<?php
/**
 * Liste dynamique (recherche + filtres via fetch).
 * @var bool $showAuthor colonne « Auteur » (administrateur)
 */
?>
<form id="ticket-filters" class="card card-body mb-3" role="search" aria-label="Recherche et filtres"
      data-endpoint="/api/tickets.php" data-show-author="<?= $showAuthor ? '1' : '0' ?>">
    <div class="row g-3 align-items-end">
        <div class="col-12 col-lg-4">
            <label for="q" class="form-label mb-1">Recherche par titre</label>
            <input type="search" class="form-control" id="q" name="q" maxlength="100" autocomplete="off">
        </div>
        <div class="col-6 col-lg-2">
            <label for="f-statut" class="form-label mb-1">Statut</label>
            <select class="form-select" id="f-statut" name="statut">
                <option value="">Tous</option>
                <?php foreach (STATUTS as $k => $l): ?><option value="<?= e($k) ?>"><?= e($l) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-lg-2">
            <label for="f-priorite" class="form-label mb-1">Priorité</label>
            <select class="form-select" id="f-priorite" name="priorite">
                <option value="">Toutes</option>
                <?php foreach (PRIORITES as $k => $l): ?><option value="<?= e($k) ?>"><?= e($l) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-lg-2">
            <label for="f-categorie" class="form-label mb-1">Catégorie</label>
            <select class="form-select" id="f-categorie" name="categorie">
                <option value="">Toutes</option>
                <?php foreach (CATEGORIES as $k => $l): ?><option value="<?= e($k) ?>"><?= e($l) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-lg-2 d-grid">
            <button type="reset" class="btn btn-outline-secondary">Réinitialiser</button>
        </div>
    </div>
</form>

<noscript><div class="alert alert-warning">JavaScript est nécessaire pour afficher et filtrer la liste des tickets.</div></noscript>

<p id="ticket-count" class="text-muted small mb-2" role="status" aria-live="polite"></p>

<div id="ticket-loading" class="text-center py-4" hidden role="status">
    <div class="spinner-border text-primary" aria-hidden="true"></div>
    <span class="ms-2">Chargement des tickets…</span>
</div>
<div id="ticket-empty" class="alert alert-info" hidden>Aucun ticket ne correspond à votre recherche.</div>
<div id="ticket-error" class="alert alert-danger d-flex flex-wrap align-items-center justify-content-between gap-2" hidden role="alert">
    <span>Impossible de charger les tickets. Vérifiez votre connexion puis réessayez.</span>
    <button type="button" class="btn btn-sm btn-outline-danger" id="ticket-retry">Réessayer</button>
</div>

<div id="ticket-table" class="table-responsive card" hidden>
    <table class="table table-hover align-middle mb-0">
        <caption class="visually-hidden">Liste des tickets</caption>
        <thead class="table-light">
            <tr>
                <th scope="col">N°</th>
                <th scope="col">Titre</th>
                <th scope="col">Catégorie</th>
                <th scope="col">Priorité</th>
                <th scope="col">Statut</th>
                <?php if ($showAuthor): ?><th scope="col">Auteur</th><?php endif; ?>
                <th scope="col">Mis à jour</th>
                <th scope="col" class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody id="ticket-rows"></tbody>
    </table>
</div>

<?php if (!$showAuthor) { require __DIR__ . '/delete_modal.php'; } ?>
