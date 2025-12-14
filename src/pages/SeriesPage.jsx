import React, { useEffect, useState, useMemo } from 'react';
import { useNavigate } from 'react-router-dom';
import { getSeriesCategories, getSeries } from '../api/content';
import useAuthStore from '../store/useAuthStore';
import { ArrowLeft, Search, MonitorPlay, AlertCircle } from 'lucide-react';
import { clsx } from 'clsx';

import { batchRequests, filterCategoriesByLang } from '../utils/batch';

const SeriesPage = () => {
    const navigate = useNavigate();
    const { dns, user, isAuthenticated } = useAuthStore();

    const [categories, setCategories] = useState([]);
    const [seriesList, setSeriesList] = useState([]);

    const [selectedCategory, setSelectedCategory] = useState(null);
    const [searchQuery, setSearchQuery] = useState('');

    const [loadingCategories, setLoadingCategories] = useState(true);
    const [loadingSeries, setLoadingSeries] = useState(false);
    const [progress, setProgress] = useState(0);
    const [visibleCount, setVisibleCount] = useState(50); // Pagination limit

    useEffect(() => {
        if (!isAuthenticated) return;

        const loadCategories = async () => {
            setLoadingCategories(true);
            const data = await getSeriesCategories(dns, user.username, user.password);

            const specialCats = [
                { category_id: 'all_fr', category_name: '🇫🇷 All French' },
                { category_id: 'all_en', category_name: '🇺🇸 All English' },
            ];

            setCategories([...specialCats, ...data]);
            setSelectedCategory('all_fr');
            setLoadingCategories(false);
        };

        loadCategories();
    }, [isAuthenticated, dns, user]);

    useEffect(() => {
        if (!selectedCategory || !isAuthenticated) return;

        const loadSeries = async () => {
            setLoadingSeries(true);
            setProgress(0);
            setSeriesList([]);
            setVisibleCount(50);

            if (selectedCategory === 'all_fr' || selectedCategory === 'all_en') {
                const lang = selectedCategory === 'all_fr' ? 'fr' : 'en';
                const realCategories = categories.filter(c => c.category_id !== 'all_fr' && c.category_id !== 'all_en');
                const targetCats = filterCategoriesByLang(realCategories, lang);

                if (targetCats.length === 0) {
                    setSeriesList([]);
                    setLoadingSeries(false);
                    return;
                }

                const batchSize = 5;
                let allSeries = [];

                for (let i = 0; i < targetCats.length; i += batchSize) {
                    const batch = targetCats.slice(i, i + batchSize);
                    const batchResults = await Promise.all(
                        batch.map(cat => getSeries(dns, user.username, user.password, cat.category_id))
                    );
                    batchResults.forEach(res => allSeries.push(...res));

                    setProgress(Math.round(((i + batch.length) / targetCats.length) * 100));
                }

                setSeriesList(allSeries);

            } else {
                // Standard Single Category
                const data = await getSeries(dns, user.username, user.password, selectedCategory);
                setSeriesList(data);
            }

            setLoadingSeries(false);
        };

        loadSeries();
    }, [selectedCategory, isAuthenticated, dns, user]);

    const filteredSeries = useMemo(() => {
        if (!searchQuery) return seriesList;
        return seriesList.filter(s => s.name.toLowerCase().includes(searchQuery.toLowerCase()));
    }, [seriesList, searchQuery]);

    const handleSeriesClick = (seriesId) => {
        navigate(`/series/${seriesId}`);
    };

    return (
        <div className="h-screen w-full bg-neutral-950 flex flex-col md:flex-row overflow-hidden text-white">

            {/* Sidebar: Categories */}
            <div className="w-full md:w-80 flex-shrink-0 bg-neutral-900 border-r border-neutral-800 flex flex-col">
                <div className="p-4 border-b border-neutral-800 flex items-center gap-3">
                    <button onClick={() => navigate('/dashboard')} className="p-2 hover:bg-neutral-800 rounded-full transition-colors text-neutral-400 hover:text-white">
                        <ArrowLeft size={20} />
                    </button>
                    <h2 className="font-bold text-lg">TV Series</h2>
                </div>

                <div className="flex-1 overflow-y-auto p-2 space-y-1 scrollbar-thin scrollbar-thumb-neutral-700">
                    {loadingCategories ? (
                        <div className="p-4 text-center text-neutral-500">Loading categories...</div>
                    ) : (
                        categories.map(cat => (
                            <button
                                key={cat.category_id}
                                onClick={() => {
                                    setSelectedCategory(cat.category_id);
                                    setSearchQuery('');
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

            {/* Main Content: Series Grid */}
            <div className="flex-1 flex flex-col h-full bg-black">
                {/* Toolbar */}
                <div className="h-16 border-b border-neutral-800 flex items-center justify-between px-6 bg-neutral-900/50 backdrop-blur-sm z-10">
                    <h2 className="font-bold text-lg hidden md:block">
                        {categories.find(c => c.category_id === selectedCategory)?.category_name || 'Series'}
                    </h2>

                    <div className="relative w-full md:w-64">
                        <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-neutral-500" size={18} />
                        <input
                            type="text"
                            placeholder="Search series..."
                            value={searchQuery}
                            onChange={(e) => setSearchQuery(e.target.value)}
                            className="w-full bg-neutral-950 border border-neutral-800 rounded-full py-2 pl-10 pr-4 text-sm focus:outline-none focus:border-red-600 focus:ring-1 focus:ring-red-600 transition-all"
                        />
                    </div>
                </div>

                {/* Grid */}
                <div className="flex-1 overflow-y-auto p-6 scrollbar-thin scrollbar-thumb-neutral-800" onScroll={(e) => {
                    if (e.target.scrollHeight - e.target.scrollTop < e.target.clientHeight + 200) {
                        // could trigger auto load more
                    }
                }}>
                    {loadingSeries ? (
                        <div className="flex flex-col items-center justify-center h-full text-neutral-500 gap-3">
                            <div className="animate-spin w-8 h-8 border-4 border-red-500 border-t-transparent rounded-full"></div>
                            <div className="text-lg font-medium">Loading Series...</div>
                            {(selectedCategory === 'all_fr' || selectedCategory === 'all_en') && (
                                <div className="w-64 h-2 bg-neutral-800 rounded-full overflow-hidden">
                                    <div
                                        className="h-full bg-red-600 transition-all duration-300"
                                        style={{ width: `${progress}%` }}
                                    />
                                </div>
                            )}
                        </div>
                    ) : filteredSeries.length === 0 ? (
                        <div className="flex flex-col items-center justify-center h-full text-neutral-500 gap-2">
                            <AlertCircle size={32} />
                            <p>No series found.</p>
                        </div>
                    ) : (
                        <>
                            <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                                {filteredSeries.slice(0, visibleCount).map(series => (
                                    <div
                                        key={series.series_id}
                                        onClick={() => handleSeriesClick(series.series_id)}
                                        className="group bg-neutral-900 hover:bg-neutral-800 rounded-xl overflow-hidden cursor-pointer transition-all hover:-translate-y-1 hover:shadow-xl hover:shadow-red-900/10 border border-neutral-800 hover:border-red-600/30 flex flex-col"
                                    >
                                        <div className="relative aspect-[2/3] bg-neutral-800">
                                            {series.cover ? (
                                                <img
                                                    src={series.cover}
                                                    alt={series.name}
                                                    loading="lazy"
                                                    className="w-full h-full object-cover"
                                                    onError={(e) => { e.target.style.display = 'none' }}
                                                />
                                            ) : (
                                                <div className="w-full h-full flex items-center justify-center text-neutral-700">
                                                    <MonitorPlay size={40} />
                                                </div>
                                            )}
                                            <div className="absolute inset-0 bg-red-600/10 opacity-0 group-hover:opacity-100 transition-opacity" />

                                            {series.rating && (
                                                <div className="absolute top-2 right-2 bg-black/70 px-2 py-0.5 rounded text-xs text-yellow-400 font-bold">
                                                    ★ {series.rating}
                                                </div>
                                            )}
                                        </div>
                                        <div className="p-3">
                                            <h3 className="text-sm font-medium text-neutral-300 group-hover:text-white truncate" title={series.name}>{series.name}</h3>
                                        </div>
                                    </div>
                                ))}
                            </div>

                            {visibleCount < filteredSeries.length && (
                                <div className="py-8 flex justify-center">
                                    <button
                                        onClick={() => setVisibleCount(prev => prev + 50)}
                                        className="px-6 py-2 bg-neutral-800 hover:bg-neutral-700 text-neutral-300 hover:text-white rounded-full transition-colors border border-neutral-700 hover:border-neutral-600"
                                    >
                                        Load More ({filteredSeries.length - visibleCount} remaining)
                                    </button>
                                </div>
                            )}
                        </>
                    )}
                </div>
            </div>

        </div>
    );
};

export default SeriesPage;
