<?php
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';

require_admin();

$showAuthor = true;
$pageTitle = 'Administration';
require __DIR__ . '/../src/views/header.php';
?>
<h1 class="h3 mb-3">Administration – tous les tickets</h1>
<?php
require __DIR__ . '/../src/views/ticket_list.php';
require __DIR__ . '/../src/views/footer.php';
