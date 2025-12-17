<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IPTV Player</title>
    <title>IPTV Player</title>
    <!-- Google Fonts: JetBrains Mono (Nerd Font style) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700;800&display=swap" rel="stylesheet">
    <!-- TailwindCSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            red: '#E50914',
                            black: '#141414',
                            dark: '#0f0f0f'
                        }
                    }
                }
            }
        }
    </script>
    <!-- Vue.js 3 -->
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
</head>

<body class="h-screen flex flex-col font-sans bg-black text-gray-200 selection:bg-red-600 selection:text-white">
    <?php
    $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (isset($_SESSION['user']) && $currentPath === '/'):
        ?>
        <!-- Header / Menu -->
        <header class="absolute top-4 right-4 p-6 z-50">
            <div class="relative">
                <!-- Profile Button -->
                <button id="menu-btn"
                    class="group flex items-center justify-center p-2 rounded-full hover:bg-white/5 transition-all focus:outline-none">
                    <div
                        class="h-14 w-14 rounded-full bg-red-600 flex items-center justify-center shadow-lg shadow-red-900/40 group-hover:scale-105 transition-transform duration-300">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                            class="w-8 h-8 text-white">
                            <path fill-rule="evenodd"
                                d="M7.5 6a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0zM3.751 20.105a8.25 8.25 0 0116.498 0 .75.75 0 01-.437.695A18.683 18.683 0 0112 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 01-.437-.695z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                </button>

                <!-- Dropdown Menu -->
                <div id="menu-dropdown"
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
                        <a href="/logout"
                            class="px-4 py-3 text-sm text-red-400 hover:bg-red-900/20 hover:text-red-300 transition-colors flex items-center gap-3">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" class="w-5 h-5 text-red-500">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                            </svg>
                            Déconnexion
                        </a>
                    </nav>
                </div>
            </div>
        </header>

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const menuBtn = document.getElementById('menu-btn');
                const menuDropdown = document.getElementById('menu-dropdown');

                if (menuBtn && menuDropdown) {
                    // Toggle menu
                    menuBtn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        menuDropdown.classList.toggle('hidden');
                    });

                    // Close when clicking outside
                    document.addEventListener('click', (e) => {
                        if (!menuDropdown.contains(e.target) && !menuBtn.contains(e.target)) {
                            menuDropdown.classList.add('hidden');
                        }
                    });

                    // Close on Escape key
                    document.addEventListener('keydown', (e) => {
                        if (e.key === 'Escape') {
                            menuDropdown.classList.add('hidden');
                        }
                    });
                }
            });
        </script>
    <?php endif; ?>

    <?php
    $classesMain = isset($modePleinEcran) && $modePleinEcran ? 'w-full h-full p-0 flex-grow bg-black' : 'flex-grow container mx-auto p-4';
    ?>

    <main id="app" class="<?= $classesMain ?>">
        <?= $content ?>
    </main>

    <?php if (!isset($modePleinEcran) || !$modePleinEcran): ?>
        <footer class="bg-black p-6 text-center text-gray-600 text-xs border-t border-gray-900">
            &copy; <?= date('Y') ?> IPTV PLAYER &bull; <span class="text-red-900">POWERED BY PHP</span>
        </footer>
    <?php endif; ?>

    <?= $scripts ?? '' ?>
</body>

</html>