import React, { useRef, useState } from 'react';
import { useParams, useNavigate, useSearchParams } from 'react-router-dom';
import VideoPlayer from '../components/VideoPlayer';
import useAuthStore from '../store/useAuthStore';
import { getStreamUrl } from '../api/xcClient';
import { ArrowLeft, AlertTriangle } from 'lucide-react';

const PlayerPage = () => {
    const { type, streamId } = useParams(); // type: live, movie, series
    const [searchParams] = useSearchParams();
    const extension = searchParams.get('ext') || (type === 'live' ? 'm3u8' : 'mp4');

    const navigate = useNavigate();
    const { dns, user } = useAuthStore();
    const playerRef = useRef(null);
    const [error, setError] = useState(false);

    // Construct URL
    const streamUrl = getStreamUrl(dns, user.username, user.password, streamId, extension, type);

    const videoJsOptions = {
        autoplay: false, // Force user click to ensure audio plays
        muted: false,
        controls: true,
        controlBar: {
            volumePanel: { inline: true }
        },
        responsive: true,
        fluid: true,
        fill: true,
        sources: [{
            src: streamUrl,
            // If live, force HLS. If VOD, try to infer.
            // video/mp4 is safe for mp4. video/x-matroska (mkv) is not supported in Chrome.
            // If it is MKV, we can't easily play it without transcoding. 
            // However, often XC servers create links that redirect to mp4 or allow transcoding.
            // But let's set 'video/mp4' as default for non-m3u8 to give it a shot, 
            // or leave type undefined for browser detection if not m3u8.
            // Video.js handles 'video/mp4' well.
            type: extension === 'm3u8' ? 'application/x-mpegURL' : (extension === 'mkv' ? 'video/webm' : `video/${extension}`)
            // Chrome plays some WEBM/MKV. If fails, user gets error.
        }],
        html5: {
            vhs: {
                overrideNative: true
            },
            nativeAudioTracks: false,
            nativeVideoTracks: false
        }
    };

    const handlePlayerReady = (player) => {
        playerRef.current = player;

        // Force volume to 100% on load
        player.on('loadedmetadata', () => {
            player.muted(false);
            player.volume(1.0);
        });

        // Double check on play to override any browser policy
        player.on('play', () => {
            if (player.muted()) {
                player.muted(false);
                player.volume(1.0);
            }
        });

        // Handle errors
        player.on('error', () => {
            console.error('Player error:', player.error());
            setError(true);
        });
    };

    return (
        <div className="h-screen w-screen bg-black flex flex-col relative group">
            {/* Back Button Overlay */}
            <div className="absolute top-0 left-0 w-full p-6 z-50 bg-gradient-to-b from-black/80 to-transparent opacity-0 group-hover:opacity-100 transition-opacity">
                <button
                    onClick={() => navigate(-1)}
                    className="flex items-center gap-2 text-white hover:text-red-500 transition-colors"
                >
                    <ArrowLeft size={28} />
                    <span className="font-bold text-lg drop-shadow-md">Back</span>
                </button>
            </div>

            {/* Error Message */}
            {error && (
                <div className="absolute inset-0 flex items-center justify-center z-40 bg-black/80">
                    <div className="bg-neutral-900 p-8 rounded-2xl border border-red-900/50 flex flex-col items-center gap-4 text-center max-w-md">
                        <div className="bg-red-500/10 p-4 rounded-full">
                            <AlertTriangle size={48} className="text-red-500" />
                        </div>
                        <h3 className="text-xl font-bold text-white">Playback Error</h3>
                        <p className="text-neutral-400">
                            Unable to play this stream. The format might not be supported in this browser or the stream is offline.
                        </p>
                        <p className="text-xs text-neutral-600 font-mono break-all">{streamUrl}</p>
                        <button
                            onClick={() => window.location.reload()}
                            className="px-6 py-2 bg-neutral-800 hover:bg-neutral-700 rounded-lg text-white transition-colors"
                        >
                            Retry
                        </button>
                    </div>
                </div>
            )}

            {/* Video Player Container */}
            <div className="flex-1 w-full h-full overflow-hidden flex items-center justify-center bg-neutral-900">
                {!error && (
                    <VideoPlayer options={videoJsOptions} onReady={handlePlayerReady} />
                )}
            </div>
        </div>
    );
};

export default PlayerPage;
