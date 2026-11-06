<?php
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';

require_post();   // la déconnexion n'est pas déclenchable par un simple lien GET
logout();
redirect('/login.php?bye=1');
