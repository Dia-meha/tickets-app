<?php
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';

if ($user = current_user()) {
    redirect(home_for($user));
}

$error = '';
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = str_param('email');
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';

    if ($email === '' || $password === '') {
        $error = 'Veuillez renseigner votre e-mail et votre mot de passe.';
    } elseif (attempt_login($email, $password)) {
        redirect(home_for(current_user()));
    } else {
        $error = 'Identifiants incorrects.';
    }
}

$pageTitle = 'Connexion';
require __DIR__ . '/../src/views/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h3 mb-3">Connexion</h1>
                <?php if (($_GET['bye'] ?? '') === '1'): ?>
                    <div class="alert alert-success" role="status">Vous avez été déconnecté.</div>
                <?php endif; ?>
                <?php if ($error !== ''): ?>
                    <div class="alert alert-danger" role="alert"><?= e($error) ?></div>
                <?php endif; ?>
                <form method="post" action="/login.php" data-validate novalidate>
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label for="email" class="form-label">Adresse e-mail</label>
                        <input type="email" class="form-control" id="email" name="email" required autocomplete="username"
                               data-label="Adresse e-mail" value="<?= e($email) ?>" aria-describedby="email-feedback">
                        <div class="invalid-feedback" id="email-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Mot de passe</label>
                        <input type="password" class="form-control" id="password" name="password" required
                               autocomplete="current-password" data-label="Mot de passe" aria-describedby="password-feedback">
                        <div class="invalid-feedback" id="password-feedback"></div>
                    </div>
                    <div class="d-grid"><button type="submit" class="btn btn-primary">Se connecter</button></div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../src/views/footer.php'; ?>
