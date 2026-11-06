<?php
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';

$user = require_login();
if ($user['role'] === 'admin') {
    redirect('/admin.php');
}

$showAuthor = false;
$pageTitle = 'Mes tickets';
require __DIR__ . '/../src/views/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h3 mb-0">Mes tickets</h1>
    <a class="btn btn-primary" href="/ticket_new.php">Nouveau ticket</a>
</div>
<?php
require __DIR__ . '/../src/views/ticket_list.php';
require __DIR__ . '/../src/views/footer.php';
