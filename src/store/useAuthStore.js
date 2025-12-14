import { create } from 'zustand';
import { persist } from 'zustand/middleware';
import { loginUser } from '../api/auth';

const useAuthStore = create(
    persist(
        (set, get) => ({
            user: null,
            isAuthenticated: false,
            serverInfo: null,
            userInfo: null,
            isLoading: false,
            error: null,
            dns: '',

            login: async (dns, username, password) => {
                set({ isLoading: true, error: null });
                const result = await loginUser(dns, username, password);

                if (result.success) {
                    set({
                        isAuthenticated: true,
                        user: { username, password },
                        userInfo: result.data.user_info,
                        serverInfo: result.data.server_info,
                        dns: dns,
                        isLoading: false
                    });
                    return true;
                } else {
                    set({
                        isAuthenticated: false,
                        error: result.error,
                        isLoading: false
                    });
                    return false;
                }
            },

            logout: () => {
                set({
                    user: null,
                    isAuthenticated: false,
                    serverInfo: null,
                    userInfo: null,
                    dns: '',
                    error: null
                });
            },

            clearError: () => set({ error: null })
        }),
        {
            name: 'auth-storage', // unique name
            partialize: (state) => ({
                user: state.user,
                dns: state.dns,
                isAuthenticated: state.isAuthenticated,
                userInfo: state.userInfo,
                serverInfo: state.serverInfo
            }),
        }
    )
);

export default useAuthStore;
