<?php
$host = 'localhost';
$user = 'root';
$pass = 'root'; // MAMP default
$dbname = 'iptv_player';

try {
    // Connect without database selected
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create database
    $sql = "CREATE DATABASE IF NOT EXISTS $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    $pdo->exec($sql);
    echo "Base de données '$dbname' créée ou déjà existante.\n";

} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
    echo "Vérifiez vos identifiants MySQL (root/root) dans setup_db.php.\n";
    exit(1);
}
