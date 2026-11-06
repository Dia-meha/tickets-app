<?php
declare(strict_types=1);

// Configuration lue dans les variables d'environnement (jamais de secret dans Git).
return [
    'db' => [
        'host'     => getenv('DB_HOST') ?: 'db',
        'name'     => getenv('DB_NAME') ?: '',
        'user'     => getenv('DB_USER') ?: '',
        'password' => getenv('DB_PASSWORD') ?: '',
    ],
];
