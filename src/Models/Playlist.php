<?php
namespace App\Models;

use PDO;

class Playlist
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
        $this->createTableIfNotExists();
    }

    private function createTableIfNotExists()
    {
        // On crée la table si elle existe pas déjà
        $sql = "CREATE TABLE IF NOT EXISTS playlists (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) DEFAULT 'Playlist',
            host VARCHAR(255) NOT NULL,
            username VARCHAR(255) NOT NULL,
            password VARCHAR(255) NOT NULL,
            is_active BOOLEAN DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $this->pdo->exec($sql);
    }

    public function creer($hote, $utilisateur, $motDePasse)
    {
        $stmt = $this->pdo->prepare("INSERT INTO playlists (host, username, password) VALUES (:host, :username, :password)");
        return $stmt->execute([
            'host' => $hote,
            'username' => $utilisateur,
            'password' => $motDePasse
        ]);
    }

    public function recupererActif()
    {
        $stmt = $this->pdo->query("SELECT * FROM playlists WHERE is_active = 1 LIMIT 1");
        return $stmt->fetch();
    }
}
