
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
let isDragging = false; // Flag pour éviter le conflit Drag vs TimeUpdate
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
                switch (data.type) {
                    case Hls.ErrorTypes.NETWORK_ERROR:
                        console.log("fatal network error encountered, try to recover");
                        hls.startLoad();
                        break;
                    case Hls.ErrorTypes.MEDIA_ERROR:
                        console.log("fatal media error encountered, try to recover");
                        hls.recoverMediaError();
                        break;
                    default:
                        console.warn("HLS Fatal, switching to TRANSCODE (Auto-Fix)...");
                        hls.destroy();
                        loadTranscode(); // Fallback to Transcode directly
                        break;
                }
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

// Buffering / Loading State Handlers
// Buffering / Loading State Handlers & Watchdog
let stallTimeout;

function startStallWatchdog() {
    clearTimeout(stallTimeout);
    // Si bloqué pendant 6 secondes, on force le reload
    stallTimeout = setTimeout(() => {
        if (isTranscoding && !video.paused) {
            console.warn("Watchdog: Stream Stalled too long -> Force Recovery");
            // On déclenche manuellement une erreur pour activer la logique de recovery
            video.dispatchEvent(new Event('error'));
        }
    }, 6000);
}

function stopStallWatchdog() {
    clearTimeout(stallTimeout);
}

video.addEventListener('waiting', () => {
    chargement.style.display = 'flex';
    startStallWatchdog();
});

video.addEventListener('playing', () => {
    chargement.style.display = 'none';
    stopStallWatchdog();
});

video.addEventListener('stalled', () => {
    console.warn("Stream Stalled");
    if (!video.paused) {
        chargement.style.display = 'flex';
        startStallWatchdog();
    }
});

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
    // On cache l'overlay play pendant le chargement pour éviter la confusion
    overlayPlay.style.display = 'none';

    // On ajoute le parametre start seulement si > 0
    let url = streamUrlTranscode;
    if (startTime > 0) {
        url += "&start=" + Math.floor(startTime);
    }

    video.autoplay = true; // Force autoplay intent
    video.src = url;
    video.load();

    const attemptPlay = () => {
        var promise = video.play();
        if (promise !== undefined) {
            promise.then(() => {
                chargement.style.display = 'none';
                majIcons(true);
            }).catch(e => {
                console.warn("Autoplay prevented or failed:", e);
                // On réessaie une fois après 500ms (souvent l'erreur est "Interrupted" à cause du chargement)
                setTimeout(() => {
                    video.play().catch(e2 => {
                        console.error("Retry Play failed:", e2);
                        chargement.style.display = 'none';
                        overlayPlay.style.display = 'flex'; // On rend la main au user seulement si échec total
                    });
                }, 500);
            });
        }
    };

    // On attend que le navigateur soit prêt
    video.addEventListener('loadeddata', attemptPlay, { once: true });
}

// Seek Bar Logic
video.addEventListener('timeupdate', () => {
    const d = getDuration();
    if (d > 0) {
        // En mode transcode, le temps affiché = temps du buffer + offset du seek
        const realCurrentTime = isTranscoding ? (video.currentTime + currentTranscodeOffset) : video.currentTime;

        const pct = (realCurrentTime / d) * 100;

        // On met à jour la barre ET le temps seulement si on ne drag pas (sinon ça le user perd ce qu'il vise)
        if (!isDragging) {
            progressBar.style.width = pct + '%';
            thumb.style.left = pct + '%';
            seekSlider.value = realCurrentTime;
            timeCurrent.innerText = formatTime(realCurrentTime || 0);
        }
        seekSlider.max = d;
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
    resetInactivityTimer();
    isDragging = false; // Fin du drag
});

seekSlider.addEventListener('mousedown', () => isDragging = true);
seekSlider.addEventListener('touchstart', () => isDragging = true);

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
// TOOLTIP HOVER REMOVED BY USER REQUEST

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
    toggleFullScreen(conteneurVideo);
});

function toggleFullScreen(elem) {
    console.log("Toggle Fullscreen called for:", elem);
    try {
        if (!document.fullscreenElement && !document.mozFullScreenElement &&
            !document.webkitFullscreenElement && !document.msFullscreenElement) {

            console.log("Requesting Fullscreen...");
            // CHROME FIX: Prioritize WebKit prefix which is often more robust on macOS
            if (elem.webkitRequestFullscreen) {
                elem.webkitRequestFullscreen();
            } else if (elem.requestFullscreen) {
                elem.requestFullscreen().catch(err => {
                    console.warn("Fullscreen container failed, trying video:", err);
                    if (video.requestFullscreen) video.requestFullscreen();
                });
            } else if (elem.msRequestFullscreen) {
                elem.msRequestFullscreen();
            } else if (elem.mozRequestFullScreen) {
                elem.mozRequestFullScreen();
            }
        } else {
            console.log("Exiting Fullscreen...");
            if (document.webkitExitFullscreen) {
                document.webkitExitFullscreen();
            } else if (document.exitFullscreen) {
                document.exitFullscreen();
            } else if (document.mozCancelFullScreen) {
                document.mozCancelFullScreen();
            } else if (document.msExitFullscreen) {
                document.msExitFullscreen();
            }
        }
    } catch (e) {
        console.error("Fullscreen Error:", e);
    }
}

controls.addEventListener('click', e => e.stopPropagation());

// Global Error Handler & Recovery Logic
let recoveryCount = 0;
const MAX_RECOVERIES = 10;

video.addEventListener('error', (e) => {
    console.error("Link Error or Decode Error:", video.error);

    // AUTO-RECOVERY LOGIC
    // Si la vidéo plante (coupure réseau), on la relance automatiquement
    if (isTranscoding && recoveryCount < MAX_RECOVERIES) {
        recoveryCount++;
        // On recule très peu (0.5s) juste pour ne pas perdre la frame
        const recoverTime = Math.max(0, video.currentTime + currentTranscodeOffset - 0.5);
        console.log(`⚠️ Stream Cut detected! Auto-Recovering (${recoveryCount}/${MAX_RECOVERIES}) at ${recoverTime}s...`);

        // Afficher chargement
        const chargement = document.getElementById('chargement');
        if (chargement) chargement.style.display = 'flex';

        // Petit délai pour laisser le réseau souffler
        setTimeout(() => {
            loadTranscode(recoverTime);
        }, 1000);
    } else {
        // Abandon après 10 tentatives
        const chargement = document.getElementById('chargement');
        if (chargement) chargement.style.display = 'none';
        majIcons(false);
        console.warn("Fatal Error: Max recoveries reached.");
    }
});

// Reset recovery counter on successful play
video.addEventListener('playing', () => {
    if (recoveryCount > 0) {
        setTimeout(() => {
            recoveryCount = 0;
            console.log("Recovery successful. Stream stable.");
        }, 5000);
    }
});
