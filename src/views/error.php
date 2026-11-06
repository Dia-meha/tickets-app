<?php
/** @var int $errorCode  @var string $errorMessage  @var string $pageTitle */
require __DIR__ . '/header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="alert alert-danger" role="alert">
            <h1 class="h4 alert-heading"><?= e($pageTitle) ?> (<?= (int) $errorCode ?>)</h1>
            <p class="mb-0"><?= e($errorMessage) ?></p>
        </div>
        <a class="btn btn-primary" href="/">Retour à l'accueil</a>
    </div>
</div>
<?php require __DIR__ . '/footer.php'; ?>
