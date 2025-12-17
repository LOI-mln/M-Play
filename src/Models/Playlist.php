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
        $this->ensureNameColumn();
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

    private function ensureNameColumn()
    {
        try {
            $this->pdo->exec("ALTER TABLE playlists ADD COLUMN name VARCHAR(255) DEFAULT 'Playlist' AFTER id");
        } catch (\PDOException $e) {
            // Ignorer si la colonne existe déjà (Code erreur 42S21 ou générique)
        }
    }

    public function creer($hote, $utilisateur, $motDePasse, $nom = 'Playlist')
    {
        $nom = empty($nom) ? 'Playlist' : $nom;
        $stmt = $this->pdo->prepare("INSERT INTO playlists (name, host, username, password) VALUES (:name, :host, :username, :password)");
        return $stmt->execute([
            'name' => $nom,
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
