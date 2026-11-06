<?php
/**
 * Formulaire partagé création / modification.
 * @var array  $values  titre, description, categorie
 * @var array  $errors  erreurs serveur par champ
 * @var string $action  URL de traitement
 * @var string $submitLabel
 * @var int    $ticketId 0 pour une création
 */
$field = fn (string $k): string => $values[$k] ?? '';
$cls = fn (string $k): string => isset($errors[$k]) ? ' is-invalid' : '';
?>
<form method="post" action="<?= e($action) ?>" data-validate novalidate>
    <?= csrf_field() ?>
    <?php if ($ticketId > 0): ?><input type="hidden" name="id" value="<?= (int) $ticketId ?>"><?php endif; ?>

    <div class="mb-3">
        <label for="titre" class="form-label">Titre <span class="text-danger" aria-hidden="true">*</span></label>
        <input type="text" class="form-control<?= $cls('titre') ?>" id="titre" name="titre" required
               data-label="Titre" data-min="5" data-max="100" value="<?= e($field('titre')) ?>"
               aria-describedby="titre-feedback titre-help">
        <div class="form-text" id="titre-help">Entre 5 et 100 caractères.</div>
        <div class="invalid-feedback" id="titre-feedback"><?= e($errors['titre'] ?? '') ?></div>
    </div>

    <div class="mb-3">
        <label for="categorie" class="form-label">Catégorie <span class="text-danger" aria-hidden="true">*</span></label>
        <select class="form-select<?= $cls('categorie') ?>" id="categorie" name="categorie" required
                data-label="Catégorie" aria-describedby="categorie-feedback">
            <option value="">— Choisir —</option>
            <?php foreach (CATEGORIES as $key => $label): ?>
                <option value="<?= e($key) ?>"<?= $field('categorie') === $key ? ' selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
        <div class="invalid-feedback" id="categorie-feedback"><?= e($errors['categorie'] ?? '') ?></div>
    </div>

    <div class="mb-3">
        <label for="description" class="form-label">Description <span class="text-danger" aria-hidden="true">*</span></label>
        <textarea class="form-control<?= $cls('description') ?>" id="description" name="description" rows="7" required
                  data-label="Description" data-min="20" data-max="2000"
                  aria-describedby="description-feedback description-counter"><?= e($field('description')) ?></textarea>
        <div class="form-text" id="description-counter" data-counter-for="description" data-max="2000">0 / 2000 caractères</div>
        <div class="invalid-feedback" id="description-feedback"><?= e($errors['description'] ?? '') ?></div>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <button type="submit" class="btn btn-primary"><?= e($submitLabel) ?></button>
        <a class="btn btn-outline-secondary" href="<?= $ticketId > 0 ? '/ticket_view.php?id=' . (int) $ticketId : '/tickets.php' ?>">Annuler</a>
    </div>
</form>
