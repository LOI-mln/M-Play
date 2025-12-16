<?php
// Vue pour le lecteur SÉRIES (Similaire à films mais adapté)
// On utilise le même JS player_vod.js car la logique de lecture est identique (HLS -> Fallback -> Transcode)

// On peut ajouter ici une logique "Épisode Suivant" plus tard si on passe les IDs des épisodes suivants en GET ou Session.
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lecture Épisode - M-Play</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700;800&family=Outfit:wght@300;400;500;700;900&display=swap"
        rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #000;
        }
    </style>
</head>

<body class="h-screen w-screen overflow-hidden flex flex-col bg-black">

    <!-- La Vidéo -->
    <div class="relative w-full h-full bg-black overflow-hidden select-none" id="conteneur-video">
        <!-- Header (Retour) -->
        <div id="player-header"
            class="absolute top-0 left-0 w-full p-6 bg-gradient-to-b from-black/90 to-transparent z-50 transition-opacity duration-500 opacity-100 flex justify-between items-start">
            <a href="javascript:history.back()"
                class="text-white hover:text-red-600 transition flex items-center gap-2 font-bold uppercase tracking-wider w-fit">
                <span>&larr;</span> Retour à la série
            </a>
        </div>

        <!-- Video -->
        <video id="lecteur-video" class="w-full h-full object-contain">
            <!-- Source will be set by JS -->
        </video>

        <!-- Overlay de chargement -->
        <div id="chargement" class="absolute inset-0 flex items-center justify-center bg-black z-40 hidden">
            <div class="animate-spin rounded-full h-16 w-16 border-t-4 border-b-4 border-red-600"></div>
        </div>

        <!-- Big Play Button (Initial) -->
        <div id="overlay-play"
            class="absolute inset-0 flex items-center justify-center z-30 bg-black/40 backdrop-blur-sm cursor-pointer transition-opacity duration-300">
            <div
                class="bg-red-600 text-white rounded-full p-6 shadow-lg shadow-red-900/50 hover:scale-110 transition-transform">
                <svg class="w-16 h-16" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M8 5v14l11-7z" />
                </svg>
            </div>
        </div>

        <!-- Contrôles Custom -->
        <div id="player-controls"
            class="absolute bottom-0 left-0 w-full z-50 transition-opacity duration-500 opacity-100">

            <!-- Gradient Background -->
            <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/60 to-transparent"></div>

            <div class="relative px-6 pb-6 pt-4 flex flex-col gap-1">

                <!-- Controls Row -->
                <div class="flex items-center gap-4">

                    <!-- Play/Pause -->
                    <button id="btn-lecture" class="text-white hover:text-red-600 transition transform hover:scale-110">
                        <svg id="icone-play" class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M8 5v14l11-7z" />
                        </svg>
                        <svg id="icone-pause" class="w-8 h-8 hidden" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z" />
                        </svg>
                    </button>

                    <!-- Volume -->
                    <div class="flex items-center gap-2 group/vol">
                        <button id="btn-mute" class="text-white hover:text-red-600 transition">
                            <svg id="vol-high" class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z" />
                            </svg>
                            <svg id="vol-mute" class="w-6 h-6 hidden" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M16.5 12c0-1.77-1.02-3.29-2.5-4.03v2.21l2.45 2.45c.03-.2.05-.41.05-.63zm2.5 0c0 .94-.2 1.82-.54 2.64l1.51 1.51C20.63 14.91 21 13.5 21 12c0-4.28-2.99-7.86-7-8.77v2.06c2.89.86 5 3.54 5 6.71zM4.27 3L3 4.27 7.73 9H3v6h4l5 5v-6.73l4.25 4.25c-.67.52-1.42.93-2.25 1.18v2.06c1.38-.31 2.63-.95 3.69-1.81L19.73 21 21 19.73l-9-9L4.27 3zM12 4L9.91 6.09 12 8.18V4z" />
                            </svg>
                        </button>
                        <input type="range" id="volume-slider" min="0" max="1" step="0.1" value="1"
                            class="w-0 overflow-hidden group-hover/vol:w-20 transition-all duration-300 accent-red-600 h-1 bg-gray-700 rounded-lg appearance-none cursor-pointer">
                    </div>

                    <!-- Seek Bar (Moved Here - Flex Grow) -->
                    <div class="flex-grow flex items-center gap-3 group/seek relative">
                        <span id="time-current"
                            class="text-xs text-gray-300 font-sans font-bold w-12 text-right">00:00</span>

                        <div class="relative flex-grow h-1 bg-gray-600 rounded-full cursor-pointer hover:h-1.5 transition-all"
                            id="seek-container">
                            <!-- Tooltip Time Hover -->
                            <div id="time-tooltip"
                                class="absolute bottom-4 -translate-x-1/2 bg-black/80 text-white text-xs font-sans font-bold py-1 px-2 rounded opacity-0 transition-opacity pointer-events-none z-20 whitespace-nowrap">
                                00:00</div>

                            <div class="absolute inset-0 rounded-full overflow-hidden">
                                <div id="progress-bar" class="absolute top-0 left-0 h-full bg-red-600 w-0"></div>
                            </div>
                            <div id="thumb"
                                class="absolute top-1/2 -translate-y-1/2 w-3 h-3 bg-white rounded-full shadow opacity-0 group-hover/seek:opacity-100 transition-opacity"
                                style="left: 0%"></div>
                            <input type="range" id="seek-slider" min="0" max="100" step="0.1" value="0"
                                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                        </div>
                        <span id="time-duration" class="text-xs text-gray-500 font-sans font-bold w-12">00:00</span>
                    </div>

                    <!-- Plein écran -->
                    <button id="btn-plein-ecran" class="text-white hover:text-red-600 transition">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M7 14H5v5h5v-2H7v-3zm-2-4h2V7h3V5H5v5zm12 7h-3v2h5v-5h-2v3zM14 5v2h3v3h2V5h-5z" />
                        </svg>
                    </button>

                </div>
            </div>
        </div>
    </div>

    <!-- HLS.js -->
    <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>

    <!-- Config Variables Transfer -->
    <script>
        window.VodConfig = {
            streamUrlHls: '<?= $streamUrlHls ?>',
            streamUrlDirect: '<?= $streamUrlDirect ?>',
            streamUrlTranscode: '<?= $streamUrlTranscode ?>',
            duration: '<?= $duration ?? '' ?>'
        };
    </script>

    <!-- Player Logic (Reused from Movies/VOD) -->
    <script src="/public/js/player_vod.js"></script>
</body>

</html>