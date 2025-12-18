<!-- Top Bar (Search & Profile) -->
<div
    class="sticky top-0 left-0 right-0 z-20 px-8 py-6 flex items-center justify-between bg-gradient-to-b from-black/80 to-transparent pointer-events-none mb-4">
    <!-- Search Bar (Functional) -->
    <div class="pointer-events-auto w-full max-w-lg">
        <form action="/search" method="GET" class="relative group">
            <input type="text" name="q"
                placeholder="<?= htmlspecialchars($searchPlaceholder ?? 'Search or paste link') ?>"
                class="w-full bg-[#1a1a1a]/80 backdrop-blur border border-transparent focus:border-red-900/50 text-gray-200 text-sm rounded-full py-3 pl-12 pr-4 shadow-lg focus:outline-none focus:ring-1 focus:ring-red-900 transition-all"
                value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
            <svg class="w-5 h-5 text-gray-500 absolute left-4 top-1/2 -translate-y-1/2" fill="none"
                stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
        </form>
    </div>

    <!-- Header Actions (Profile) -->
    <div class="pointer-events-auto flex items-center gap-4">
        <div class="relative">
            <button id="profile-menu-btn" class="text-gray-400 hover:text-white transition focus:outline-none">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
            </button>

            <!-- Dropdown Menu -->
            <div id="profile-menu-dropdown"
                class="hidden absolute right-0 mt-2 w-64 bg-[#141414] border border-gray-800 rounded-lg shadow-xl overflow-hidden transform transition-all duration-200 origin-top-right z-50">
                <div class="p-4 border-b border-gray-800 bg-[#0f0f0f]">
                    <p class="text-xs text-gray-500 uppercase tracking-widest mb-1">Playlist</p>
                    <p class="text-white font-bold truncate"
                        title="<?= htmlspecialchars($_SESSION['playlist_name'] ?? 'Ma Playlist') ?>">
                        <?= htmlspecialchars($_SESSION['playlist_name'] ?? 'Ma Playlist') ?>
                    </p>
                </div>
                <nav class="flex flex-col">
                    <a href="/login"
                        class="px-4 py-3 text-sm text-gray-300 hover:bg-gray-800 hover:text-white transition-colors flex items-center gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="w-5 h-5 text-gray-500">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5H9.512A3 3 0 017.5 6v6m13.5 0v5a3 3 0 01-3 3h-5" />
                        </svg>
                        Changer de playlist
                    </a>
                </nav>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const btn = document.getElementById('profile-menu-btn');
                const dropdown = document.getElementById('profile-menu-dropdown');

                if (btn && dropdown) {
                    btn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        dropdown.classList.toggle('hidden');
                    });

                    document.addEventListener('click', (e) => {
                        if (!dropdown.contains(e.target) && !btn.contains(e.target)) {
                            dropdown.classList.add('hidden');
                        }
                    });

                    document.addEventListener('keydown', (e) => {
                        if (e.key === 'Escape') {
                            dropdown.classList.add('hidden');
                        }
                    });
                }
            });
        </script>
    </div>
</div>