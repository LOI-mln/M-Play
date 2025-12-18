<?php
require __DIR__ . '/src/Models/Database.php';
require __DIR__ . '/src/Models/WatchProgress.php';

session_start();
// if (!isset($_SESSION['auth_creds']['username'])) {
//     die("Not logged in.");
// }

// $user = $_SESSION['auth_creds']['username'];
$user = 'milan'; // Hardcode known user or wildcard
echo "<h1>Debug Watch Progress (No Auth Check)</h1>";

$model = new \App\Models\WatchProgress();
$progress = $model->getInProgress($user, 'movie');

echo "<pre>";
print_r($progress);
echo "</pre>";

// Also try to list ALL to see if IDs match
$pdo = \App\Models\Database::getInstance()->getConnection();
$stmt = $pdo->query("SELECT * FROM watch_progress");
echo "<h2>Raw Table Dump:</h2>";
echo "<pre>";
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
echo "</pre>";
