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
        // -strict experimental : Pour d'anciens encoders AAC

        // -user_agent : Spoof browser to avoid blocking
        // -reconnect 1 : Auto reconnect on drop
        // -reconnect_at_eof 1 : Reconnect at end if incomplete
        // -reconnect_streamed 1 : For streams
        // -reconnect_delay_max 2 : Fast reconnect
        $cmd = "ffmpeg -user_agent \"Mozilla/5.0 (Windows NT 10.0; Win64; x64)\" -reconnect 1 -reconnect_at_eof 1 -reconnect_streamed 1 -reconnect_delay_max 2 -i " . escapeshellarg($decodedUrl) . " -c:v copy -c:a aac -b:a 192k -ac 2 -f mp4 -movflags frag_keyframe+empty_moov pipe:1 2>>" . escapeshellarg($logFile);

        file_put_contents($logFile, date('Y-m-d H:i:s') . " - CMD: $cmd\n", FILE_APPEND);

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
