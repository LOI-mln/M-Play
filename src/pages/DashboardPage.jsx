import React from 'react';
import { useNavigate } from 'react-router-dom';
import useAuthStore from '../store/useAuthStore';
import { Tv, Film, MonitorPlay, LogOut, Calendar, User } from 'lucide-react';

const DashboardPage = () => {
    const navigate = useNavigate();
    const { userInfo, serverInfo, logout } = useAuthStore();

    const handleLogout = () => {
        logout();
        navigate('/login');
    };

    // Format expiry date if available
    const expiryDate = userInfo?.exp_date
        ? new Date(parseInt(userInfo.exp_date) * 1000).toLocaleDateString()
        : 'Unknown';

    return (
        <div className="min-h-screen bg-black text-white p-6 md:p-12 font-sans">
            {/* Header */}
            <header className="flex justify-between items-center mb-12">
                <div className="flex items-center gap-4">
                    <div className="bg-red-600 p-3 rounded-full shadow-lg shadow-red-900/40">
                        <Tv size={28} className="text-white" />
                    </div>
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">M-Play</h1>
                        <div className="flex items-center gap-2 text-neutral-400 text-sm">
                            <span className="w-2 h-2 rounded-full bg-green-500"></span>
                            <span>{serverInfo?.url || 'Connected'}</span>
                        </div>
                    </div>
                </div>

                <div className="flex items-center gap-6">
                    <div className="hidden md:block text-right">
                        <p className="text-sm font-semibold text-neutral-200">{userInfo?.username}</p>
                        <div className="flex items-center gap-1.5 text-xs text-neutral-500">
                            <Calendar size={12} />
                            <span>Expires: {expiryDate}</span>
                        </div>
                    </div>
                    <button
                        onClick={handleLogout}
                        className="bg-neutral-900 hover:bg-neutral-800 p-3 rounded-full transition-colors border border-neutral-800"
                        title="Logout"
                    >
                        <LogOut size={20} className="text-neutral-400" />
                    </button>
                </div>
            </header>

            {/* Main Tiles */}
            <div className="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-3 gap-6">

                {/* LIVE TV */}
                <div
                    onClick={() => navigate('/live')}
                    className="group relative h-64 md:h-80 bg-neutral-900 rounded-3xl border border-neutral-800 overflow-hidden cursor-pointer hover:border-red-600/50 transition-all duration-300 shadow-2xl hover:shadow-red-900/10"
                >
                    <div className="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent z-10" />
                    <div className="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1593359677879-a4bb92f829d1?q=80&w=2070&auto=format&fit=crop')] bg-cover bg-center group-hover:scale-105 transition-transform duration-700 opacity-60" />

                    <div className="absolute bottom-0 left-0 p-8 z-20">
                        <div className="bg-red-600 w-12 h-12 rounded-2xl flex items-center justify-center mb-4 shadow-lg shadow-red-600/20 group-hover:scale-110 transition-transform">
                            <Tv size={24} className="text-white" />
                        </div>
                        <h2 className="text-3xl font-bold text-white mb-1">Live TV</h2>
                        <p className="text-neutral-400">Watch live channels</p>
                    </div>
                </div>

                {/* SERIES */}
                <div
                    onClick={() => navigate('/series')}
                    className="group relative h-64 md:h-80 bg-neutral-900 rounded-3xl border border-neutral-800 overflow-hidden cursor-pointer hover:border-red-600/50 transition-all duration-300 shadow-2xl hover:shadow-red-900/10"
                >
                    <div className="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent z-10" />
                    <div className="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1542204165-65bf26472b9b?q=80&w=2074&auto=format&fit=crop')] bg-cover bg-center group-hover:scale-105 transition-transform duration-700 opacity-60" />

                    <div className="absolute bottom-0 left-0 p-8 z-20">
                        <div className="bg-red-600 w-12 h-12 rounded-2xl flex items-center justify-center mb-4 shadow-lg shadow-red-600/20 group-hover:scale-110 transition-transform">
                            <MonitorPlay size={24} className="text-white" />
                        </div>
                        <h2 className="text-3xl font-bold text-white mb-1">Series</h2>
                        <p className="text-neutral-400">Binge watch TV shows</p>
                    </div>
                </div>

                {/* MOVIES */}
                <div
                    onClick={() => navigate('/movies')}
                    className="group relative h-64 md:h-80 bg-neutral-900 rounded-3xl border border-neutral-800 overflow-hidden cursor-pointer hover:border-red-600/50 transition-all duration-300 shadow-2xl hover:shadow-red-900/10"
                >
                    <div className="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent z-10" />
                    <div className="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?q=80&w=2070&auto=format&fit=crop')] bg-cover bg-center group-hover:scale-105 transition-transform duration-700 opacity-60" />

                    <div className="absolute bottom-0 left-0 p-8 z-20">
                        <div className="bg-red-600 w-12 h-12 rounded-2xl flex items-center justify-center mb-4 shadow-lg shadow-red-600/20 group-hover:scale-110 transition-transform">
                            <Film size={24} className="text-white" />
                        </div>
                        <h2 className="text-3xl font-bold text-white mb-1">Movies</h2>
                        <p className="text-neutral-400">Latest films on demand</p>
                    </div>
                </div>

            </div>

            <div className="mt-12 text-center text-neutral-600 text-sm">
                M-Play v2.0 • Premium OTT Experience
            </div>
        </div>
    );
};

export default DashboardPage;
