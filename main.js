const { app, BrowserWindow } = require('electron');
const { spawn } = require('child_process');
const express = require('express');
const ffmpeg = require('fluent-ffmpeg');
const path = require('path');
const tcpPortUsed = require('tcp-port-used');
const fs = require('fs');

// CONFIGURATION
const PHP_PORT = 8000;
const STREAM_PORT = 10000;
let phpServer;
let mainWindow;

// 1. DÉMARRAGE DU SERVEUR PHP (UI)
function resolvePhpPath() {
    const possiblePaths = [
        '/opt/homebrew/bin/php', // Apple Silicon Homebrew (Votre cas)
        '/usr/local/bin/php',    // Intel Homebrew
        '/usr/bin/php',          // System (Rare sur macOS récents)
        '/Applications/MAMP/bin/php/php8.1.0/bin/php', // MAMP Example fallback
        'php'                    // Fallback PATH global
    ];

    for (const p of possiblePaths) {
        if (p === 'php') return p; // Si on arrive là, on tente le PATH par défaut
        if (fs.existsSync(p)) return p;
    }
    return 'php';
}

async function startPhpServer() {
    console.log("🐘 Starting PHP Server...");

    // Vérifier si le port est libre
    const inUse = await tcpPortUsed.check(PHP_PORT, '127.0.0.1');
    if (inUse) {
        console.log("⚠️ Port 8000 already in use. Assuming external PHP server or zombie process.");
        return;
    }

    const phpPath = resolvePhpPath();
    console.log(`🐘 PHP Executable found at: ${phpPath}`);

    phpServer = spawn(phpPath, ['-S', `0.0.0.0:${PHP_PORT}`, '-t', __dirname]);

    phpServer.stdout.on('data', (data) => console.log(`[PHP] ${data}`));
    phpServer.stderr.on('data', (data) => console.error(`[PHP ERR] ${data}`));

    phpServer.on('close', (code) => {
        console.log(`[PHP] Server exited with code ${code}`);
    });
}

// 2. DÉMARRAGE DU STREAMER NODE.JS (MOTEUR VIDÉO)
function startStreamServer() {
    const server = express();

    // Endpoint de Streaming
    server.get('/stream', (req, res) => {
        const url = req.query.url; // L'URL source (encodée base64 ou brute, à voir)
        const start = req.query.start || 0;

        if (!url) {
            return res.status(400).send("No URL provided");
        }

        let decodedUrl;
        try {
            // On tente de décoder si c'est du base64, sinon on prend brut
            decodedUrl = Buffer.from(url, 'base64').toString('utf-8');
            if (!decodedUrl.startsWith('http')) throw new Error('Not URL');
        } catch (e) {
            decodedUrl = url;
            console.warn('⚠️ URL decoding failed or raw URL used.');
        }

        console.log(`🚀 [NODE STREAM] Request: Start=${start}s`);

        //HEADERS
        // Important: On dit au navigateur que c'est un FLUX CONTINU (pas de longueur finie)
        res.writeHead(200, {
            'Content-Type': 'video/webm', // WebM pour VP8
            'Transfer-Encoding': 'chunked',
            'Accept-Ranges': 'none',
            'Cache-Control': 'no-cache',
            'Connection': 'keep-alive',
            'Access-Control-Allow-Origin': '*'
        });

        // FFMPEG NATIVE (Pas de proxy PHP instable !)
        const ffmpegCmd = ffmpeg(decodedUrl)
            .inputOptions([
                '-reconnect', '1',
                '-reconnect_streamed', '1',
                '-reconnect_delay_max', '5',
                '-rw_timeout', '15000000', // 15s timeout
                '-user_agent', 'Mozilla/5.0',
                (start > 0) ? `-ss ${start}` : null
            ].filter(Boolean)) // Enlever les nulls
            .videoCodec('libvpx')
            .videoBitrate('3000k') // Safe HD
            .audioCodec('libvorbis')
            .audioBitrate('128k')
            .outputOptions([
                '-quality', 'realtime',
                '-cpu-used', '8',
                '-f', 'webm', // WebM est le plus robuste pour le streaming
                '-deadline', 'realtime',
                '-ac', '2'
            ])
            .on('error', (err) => {
                // Si le client ferme la connexion, ce n'est pas une "vraie" erreur serveur
                if (!err.message.includes('Output stream closed')) {
                    console.error(`❌ FFmpeg Error: ${err.message}`);
                }
            })
            .on('end', () => {
                // Stream finished
            });

        // PIPE DIRECT VERS LA RÉPONSE HTTP
        ffmpegCmd.pipe(res, { end: true });

        // Nettoyage si le client coupe
        req.on('close', () => {
            ffmpegCmd.kill('SIGKILL');
        });
    });

    server.listen(STREAM_PORT, () => {
        console.log(`⚡ Node Streamer running on http://localhost:${STREAM_PORT}`);
    });
}

// 3. FENÊTRE ELECTRON
function createWindow() {
    mainWindow = new BrowserWindow({
        width: 1280,
        height: 720,
        backgroundColor: '#000', // Noir pour le cinéma
        webPreferences: {
            nodeIntegration: false, // Sécurité
            contextIsolation: true
        }
    });

    // On charge l'interface PHP
    // On attend un peu que PHP démarre
    setTimeout(() => {
        mainWindow.loadURL(`http://localhost:${PHP_PORT}/`);
    }, 1000);
}

//CYCLE DE VIE
app.whenReady().then(async () => {
    await startPhpServer();
    startStreamServer();
    createWindow();

    app.on('activate', () => {
        if (BrowserWindow.getAllWindows().length === 0) createWindow();
    });
});

app.on('window-all-closed', () => {
    if (process.platform !== 'darwin') app.quit();
    // Kill PHP on exit
    if (phpServer) phpServer.kill();
});

app.on('before-quit', () => {
    if (phpServer) phpServer.kill();
});
