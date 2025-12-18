<?php

namespace App\Controllers;

class ProxyController
{

    public function transcode()
    {
        // Stop timeout
        set_time_limit(0);

        // Close session immediately to prevent blocking concurrent requests (like save progress)
        if (session_id()) {
            session_write_close();
        }

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
        // Headers will be set later based on User-Agent
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

        // DÉBUT DE LA LOGIQUE UNIFIÉE ET STABILISÉE
        $seekParam = ($startTime > 0) ? "-ss $startTime" : "";

        // Flags réseau ultra-robustes pour éviter les coupures (Input I/O Error)
        // -rw_timeout 15000000 : 15 secondes de patience avant timeout ( vital pour sources lentes )
        $networkFlags = "-reconnect 1 -reconnect_streamed 1 -reconnect_delay_max 5 -rw_timeout 15000000 -user_agent \"Mozilla/5.0\"";

        // DETECTION NAVIGATEUR (Firefox vs Chrome/Safari)
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $isFirefox = (strpos($ua, 'Firefox') !== false);

        if ($isFirefox) {
            // FIREFOX : VP8 + Vorbis en WebM (100% Natif et Stable)
            // On abandonne H.264 pour Firefox car il cause soit des "Byte Range Error" (MP4), soit "Infinite Load" (MKV).
            // VP8 est le format de prédilection de Firefox.

            // -cpu-used 5 : Compromis vitesse/qualité pour le temps réel
            // -b:v 3M : Sécurité maximale pour éviter les coupures
            // -g 48 : Keyframe tous les 2s (Fast Start)
            $videoCodec = "-c:v libvpx -quality realtime -cpu-used 8 -b:v 3M -g 48 -qmin 10 -qmax 42";
            $audioCodec = "-c:a libvorbis -b:a 128k";
            $outputFlags = "-f webm";

            header('Content-Type: video/webm');
        } else {
            // CHROME / SAFARI : H.264 + AAC en MP4 (Performance Max)
            // On garde la config qui marche parfaitement sur Chrome.
            // Mode "Safe HD" : 3M bitrate pour ne pas provoquer de coupure source.

            $videoCodec = "-c:v libx264 -preset ultrafast -profile:v baseline -level 3.0 -pix_fmt yuv420p -maxrate 6M -bufsize 12M -b:v 3M -g 48";
            $audioCodec = "-c:a aac -ac 2 -b:a 128k";
            $outputFlags = "-f mp4 -movflags frag_keyframe+empty_moov+default_base_moof";

            header('Content-Type: video/mp4');
        }

        // 4. COMMANDE FINALE
        // -sn : Pas de sous-titres (cause de crash fréquente)
        $cmd = "ffmpeg $networkFlags $seekParam -i " . escapeshellarg($decodedUrl) . " $videoCodec $audioCodec -sn $outputFlags pipe:1 2>>" . escapeshellarg($logFile);

        // Log de la commande pour débug
        file_put_contents(__DIR__ . '/../../proxy_debug.txt', date('[Y-m-d H:i:s] ') . "CMD: $cmd" . PHP_EOL, FILE_APPEND);

        // Execution
        $fp = popen($cmd, 'r');

        if ($fp && is_resource($fp)) {
            while (!feof($fp)) {
                // Buffer augmenté à 64KB (au lieu de 8KB) pour fluidifier le pipe PHP > Apache
                echo fread($fp, 65536);
                flush();
            }
            pclose($fp);
        } else {
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - ERROR: Could not open process\n", FILE_APPEND);
        }
        exit;
    }
}
