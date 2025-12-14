import React, { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { getSeriesInfo } from '../api/content';
import useAuthStore from '../store/useAuthStore';
import { ArrowLeft, Play, Calendar, Star } from 'lucide-react';
import { clsx } from 'clsx';

const SeriesDetailsPage = () => {
    const { seriesId } = useParams();
    const navigate = useNavigate();
    const { dns, user } = useAuthStore();

    const [info, setInfo] = useState(null);
    const [episodes, setEpisodes] = useState({});
    const [seasons, setSeasons] = useState([]);
    const [selectedSeason, setSelectedSeason] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const loadDetails = async () => {
            setLoading(true);
            const data = await getSeriesInfo(dns, user.username, user.password, seriesId);
            if (data) {
                setInfo(data.info);
                setEpisodes(data.episodes);
                // Episodes object keys are usually season numbers "1", "2" etc.
                // Or sometimes nested arrays. XC usually returns { "1": [episodes...], "2": [...] }
                const seasonKeys = Object.keys(data.episodes).sort((a, b) => parseInt(a) - parseInt(b));
                setSeasons(seasonKeys);
                if (seasonKeys.length > 0) setSelectedSeason(seasonKeys[0]);
            }
            setLoading(false);
        };
        loadDetails();
    }, [seriesId, dns, user]);

    const handleEpisodeClick = (episode) => {
        // Episode stream URL construction
        // format often: /series/username/password/id.extension
        navigate(`/player/series/${episode.id}?ext=${episode.container_extension || 'mp4'}`);
    };

    if (loading) {
        return <div className="min-h-screen bg-black text-white flex items-center justify-center">Loading Series Details...</div>;
    }

    if (!info) {
        return <div className="min-h-screen bg-black text-white flex items-center justify-center">Series not found.</div>;
    }

    return (
        <div className="min-h-screen bg-neutral-950 text-white flex flex-col">
            {/* Backdrop/Cover Section */}
            <div className="relative h-[50vh] w-full bg-neutral-900 overflow-hidden">
                <div
                    className="absolute inset-0 bg-cover bg-center blur-xl opacity-30 scale-110"
                    style={{ backgroundImage: `url(${info.backdrop_path && info.backdrop_path.length > 0 ? info.backdrop_path[0] : info.cover})` }}
                />
                <div className="absolute inset-0 bg-gradient-to-t from-neutral-950 via-neutral-950/60 to-transparent" />

                <div className="absolute top-0 left-0 p-6 z-20">
                    <button onClick={() => navigate(-1)} className="flex items-center gap-2 text-neutral-300 hover:text-white transition-colors bg-black/30 p-2 rounded-full backdrop-blur-md">
                        <ArrowLeft size={24} />
                    </button>
                </div>

                <div className="absolute bottom-0 left-0 w-full p-6 md:p-12 flex flex-col md:flex-row gap-8 items-end z-10 w-full max-w-7xl mx-auto">
                    <div className="w-32 md:w-48 aspect-[2/3] rounded-lg overflow-hidden shadow-2xl border border-neutral-800 flex-shrink-0">
                        <img src={info.cover} className="w-full h-full object-cover" alt={info.name} />
                    </div>
                    <div className="flex-1 mb-2">
                        <h1 className="text-3xl md:text-5xl font-bold text-white mb-2">{info.name}</h1>
                        <div className="flex items-center gap-4 text-sm text-neutral-400 mb-4">
                            {info.releaseDate && <div className="flex items-center gap-1"><Calendar size={14} /> {info.releaseDate}</div>}
                            {info.rating && <div className="flex items-center gap-1 text-yellow-400"><Star size={14} fill="currentColor" /> {info.rating}</div>}
                        </div>
                        <p className="text-neutral-300 max-w-2xl line-clamp-3 md:line-clamp-none text-sm md:text-base">{info.plot}</p>
                    </div>
                </div>
            </div>

            {/* Episodes Section */}
            <div className="max-w-7xl mx-auto w-full p-6 md:p-12">
                <div className="flex items-center gap-4 border-b border-neutral-800 pb-2 mb-6 overflow-x-auto">
                    {seasons.map(seasonNum => (
                        <button
                            key={seasonNum}
                            onClick={() => setSelectedSeason(seasonNum)}
                            className={clsx(
                                "px-4 py-2 rounded-lg text-sm font-bold whitespace-nowrap transition-all",
                                selectedSeason === seasonNum
                                    ? "bg-red-600 text-white"
                                    : "text-neutral-400 hover:bg-neutral-800 hover:text-white"
                            )}
                        >
                            Season {seasonNum}
                        </button>
                    ))}
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    {selectedSeason && episodes[selectedSeason] && episodes[selectedSeason].map(ep => (
                        <div
                            key={ep.id}
                            onClick={() => handleEpisodeClick(ep)}
                            className="bg-neutral-900 border border-neutral-800 rounded-lg p-4 cursor-pointer hover:bg-neutral-800 hover:border-red-600/30 transition-all flex items-center gap-4 group"
                        >
                            <div className="w-12 h-12 rounded-full bg-neutral-800 flex-shrink-0 flex items-center justify-center group-hover:bg-red-600 transition-colors">
                                <Play size={20} className="text-white fill-current ml-1" />
                            </div>
                            <div className="flex-1 min-w-0">
                                <h4 className="font-medium text-white truncate">
                                    {ep.title && ep.title.length > 3 ? ep.title : `Episode ${ep.episode_num}`}
                                </h4>
                                <p className="text-xs text-neutral-500 mt-0.5">
                                    Ep {ep.episode_num} • {ep.container_extension}
                                </p>
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </div>
    );
};

export default SeriesDetailsPage;
