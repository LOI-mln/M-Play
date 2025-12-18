<?php
namespace App\Models;

use PDO;

class WatchProgress
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
        $this->createTableIfNotExists();
    }

    private function createTableIfNotExists()
    {
        $sql = "CREATE TABLE IF NOT EXISTS watch_progress (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id VARCHAR(255) NOT NULL,
            stream_id INT NOT NULL,
            type VARCHAR(50) DEFAULT 'movie',
            `current_time` INT DEFAULT 0,
            duration INT DEFAULT 0,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_seen (user_id, stream_id, type)
        )";
        $this->pdo->exec($sql);
    }

    public function save($userId, $streamId, $time, $duration, $type = 'movie')
    {
        // Seuil: Si on est à > 95% du film, on considère comme vu => on retire de la liste "reprendre" ?
        // Ou on laisse, l'utilisateur gèrera. Pour l'instant on update juste.

        $sql = "INSERT INTO watch_progress (user_id, stream_id, type, `current_time`, duration) 
                VALUES (:uid, :sid, :type, :time, :dur)
                ON DUPLICATE KEY UPDATE 
                `current_time` = :time_upd, duration = :dur_upd, updated_at = CURRENT_TIMESTAMP";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'uid' => $userId,
            'sid' => $streamId,
            'type' => $type,
            'time' => $time,
            'dur' => $duration,
            'time_upd' => $time,
            'dur_upd' => $duration
        ]);
    }

    public function getProgress($userId, $streamId, $type = 'movie')
    {
        $stmt = $this->pdo->prepare("SELECT `current_time`, duration FROM watch_progress WHERE user_id = :uid AND stream_id = :sid AND type = :type");
        $stmt->execute(['uid' => $userId, 'sid' => $streamId, 'type' => $type]);
        return $stmt->fetch();
    }

    public function getInProgress($userId, $type = 'movie')
    {
        // On récupère tout ce qui a été commencé (temps > 0) et pas fini/presque fini (ex: < 95%) 
        // Trié par date de mise à jour (le plus récent en premier)
        $sql = "SELECT * FROM watch_progress 
                WHERE user_id = :uid 
                AND type = :type 
                AND `current_time` > 60 -- Moins d'une minute, on ignore
                AND (`current_time` / duration) < 0.95 -- Pas encore fini
                ORDER BY updated_at DESC
                LIMIT 20";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid' => $userId, 'type' => $type]);
        return $stmt->fetchAll();
    }
}
