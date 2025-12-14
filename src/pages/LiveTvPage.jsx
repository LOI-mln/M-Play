import React, { useEffect, useState, useMemo } from 'react';
import { useNavigate } from 'react-router-dom';
import { getLiveCategories, getLiveStreams } from '../api/content';
import useAuthStore from '../store/useAuthStore';
import { ArrowLeft, Search, Play, AlertCircle } from 'lucide-react';
import { clsx } from 'clsx';
// Will import Player modal later

const LiveTvPage = () => {
    const navigate = useNavigate();
    const { dns, user, isAuthenticated } = useAuthStore();

    const [categories, setCategories] = useState([]);
    const [channels, setChannels] = useState([]);

    const [selectedCategory, setSelectedCategory] = useState(null);
    const [searchQuery, setSearchQuery] = useState('');

    const [loadingCategories, setLoadingCategories] = useState(true);
    const [loadingChannels, setLoadingChannels] = useState(false);

    // Load Categories on mount
    useEffect(() => {
        if (!isAuthenticated) return;

        const loadCategories = async () => {
            setLoadingCategories(true);
            const data = await getLiveCategories(dns, user.username, user.password);
            setCategories(data);
            if (data.length > 0) {
                // Automatically select first category or "All" logic if preferred. 
                // For now just list them.
                setSelectedCategory(data[0].category_id);
            }
            setLoadingCategories(false);
        };

        loadCategories();
    }, [isAuthenticated, dns, user]);

    // Load Channels when Category changes
    useEffect(() => {
        if (!selectedCategory || !isAuthenticated) return;

        const loadChannels = async () => {
            setLoadingChannels(true);
            // In a real optimized app, we might cache this response
            const data = await getLiveStreams(dns, user.username, user.password, selectedCategory);
            setChannels(data);
            setLoadingChannels(false);
        };

        loadChannels();
    }, [selectedCategory, isAuthenticated, dns, user]);

    const filteredChannels = useMemo(() => {
        if (!searchQuery) return channels;
        return channels.filter(c => c.name.toLowerCase().includes(searchQuery.toLowerCase()));
    }, [channels, searchQuery]);

    // Placeholder for channel click
    const handleChannelClick = (streamId) => {
        navigate(`/player/live/${streamId}`);
    };

    return (
        <div className="h-screen w-full bg-neutral-950 flex flex-col md:flex-row overflow-hidden text-white">

            {/* Sidebar: Categories */}
            <div className="w-full md:w-80 flex-shrink-0 bg-neutral-900 border-r border-neutral-800 flex flex-col">
                <div className="p-4 border-b border-neutral-800 flex items-center gap-3">
                    <button onClick={() => navigate('/dashboard')} className="p-2 hover:bg-neutral-800 rounded-full transition-colors text-neutral-400 hover:text-white">
                        <ArrowLeft size={20} />
                    </button>
                    <h2 className="font-bold text-lg">Categories</h2>
                </div>

                <div className="flex-1 overflow-y-auto p-2 space-y-1 scrollbar-thin scrollbar-thumb-neutral-700">
                    {loadingCategories ? (
                        <div className="p-4 text-center text-neutral-500">Loading groups...</div>
                    ) : (
                        categories.map(cat => (
                            <button
                                key={cat.category_id}
                                onClick={() => {
                                    setSelectedCategory(cat.category_id);
                                    setSearchQuery(''); // Reset search when switching categories
                                }}
                                className={clsx(
                                    "w-full text-left px-4 py-3 rounded-lg text-sm font-medium transition-all",
                                    selectedCategory === cat.category_id
                                        ? "bg-red-600 text-white shadow-lg shadow-red-900/20"
                                        : "text-neutral-400 hover:bg-neutral-800 hover:text-white"
                                )}
                            >
                                {cat.category_name}
                            </button>
                        ))
                    )}
                </div>
            </div>

            {/* Main Content: Channels */}
            <div className="flex-1 flex flex-col h-full bg-black">
                {/* Toolbar */}
                <div className="h-16 border-b border-neutral-800 flex items-center justify-between px-6 bg-neutral-900/50 backdrop-blur-sm z-10">
                    <h2 className="font-bold text-lg hidden md:block">Channels</h2>

                    <div className="relative w-full md:w-64">
                        <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-neutral-500" size={18} />
                        <input
                            type="text"
                            placeholder="Search channels..."
                            value={searchQuery}
                            onChange={(e) => setSearchQuery(e.target.value)}
                            className="w-full bg-neutral-950 border border-neutral-800 rounded-full py-2 pl-10 pr-4 text-sm focus:outline-none focus:border-red-600 focus:ring-1 focus:ring-red-600 transition-all"
                        />
                    </div>
                </div>

                {/* Grid */}
                <div className="flex-1 overflow-y-auto p-6 scrollbar-thin scrollbar-thumb-neutral-800">
                    {loadingChannels ? (
                        <div className="flex items-center justify-center h-full text-neutral-500">
                            <div className="animate-spin w-6 h-6 border-2 border-red-500 border-t-transparent rounded-full mr-3"></div>
                            Loading Channels...
                        </div>
                    ) : filteredChannels.length === 0 ? (
                        <div className="flex flex-col items-center justify-center h-full text-neutral-500 gap-2">
                            <AlertCircle size={32} />
                            <p>No channels found.</p>
                        </div>
                    ) : (
                        <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                            {filteredChannels.map(channel => (
                                <div
                                    key={channel.stream_id}
                                    onClick={() => handleChannelClick(channel.stream_id)}
                                    className="group bg-neutral-900 hover:bg-neutral-800 rounded-xl p-4 cursor-pointer transition-all hover:-translate-y-1 hover:shadow-xl hover:shadow-red-900/10 border border-neutral-800 hover:border-red-600/30 flex flex-col items-center gap-3"
                                >
                                    <div className="relative w-16 h-16 md:w-20 md:h-20 rounded-full bg-neutral-950 p-2 flex items-center justify-center shadow-inner group-hover:scale-110 transition-transform duration-300">
                                        {channel.stream_icon ? (
                                            <img src={channel.stream_icon} alt={channel.name} className="w-full h-full object-contain rounded-full" onError={(e) => { e.target.style.display = 'none' }} />
                                        ) : (
                                            < Tv size={24} className="text-neutral-700" />
                                        )}
                                        <div className="absolute inset-0 bg-black/40 rounded-full opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity">
                                            <Play size={20} className="text-white fill-current" />
                                        </div>
                                    </div>
                                    <div className="text-center w-full">
                                        <h3 className="text-sm font-medium text-neutral-300 group-hover:text-white truncate" title={channel.name}>{channel.name}</h3>
                                        <p className="text-xs text-neutral-600 mt-1 uppercase tracking-wider truncate">#{channel.num}</p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </div>

        </div>
    );
};

export default LiveTvPage;
