
const video = document.getElementById('lecteur-video');
const conteneurVideo = document.getElementById('conteneur-video');
const header = document.getElementById('player-header');
const controls = document.getElementById('player-controls');
const overlayPlay = document.getElementById('overlay-play');
const chargement = document.getElementById('chargement');

const btnLecture = document.getElementById('btn-lecture');
const iconePlay = document.getElementById('icone-play');
const iconePause = document.getElementById('icone-pause');

const btnMute = document.getElementById('btn-mute');
const volHigh = document.getElementById('vol-high');
const volMute = document.getElementById('vol-mute');
const sliderVolume = document.getElementById('volume-slider');

const btnPleinEcran = document.getElementById('btn-plein-ecran');

const seekSlider = document.getElementById('seek-slider');
const progressBar = document.getElementById('progress-bar');
const thumb = document.getElementById('thumb');
const timeCurrent = document.getElementById('time-current');
const timeDuration = document.getElementById('time-duration');

// Initialisation HLS via Window Config
const { streamUrlHls, streamUrlDirect, streamUrlTranscode } = window.VodConfig;

let hls;

function loadVideo() {
    if (Hls.isSupported()) {
        hls = new Hls();
        hls.loadSource(streamUrlHls);
        hls.attachMedia(video);

        hls.on(Hls.Events.MANIFEST_PARSED, function () {
            console.log("HLS found and parsed.");
        });

        hls.on(Hls.Events.ERROR, function (event, data) {
            if (data.fatal) {
                console.warn("HLS Fatal, switching to TRANSCODE (Auto-Fix)...");
                hls.destroy();
                loadTranscode(); // Fallback to Transcode directly
            }
        });
    } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
        // Safari
        video.src = streamUrlHls;
        video.addEventListener('error', () => {
            console.warn("Safari HLS failed, switching to TRANSCODE.");
            loadTranscode();
        }, { once: true });
    } else {
        // No HLS support -> Force Transcode
        loadTranscode();
    }
}

// Mode Direct (Backup manuel si besoin, mais on ne l'utilise plus en auto)
function loadDirect() {
    console.log("Loading Direct Source:", streamUrlDirect);
    if (hls) hls.destroy();
    video.src = streamUrlDirect;
    video.load();
}

function loadTranscode() {
    console.log("Loading Transcoded Source (AAC):", streamUrlTranscode);
    if (hls) hls.destroy();

    // Affichage chargement
    const chargement = document.getElementById('chargement');
    chargement.style.display = 'flex';

    video.src = streamUrlTranscode;
    video.load();
    video.play().then(() => {
        chargement.style.display = 'none';
        majIcons(true);
    }).catch(e => console.error(e));
}

loadVideo();


// Initial Volume
video.volume = 1;

// UI Helpers & Inactivity Timer
let inactivityTimer;

function showControls() {
    header.style.opacity = '1';
    controls.style.opacity = '1';
    conteneurVideo.style.cursor = 'default';
}

function hideControls() {
    // Ne pas cacher si la vidéo est en pause
    if (!video.paused) {
        header.style.opacity = '0';
        controls.style.opacity = '0';
        conteneurVideo.style.cursor = 'none';
    }
}

function resetInactivityTimer() {
    showControls();
    clearTimeout(inactivityTimer);
    inactivityTimer = setTimeout(hideControls, 3000); // 3 secondes
}

// Listeners pour l'activité souris
conteneurVideo.addEventListener('mousemove', resetInactivityTimer);
conteneurVideo.addEventListener('click', resetInactivityTimer);

// Playback Logic (Click to Play -> Sound Guaranteed)
function startVideo() {
    video.play().then(() => {
        overlayPlay.style.display = 'none'; // Vire l'overlay
        majIcons(true);
        resetInactivityTimer(); // Start timer
    }).catch(e => {
        console.error("Play failed", e);
    });
}

overlayPlay.addEventListener('click', (e) => {
    e.stopPropagation();
    startVideo();
});

// Toggle Play/Pause
function togglePlay() {
    if (video.paused || video.ended) {
        video.play();
    } else {
        video.pause();
    }
    resetInactivityTimer();
}

video.addEventListener('play', () => { majIcons(true); resetInactivityTimer(); });
video.addEventListener('pause', () => { majIcons(false); showControls(); }); // Toujours afficher en pause

function majIcons(isPlaying) {
    if (isPlaying) {
        iconePlay.classList.add('hidden');
        iconePause.classList.remove('hidden');
        overlayPlay.style.opacity = '0';
        overlayPlay.style.pointerEvents = 'none';
    } else {
        iconePlay.classList.remove('hidden');
        iconePause.classList.add('hidden');
        showControls();
    }
}

btnLecture.addEventListener('click', (e) => { e.stopPropagation(); togglePlay(); });
video.addEventListener('click', (e) => { e.stopPropagation(); togglePlay(); });

// Seek Bar Logic
video.addEventListener('timeupdate', () => {
    if (video.duration && isFinite(video.duration)) {
        const pct = (video.currentTime / video.duration) * 100;
        progressBar.style.width = pct + '%';
        thumb.style.left = pct + '%';
        seekSlider.value = video.currentTime;
        // Avoid NaN
        timeCurrent.innerText = formatTime(video.currentTime || 0);
    }
});

video.addEventListener('loadedmetadata', () => {
    if (isFinite(video.duration)) {
        timeDuration.innerText = formatTime(video.duration);
        seekSlider.max = video.duration;
    }
});

seekSlider.addEventListener('input', (e) => {
    // Pendant le seek, on reset le timer
    resetInactivityTimer();
    const val = e.target.value;
    const pct = (val / video.duration) * 100;
    progressBar.style.width = pct + '%';
    thumb.style.left = pct + '%';
    timeCurrent.innerText = formatTime(val);
    video.currentTime = val;
});

function formatTime(s) {
    if (!s || s < 0) s = 0;
    const d = new Date(s * 1000);
    const m = d.getUTCMinutes();
    const sec = d.getUTCSeconds().toString().padStart(2, '0');
    const h = d.getUTCHours();
    return h ? `${h}:${m.toString().padStart(2, '0')}:${sec}` : `${m}:${sec}`;
}

// Volume
sliderVolume.addEventListener('input', (e) => { video.volume = e.target.value; });
btnMute.addEventListener('click', (e) => {
    e.stopPropagation();
    video.muted = !video.muted;
    updateVolIcon();
});
video.addEventListener('volumechange', updateVolIcon);

function updateVolIcon() {
    if (video.muted || video.volume === 0) {
        volHigh.classList.add('hidden');
        volMute.classList.remove('hidden');
        sliderVolume.value = 0;
    } else {
        volHigh.classList.remove('hidden');
        volMute.classList.add('hidden');
        sliderVolume.value = video.volume;
    }
}

// Fullscreen
btnPleinEcran.addEventListener('click', (e) => {
    e.stopPropagation();
    if (!document.fullscreenElement) conteneurVideo.requestFullscreen();
    else document.exitFullscreen();
});

controls.addEventListener('click', e => e.stopPropagation());
