
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

// Initialisation HLS via Window Config
const { streamUrl } = window.LiveConfig;

if (Hls.isSupported()) {
    const hls = new Hls();
    hls.loadSource(streamUrl);
    hls.attachMedia(video);
    hls.on(Hls.Events.MANIFEST_PARSED, function () {
        demarrerLecture();
    });
} else if (video.canPlayType('application/vnd.apple.mpegurl')) {
    video.src = streamUrl;
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
