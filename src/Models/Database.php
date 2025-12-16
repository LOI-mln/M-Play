<?php
namespace App\Models;

use PDO;
use PDOException;

class Database
{
    private static $instance = null;
    private $pdo;

    private $host = 'localhost'; // Should ideally be in env config
    private $db = 'iptv_player'; // Default name, user might need to change
    private $user = 'root';
    private $pass = 'root'; // Default MAMP password
    private $charset = 'utf8mb4';

    private function __construct()
    {
        $dsn = "mysql:host=$this->host;dbname=$this->db;charset=$this->charset";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        try {
            $this->pdo = new PDO($dsn, $this->user, $this->pass, $options);
        } catch (PDOException $e) {
            // simpler handling for now
            throw new PDOException($e->getMessage(), (int) $e->getCode());
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection()
    {
        return $this->pdo;
    }
}
