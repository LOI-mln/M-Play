
console.log("Player VOD JS executing...");
// alert("DEBUG: JS Loaded"); // Commented out to avoid annoyance if it works, but user said "nothing happens". 
// Let's use console.log primarily, but I need to know if it runs. Use a temporary header color change as a signal.
// alert("DEBUG: JS Loaded"); 
// Headers red border removed.

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
// Resume Logic
const { streamUrlHls, streamUrlDirect, streamUrlTranscode, duration: rawDuration, resumeTime, streamId } = window.VodConfig;

// ... (Existing code) ...

// Auto-Resume
if (resumeTime > 10) {
    // Si on a déjà vu + de 10s, on demande ou on resume direct
    // Ici on resume direct pour UX fluide (Netflix style)
    console.log("Resuming at", resumeTime);
    video.currentTime = resumeTime;
}

// Progress Saver
if (streamId) {
    setInterval(() => {
        if (!video.paused && video.currentTime > 5) {
            fetch('/movies/progress', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    stream_id: streamId,
                    time: Math.floor(video.currentTime),
                    duration: Math.floor(getDuration())
                })
            }).catch(e => console.warn("Save progress failed", e));
        }
    }, 10000); // Every 10s
}

// Global Error Handler

// Parsing de la durée (format "hh:mm:ss" ou secondes)
let staticDuration = 0;
if (rawDuration) {
    if (rawDuration.includes(':')) {
        const parts = rawDuration.split(':').reverse();
        staticDuration += parseInt(parts[0] || 0); // sec
        staticDuration += (parseInt(parts[1] || 0) * 60); // min
        staticDuration += (parseInt(parts[2] || 0) * 3600); // hour
    } else {
        staticDuration = parseInt(rawDuration);
    }
}
console.log("Static Duration (PHP):", staticDuration);

// SEEK & Transcode State
let currentTranscodeOffset = 0;
let isTranscoding = false;

let hls;

function loadVideo() {
    // Si pas de HLS (ex: Film MP4/MKV), on passe direct au transcode/direct
    // Note: Pour les MOVIES, on n'a jamais de HLS dans cette configuration, donc on force le fallback
    if (!streamUrlHls || streamUrlHls === '') {
        console.log("No HLS URL provided, switching to TRANSCODE/DIRECT.");
        loadTranscode();
        return;
    }

    if (Hls.isSupported()) {
        hls = new Hls();
        hls.loadSource(streamUrlHls);
        hls.attachMedia(video);

        hls.on(Hls.Events.MANIFEST_PARSED, function () {
            console.log("HLS found and parsed.");
            // HLS a souvent la bonne durée, mais on garde staticDuration en backup
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

// Function removed (duplicate)

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

overlayPlay.style.pointerEvents = 'auto'; // FORCE CLICKABLE

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

// Helper to get real duration
function getDuration() {
    if (staticDuration > 0) return staticDuration;
    return (video.duration && isFinite(video.duration)) ? video.duration : 0;
}

// Variables moved to top
// Mode Transcode (Fallback & Seek)

// Mode Transcode (Fallback & Seek)
function loadTranscode(startTime = 0) {
    console.log("Loading Transcoded Source (AAC):", streamUrlTranscode, "Start:", startTime);
    if (hls) hls.destroy();

    isTranscoding = true;
    currentTranscodeOffset = startTime;

    // Affichage chargement
    const chargement = document.getElementById('chargement');
    chargement.style.display = 'flex';

    // On ajoute le parametre start seulement si > 0
    let url = streamUrlTranscode;
    if (startTime > 0) {
        url += "&start=" + Math.floor(startTime);
    }

    video.src = url;
    video.load();

    var promise = video.play();
    if (promise !== undefined) {
        promise.then(() => {
            chargement.style.display = 'none';
            majIcons(true);
        }).catch(e => {
            console.warn("Autoplay prevented or failed:", e);
            // ESSENTIEL : On cache le spinner pour que l'utilisateur puisse cliquer sur Play
            chargement.style.display = 'none';
            overlayPlay.style.display = 'flex';
        });
    }
}

// Seek Bar Logic
video.addEventListener('timeupdate', () => {
    const d = getDuration();
    if (d > 0) {
        // En mode transcode, le temps affiché = temps du buffer + offset du seek
        const realCurrentTime = isTranscoding ? (video.currentTime + currentTranscodeOffset) : video.currentTime;

        const pct = (realCurrentTime / d) * 100;
        progressBar.style.width = pct + '%';
        thumb.style.left = pct + '%';
        seekSlider.value = realCurrentTime;
        seekSlider.max = d;

        timeCurrent.innerText = formatTime(realCurrentTime || 0);
        timeDuration.innerText = formatTime(d);
    }
});

video.addEventListener('loadedmetadata', () => {
    const d = getDuration();
    if (d > 0) {
        timeDuration.innerText = formatTime(d);
        seekSlider.max = d;
    }
});

// Event SEEK (Input change)
seekSlider.addEventListener('change', (e) => {
    // Note: 'change' se déclenche à la fin du drag, 'input' en continu. 
    // Pour le seek serveur, on préfère 'change' pour éviter de spammer.
    const val = parseFloat(e.target.value);

    // Si on est en Transcode, on DOIT recharger le stream
    if (isTranscoding && staticDuration > 0) {
        loadTranscode(val);
    } else {
        // Standard seek
        if (isFinite(video.duration)) {
            video.currentTime = val;
        }
    }
    resetInactivityTimer();
});

seekSlider.addEventListener('input', (e) => {
    // Just visual update during drag
    resetInactivityTimer();
    const val = parseFloat(e.target.value);
    const d = getDuration();
    const pct = (val / d) * 100;

    progressBar.style.width = pct + '%';
    thumb.style.left = pct + '%';
    timeCurrent.innerText = formatTime(val);
});

// TOOLTIP HOVER
const seekContainer = document.getElementById('seek-container');
const timeTooltip = document.getElementById('time-tooltip');

if (seekContainer && timeTooltip) {
    seekContainer.addEventListener('mousemove', (e) => {
        const rect = seekContainer.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const width = rect.width;

        // Calcul du temps survolé
        const d = getDuration();
        if (d > 0) {
            const pct = Math.max(0, Math.min(1, x / width));
            const time = pct * d;

            // Positionnement
            timeTooltip.style.left = (pct * 100) + '%';
            timeTooltip.innerText = formatTime(time);
            timeTooltip.style.opacity = '1';
        }
    });

    seekContainer.addEventListener('mouseleave', () => {
        timeTooltip.style.opacity = '0';
    });
}

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

// Global Error Handler
video.addEventListener('error', (e) => {
    console.error("Link Error or Decode Error:", video.error);
    const chargement = document.getElementById('chargement');
    if (chargement) chargement.style.display = 'none';
    majIcons(false);
});
