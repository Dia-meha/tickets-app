<?php
declare(strict_types=1);

/** Connexion PDO unique, partagée par toute l'application. */
final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            $c = (require __DIR__ . '/config.php')['db'];
            $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $c['host'], $c['name']);
            try {
                self::$pdo = new PDO($dsn, $c['user'], $c['password'], [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    // rowCount() = lignes trouvées (même si la valeur est inchangée)
                    PDO::MYSQL_ATTR_FOUND_ROWS   => true,
                    // Toutes les dates sont stockées en UTC
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '+00:00'",
                ]);
            } catch (PDOException $e) {
                // Jamais de message d'origine (il peut contenir des identifiants).
                error_log('[tickets] Connexion BDD impossible (code ' . $e->getCode() . ')');
                throw new RuntimeException('Connexion à la base de données impossible.');
            }
        }
        return self::$pdo;
    }
}
