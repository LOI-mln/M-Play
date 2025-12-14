import React, { useRef, useState } from 'react';
import { useParams, useNavigate, useSearchParams } from 'react-router-dom';
import VideoPlayer from '../components/VideoPlayer';
import useAuthStore from '../store/useAuthStore';
import { getStreamUrl } from '../api/xcClient';
import { ArrowLeft, AlertTriangle } from 'lucide-react';

const PlayerPage = () => {
    const { type, streamId } = useParams(); // type: live, movie, series
    const [searchParams] = useSearchParams();
    const navigate = useNavigate();

    // User selectable format. Default to requested or MKV for VOD.
    const [selectedFormat, setSelectedFormat] = useState(type === 'live' ? 'm3u8' : 'mkv');

    const { dns, user } = useAuthStore();
    const [error, setError] = useState(false);
    const playerRef = useRef(null);
    const [copied, setCopied] = useState(false);

    // Construct URL based on manual format selection
    const streamUrl = getStreamUrl(dns, user.username, user.password, streamId, selectedFormat, type);

    const videoJsOptions = {
        autoplay: true,
        muted: false,
        controls: true,
        controlBar: {
            volumePanel: { inline: true }
        },
        responsive: true,
        fluid: true,
        fill: true,
        crossOrigin: 'anonymous', // Critical for accessing cross-origin streams (HLS/MP4) correctly
        sources: [{
            src: streamUrl,
            // Map extensions to mime types
            type: selectedFormat === 'm3u8' ? 'application/x-mpegURL' :
                (selectedFormat === 'mp4' ? 'video/mp4' :
                    (selectedFormat === 'mkv' ? 'video/webm' : `video/${selectedFormat}`))
        }],
        html5: {
            vhs: {
                enableLowInitialPlaylist: true,
                smoothQualityChange: true,
            },
            nativeAudioTracks: true,
            nativeVideoTracks: true
        }
    };

    const handlePlayerReady = (player) => {
        playerRef.current = player;
        player.volume(1.0);
        player.muted(false);
        // Add crossorigin attribute manually to the video element for better support
        const videoEl = player.el().querySelector('video');
        if (videoEl) {
            videoEl.setAttribute('crossorigin', 'anonymous');
        }

        player.on('error', () => {
            console.error('Player error:', player.error());
            setError(true);
        });
    };

    const handleCopyLink = () => {
        navigator.clipboard.writeText(streamUrl);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    };

    const handleDownload = () => {
        const link = document.createElement('a');
        link.href = streamUrl;
        link.download = `video-${streamId}.${selectedFormat}`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    };

    return (
        <div className="h-screen w-screen bg-black flex flex-col relative group">
            {/* Header / Controls Overlay */}
            <div className="absolute top-0 left-0 w-full p-6 z-50 bg-gradient-to-b from-black/80 to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex items-center flex-wrap gap-4">
                <button
                    onClick={() => navigate(-1)}
                    className="flex items-center gap-2 text-white hover:text-red-500 transition-colors"
                >
                    <ArrowLeft size={28} />
                    <span className="font-bold text-lg drop-shadow-md">Back</span>
                </button>

                {/* Format Selector */}
                <div className="ml-auto flex items-center gap-2 bg-neutral-900/80 backdrop-blur-md p-1 rounded-lg border border-white/10">
                    <span className="text-xs text-neutral-400 px-2 font-bold uppercase">Source:</span>
                    {['mkv', 'mp4', 'm3u8', 'ts'].map((fmt) => (
                        <button
                            key={fmt}
                            onClick={() => {
                                setError(false);
                                setSelectedFormat(fmt);
                            }}
                            className={`px-3 py-1 rounded-md text-xs font-bold transition-colors uppercase ${selectedFormat === fmt
                                ? 'bg-blue-600 text-white shadow-lg'
                                : 'hover:bg-white/10 text-neutral-300'
                                }`}
                        >
                            {fmt}
                        </button>
                    ))}
                </div>

                {/* Actions */}
                <div className="flex items-center gap-2">
                    <button
                        onClick={handleDownload}
                        className="bg-neutral-800 hover:bg-neutral-700 text-white p-2 rounded-lg border border-white/10 transition-colors"
                        title="Download Video"
                    >
                        ⬇️
                    </button>
                    <button
                        onClick={handleCopyLink}
                        className="bg-neutral-800 hover:bg-neutral-700 text-white p-2 rounded-lg border border-white/10 transition-colors"
                        title="Copy Stream Link"
                    >
                        {copied ? '✓' : '🔗'}
                    </button>
                </div>
            </div>

            {/* ERROR DISPLAY */}
            {error && (
                <div className="absolute inset-0 flex items-center justify-center z-40 bg-black/80">
                    <div className="bg-neutral-900 p-8 rounded-2xl border border-red-900/50 flex flex-col items-center gap-4 text-center max-w-md">
                        <div className="bg-red-500/10 p-4 rounded-full">
                            <AlertTriangle size={48} className="text-red-500" />
                        </div>
                        <h3 className="text-xl font-bold text-white">Playback Error</h3>
                        <p className="text-neutral-400">
                            Format <strong>.{selectedFormat}</strong> failed.
                        </p>
                        <p className="text-sm text-neutral-500">
                            Try clicking another format in the top bar.
                        </p>

                        <div className="flex gap-2 w-full pt-4">
                            {['mkv', 'mp4', 'm3u8'].map(fmt => (
                                fmt !== selectedFormat && (
                                    <button
                                        key={fmt}
                                        onClick={() => { setError(false); setSelectedFormat(fmt); }}
                                        className="flex-1 py-2 bg-neutral-800 hover:bg-neutral-700 rounded text-xs uppercase font-bold text-white"
                                    >
                                        Try .{fmt}
                                    </button>
                                )
                            ))}
                        </div>

                        <div className="w-full border-t border-white/5 pt-4 mt-2 mb-2">
                            <p className="text-xs text-neutral-500 mb-2">If no format works with sound:</p>
                            <button
                                onClick={handleDownload}
                                className="w-full py-2 bg-green-600/20 hover:bg-green-600/30 text-green-400 rounded border border-green-500/30 text-sm font-bold flex items-center justify-center gap-2"
                            >
                                ⬇️ Download Video File
                            </button>
                            <p className="text-[10px] text-neutral-600 mt-1">
                                (Play the downloaded file with standard QuickTime/Windows Player)
                            </p>
                        </div>
                    </div>
                </div>
            )}

            {/* Video Player Container - Force remount on URL change using key */}
            <div className="flex-1 w-full h-full overflow-hidden flex items-center justify-center bg-neutral-900">
                {!error && (
                    <VideoPlayer key={streamUrl} options={videoJsOptions} onReady={handlePlayerReady} />
                )}
            </div>
        </div>
    );
};

export default PlayerPage;
