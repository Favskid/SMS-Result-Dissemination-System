<?php
/**
 * includes/db.php
 * Singleton PDO connection using MySQL (XAMPP).
 */

require_once dirname(__DIR__) . '/config.php';

function getDB(): PDO
{
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    try {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);

    } catch (PDOException $e) {
        die('<div style="font-family:sans-serif;color:red;padding:2rem">
               <h2>Database Error</h2>
               <p>' . htmlspecialchars($e->getMessage()) . '</p>
             </div>');
    }

    return $pdo;
}
