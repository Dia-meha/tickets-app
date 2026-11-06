<?php
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';

$user = current_user();
redirect($user ? home_for($user) : '/login.php');
