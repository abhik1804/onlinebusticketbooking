<?php
/**
 * Database Connection Handler
 * Shreenathji Travels
 */

defined('IN_APP') or define('IN_APP', true);

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $config = require __DIR__ . '/../config/database.php';
        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset={$config['charset']}";
        try {
            $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
            // Ensure MySQL timezone aligns with Indian Standard Time
            $pdo->exec("SET time_zone = '+05:30'");
        } catch (PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            die("Database Connection Error. Please verify your MySQL server is running in XAMPP.");
        }
    }
    return $pdo;
}
