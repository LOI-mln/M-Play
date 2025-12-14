import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import useAuthStore from '../store/useAuthStore';
import { Tv, User, Lock, Server, AlertCircle } from 'lucide-react';
import { clsx } from 'clsx';

const LoginPage = () => {
    const navigate = useNavigate();
    const { login, isAuthenticated, isLoading, error, clearError } = useAuthStore();

    const [formData, setFormData] = useState({
        dns: '',
        username: '',
        password: ''
    });

    useEffect(() => {
        if (isAuthenticated) {
            navigate('/dashboard');
        }
    }, [isAuthenticated, navigate]);

    const handleSubmit = async (e) => {
        e.preventDefault();
        clearError();
        const success = await login(formData.dns, formData.username, formData.password);
        if (success) {
            navigate('/dashboard');
        }
    };

    const handleChange = (e) => {
        setFormData({ ...formData, [e.target.name]: e.target.value });
    };

    return (
        <div className="min-h-screen bg-black flex items-center justify-center p-4 bg-gradient-to-br from-neutral-900 to-black">
            <div className="w-full max-w-md bg-neutral-900/80 border border-neutral-800 p-8 rounded-2xl shadow-2xl backdrop-blur-md">
                <div className="flex flex-col items-center mb-8">
                    <div className="bg-red-600 p-3 rounded-full mb-4 shadow-lg shadow-red-900/20">
                        <Tv size={32} className="text-white" />
                    </div>
                    <h1 className="text-3xl font-bold text-white tracking-tight">XC Player</h1>
                    <p className="text-neutral-400 mt-2">Enter your Xtream Codes API details</p>
                </div>

                {error && (
                    <div className="mb-6 p-4 bg-red-500/10 border border-red-500/20 rounded-lg flex items-center gap-3 text-red-400">
                        <AlertCircle size={20} />
                        <span className="text-sm font-medium">{error}</span>
                    </div>
                )}

                <form onSubmit={handleSubmit} className="space-y-5">
                    <div className="space-y-1">
                        <label className="text-xs font-semibold text-neutral-500 uppercase tracking-wider ml-1">Server URL</label>
                        <div className="relative group">
                            <Server className="absolute left-3 top-1/2 -translate-y-1/2 text-neutral-500 group-focus-within:text-red-500 transition-colors" size={20} />
                            <input
                                type="text"
                                name="dns"
                                value={formData.dns}
                                onChange={handleChange}
                                placeholder="http://domain.com:port"
                                className="w-full bg-neutral-950 border border-neutral-800 rounded-lg py-3 pl-10 pr-4 text-white placeholder-neutral-600 focus:outline-none focus:border-red-600 focus:ring-1 focus:ring-red-600 transition-all font-mono text-sm"
                                required
                            />
                        </div>
                    </div>

                    <div className="space-y-1">
                        <label className="text-xs font-semibold text-neutral-500 uppercase tracking-wider ml-1">Username</label>
                        <div className="relative group">
                            <User className="absolute left-3 top-1/2 -translate-y-1/2 text-neutral-500 group-focus-within:text-red-500 transition-colors" size={20} />
                            <input
                                type="text"
                                name="username"
                                value={formData.username}
                                onChange={handleChange}
                                placeholder="Username"
                                className="w-full bg-neutral-950 border border-neutral-800 rounded-lg py-3 pl-10 pr-4 text-white placeholder-neutral-600 focus:outline-none focus:border-red-600 focus:ring-1 focus:ring-red-600 transition-all"
                                required
                            />
                        </div>
                    </div>

                    <div className="space-y-1">
                        <label className="text-xs font-semibold text-neutral-500 uppercase tracking-wider ml-1">Password</label>
                        <div className="relative group">
                            <Lock className="absolute left-3 top-1/2 -translate-y-1/2 text-neutral-500 group-focus-within:text-red-500 transition-colors" size={20} />
                            <input
                                type="password"
                                name="password"
                                value={formData.password}
                                onChange={handleChange}
                                placeholder="Password"
                                className="w-full bg-neutral-950 border border-neutral-800 rounded-lg py-3 pl-10 pr-4 text-white placeholder-neutral-600 focus:outline-none focus:border-red-600 focus:ring-1 focus:ring-red-600 transition-all"
                                required
                            />
                        </div>
                    </div>

                    <button
                        type="submit"
                        disabled={isLoading}
                        className={clsx(
                            "w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3.5 rounded-lg transition-all transform active:scale-[0.98] shadow-lg shadow-red-900/30 flex items-center justify-center gap-2 mt-2",
                            isLoading && "opacity-70 cursor-not-allowed"
                        )}
                    >
                        {isLoading ? (
                            <>
                                <div className="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                                <span>Connecting...</span>
                            </>
                        ) : (
                            <span>Login</span>
                        )}
                    </button>
                </form>
            </div>
        </div>
    );
};

export default LoginPage;
