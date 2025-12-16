<?php

namespace App\Controllers;

class ProxyController
{

    public function transcode()
    {
        // Stop timeout
        set_time_limit(0);

        $url = $_GET['url'] ?? '';

        if (empty($url)) {
            http_response_code(400);
            die('URL manquante');
        }

        // Validation basique
        $decodedUrl = base64_decode($url);

        // DEBUG: Log
        $logFile = __DIR__ . '/../../proxy_debug.txt';
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - Request: $url\n", FILE_APPEND);
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - Decoded: $decodedUrl\n", FILE_APPEND);

        // Configuration headers pour streaming
        header('Content-Type: video/mp4');
        header('Cache-Control: no-cache');

        // Désactiver le buffering PHP pour le streaming
        if (ob_get_level())
            ob_end_clean();

        // Commande FFmpeg
        // On redirige stderr vers stdout pour le debug dans le log si besoin, 
        // ou on le capture séparément. Ici on loggue la commande.
        // -ac 2 : Force le downmix Stéréo (fixes 5.1 AAC compatibility issues)
        // -strict experimental        // Paramètre de début (Seek) en secondes
        $startTime = isset($_GET['start']) ? (int) $_GET['start'] : 0;

        // Construction de la commande FFmpeg
        // -ss avant -i pour un seek rapide (input seeking)
        $seekParam = $startTime > 0 ? "-ss $startTime" : "";

        // Commande optimisée :
        // -copyts : Copie les timestamps (important pour le seek ?) - Test sans pour l'instant
        // -c:v copy : On ne re-encode PAS la video (CPU save), on la copie juste.
        // -c:a aac : On re-encode l'audio en AAC stéréo (Le fix).
        // -movflags frag_keyframe+empty_moov : Pour le streaming MP4 fragmenté.
        $cmd = "ffmpeg $seekParam -reconnect 1 -reconnect_streamed 1 -reconnect_delay_max 5 -user_agent \"Mozilla/5.0\" -i " . escapeshellarg($decodedUrl) . " -c:v copy -c:a aac -ac 2 -f mp4 -movflags frag_keyframe+empty_moov pipe:1 2>>" . escapeshellarg($logFile);

        // Log de la commande pour débug
        file_put_contents(__DIR__ . '/../../proxy_debug.txt', date('[Y-m-d H:i:s] ') . "CMD: $cmd" . PHP_EOL, FILE_APPEND);

        // Execution
        $fp = popen($cmd, 'r');

        if ($fp && is_resource($fp)) {
            while (!feof($fp)) {
                echo fread($fp, 8192);
                flush();
            }
            pclose($fp);
        } else {
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - ERROR: Could not open process\n", FILE_APPEND);
        }
        exit;
    }
}
