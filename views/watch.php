<?php
$modePleinEcran = true; // Pour dire au layout de virer le padding
ob_start();
?>

<div class="relative w-full h-full bg-black overflow-hidden select-none" id="conteneur-video">
    <!-- Header (Retour) -->
    <div id="player-header"
        class="absolute top-0 left-0 w-full p-6 bg-gradient-to-b from-black/90 to-transparent z-50 transition-opacity duration-500 opacity-100">
        <a href="/live"
            class="text-white hover:text-red-600 transition flex items-center gap-2 font-bold uppercase tracking-wider w-fit">
            <span>&larr;</span> Retour au Live
        </a>
    </div>

    <!-- La Vidéo -->
    <video id="lecteur-video" class="w-full h-full object-contain"></video>

    <!-- Overlay de chargement -->
    <div id="chargement" class="absolute inset-0 flex items-center justify-center bg-black z-40">
        <div class="animate-spin rounded-full h-16 w-16 border-t-4 border-b-4 border-red-600"></div>
    </div>

    <!-- Contrôles Custom (Barre du bas) -->
    <div id="player-controls"
        class="absolute bottom-0 left-0 w-full bg-gradient-to-t from-black/90 via-black/60 to-transparent px-6 py-6 transition-opacity duration-500 opacity-100 z-50 flex items-center gap-6">

        <!-- Play/Pause -->
        <button id="btn-lecture" class="text-white hover:text-red-600 transition transform hover:scale-110">
            <svg id="icone-play" class="w-10 h-10" fill="currentColor" viewBox="0 0 24 24">
                <path d="M8 5v14l11-7z" />
            </svg>
            <svg id="icone-pause" class="w-10 h-10 hidden" fill="currentColor" viewBox="0 0 24 24">
                <path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z" />
            </svg>
        </button>

        <!-- Volume -->
        <div class="flex items-center gap-2 group/vol">
            <button id="btn-mute" class="text-white hover:text-red-600 transition">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                    <path
                        d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z" />
                </svg>
            </button>
            <input type="range" id="volume-slider" min="0" max="1" step="0.1" value="1"
                class="w-0 overflow-hidden group-hover/vol:w-24 transition-all duration-300 accent-red-600 h-1 bg-gray-700 rounded-lg appearance-none cursor-pointer">
        </div>

        <!-- Espaceur -->
        <div class="flex-grow"></div>

        <!-- Indicateur DIRECT -->
        <div class="flex items-center gap-2">
            <div class="w-3 h-3 bg-red-600 rounded-full animate-pulse"></div>
            <span class="text-red-600 font-black tracking-widest uppercase text-sm">EN DIRECT</span>
        </div>

        <!-- Plein écran -->
        <button id="btn-plein-ecran" class="text-white hover:text-red-600 transition ml-4">
            <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                <path d="M7 14H5v5h5v-2H7v-3zm-2-4h2V7h3V5H5v5zm12 7h-3v2h5v-5h-2v3zM14 5v2h3v3h2V5h-5z" />
            </svg>
        </button>
    </div>
</div>

<!-- HLS.js -->
<script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
<script>
    // Variables
    const video = document.getElementById('lecteur-video');
    const conteneurVideo = document.getElementById('conteneur-video');
    const header = document.getElementById('player-header');
    const controls = document.getElementById('player-controls');
    const chargement = document.getElementById('chargement');
    const btnLecture = document.getElementById('btn-lecture');
    const iconePlay = document.getElementById('icone-play');
    const iconePause = document.getElementById('icone-pause');
    const btnPleinEcran = document.getElementById('btn-plein-ecran');
    const btnMute = document.getElementById('btn-mute');
    const sliderVolume = document.getElementById('volume-slider');

    let timerInactivite;

    // Fonctionnalité Auto-Hide (Disparition souris + contrôles)
    function cacherControles() {
        if (!video.paused) { // On ne cache pas si la vidéo est en pause
            header.style.opacity = '0';
            controls.style.opacity = '0';
            conteneurVideo.style.cursor = 'none'; // Cache la souris
        }
    }

    function montrerControles() {
        header.style.opacity = '1';
        controls.style.opacity = '1';
        conteneurVideo.style.cursor = 'default';

        clearTimeout(timerInactivite);
        timerInactivite = setTimeout(cacherControles, 3000); // Disparaît après 3s d'inactivité
    }

    // On écoute les mouvements dans le conteneur
    conteneurVideo.addEventListener('mousemove', montrerControles);
    conteneurVideo.addEventListener('click', montrerControles);

    // Initialisation HLS
    const urlSource = '<?= $urlFluxM3u8 ?>';

    if (Hls.isSupported()) {
        const hls = new Hls();
        hls.loadSource(urlSource);
        hls.attachMedia(video);
        hls.on(Hls.Events.MANIFEST_PARSED, function () {
            demarrerLecture();
        });
    } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
        video.src = urlSource;
        video.addEventListener('loadedmetadata', function () {
            demarrerLecture();
        });
    }

    function demarrerLecture() {
        video.play().then(() => {
            chargement.style.display = 'none';
            majBoutonLecture(true);
            montrerControles(); // On lance le timer au début
        }).catch(e => {
            console.log("Lecture auto bloquée");
            chargement.style.display = 'none';
        });
    }

    function basculerLecture() {
        if (video.paused) {
            video.play();
            majBoutonLecture(true);
            montrerControles();
        } else {
            video.pause();
            majBoutonLecture(false);
            montrerControles(); // On s'assure que les contrôles restent affichés en pause
        }
    }

    function majBoutonLecture(enLecture) {
        if (enLecture) {
            iconePlay.classList.add('hidden');
            iconePause.classList.remove('hidden');
        } else {
            iconePlay.classList.remove('hidden');
            iconePause.classList.add('hidden');
        }
    }

    btnLecture.addEventListener('click', (e) => {
        e.stopPropagation(); // Évite de trigger le clic sur la vidéo 2 fois
        basculerLecture();
    });

    video.addEventListener('click', basculerLecture);

    // Plein écran
    btnPleinEcran.addEventListener('click', (e) => {
        e.stopPropagation();
        if (!document.fullscreenElement) {
            conteneurVideo.requestFullscreen();
        } else {
            document.exitFullscreen();
        }
    });

    conteneurVideo.addEventListener('dblclick', () => {
        if (!document.fullscreenElement) {
            conteneurVideo.requestFullscreen();
        } else {
            document.exitFullscreen();
        }
    });

    // Volume
    sliderVolume.addEventListener('input', (e) => {
        video.volume = e.target.value;
        video.muted = false;
    });

    // Empêcher la propagation du clic sur la barre de contrôle (pour pas mettre pause quand on change le volume)
    controls.addEventListener('click', (e) => {
        e.stopPropagation();
    });

</script>

<?php $content = ob_get_clean(); ?>

<?php require __DIR__ . '/layout.php'; ?>