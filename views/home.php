<?php
// On récupère les dernières sorties
$moviesController = new App\Controllers\MoviesController();
$seriesController = new App\Controllers\SeriesController();

$recentMovies = $moviesController->getRecent(15);
$recentSeries = $seriesController->getRecent(15);

// Mode plein écran pour désactiver le header global du layout (on utilise notre sidebar)
$modePleinEcran = true;
ob_start();
?>

<div class="flex h-screen overflow-hidden bg-[#0b0b0b]">
    <!-- Sidebar -->
    <?php require __DIR__ . '/components/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col relative overflow-hidden">

        <!-- Top Bar (Search & Profile) -->
        <div
            class="absolute top-0 left-0 right-0 z-20 px-8 py-6 flex items-center justify-between bg-gradient-to-b from-black/80 to-transparent pointer-events-none">
            <!-- Search Bar (Functional) -->
            <div class="pointer-events-auto w-full max-w-lg">
                <form action="/movies" method="GET" class="relative group">
                    <input type="text" name="q" placeholder="Search or paste link"
                        class="w-full bg-[#1a1a1a]/80 backdrop-blur border border-transparent focus:border-red-900/50 text-gray-200 text-sm rounded-full py-3 pl-12 pr-4 shadow-lg focus:outline-none focus:ring-1 focus:ring-red-900 transition-all">
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
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-gray-500">
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

        <!-- Scrollable Content -->
        <div
            class="flex-1 overflow-y-auto overflow-x-hidden pt-24 pb-10 px-16 scrollbar-thin scrollbar-thumb-red-900 scrollbar-track-transparent">

            <!-- Section Movies -->
            <div class="mb-12 relative group/slider">
                <div class="flex items-center justify-between mb-6 px-1">
                    <h2 class="text-2xl font-bold text-gray-100 flex items-center gap-2">
                        Popular - Movie
                    </h2>
                    <a href="/movies"
                        class="text-xs font-bold text-gray-500 hover:text-white transition flex items-center gap-1">See
                        All <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg></a>
                </div>

                <!-- Scroll Buttons -->
                <!-- Scroll Buttons -->
                <button
                    class="absolute left-0 top-1/2 -translate-y-1/2 z-10 bg-black/50 hover:bg-red-600/80 text-white p-3 rounded-full opacity-0 group-hover/slider:opacity-100 transition-all duration-300 backdrop-blur-sm -ml-4 shadow-xl border border-white/10 hidden md:hidden">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
                <button
                    class="absolute right-0 top-1/2 -translate-y-1/2 z-10 bg-black/50 hover:bg-red-600/80 text-white p-3 rounded-full opacity-0 group-hover/slider:opacity-100 transition-all duration-300 backdrop-blur-sm -mr-4 shadow-xl border border-white/10 hidden md:block">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>

                <!-- Scroll Container -->
                <div class="flex gap-5 overflow-x-auto scroll-smooth pb-8 pt-2 scrollbar-hide snap-x">
                    <?php if (empty($recentMovies)): ?>
                        <p class="text-gray-500 text-sm">Aucun film récent trouvé.</p>
                    <?php else: ?>
                        <?php foreach ($recentMovies as $m): ?>
                            <a href="/movies/details?id=<?= $m['stream_id'] ?>"
                                class="group relative block w-32 md:w-40 lg:w-48 flex-shrink-0 snap-start">
                                <div
                                    class="aspect-[2/3] rounded-xl overflow-hidden bg-[#1a1a1a] shadow-lg shadow-black/50 transition-all duration-300 group-hover:shadow-red-900/20 group-hover:scale-105 ring-1 ring-white/5 group-hover:ring-red-600/40">
                                    <img src="<?= htmlspecialchars($m['stream_icon']) ?>"
                                        class="w-full h-full object-cover transition-transform duration-500" loading="lazy"
                                        onerror="this.src='https://via.placeholder.com/300x450?text=No+Poster'">

                                    <!-- Overlay -->
                                    <div
                                        class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex flex-col justify-end p-4">
                                        <h3 class="font-bold text-white text-sm leading-tight line-clamp-2 drop-shadow-md">
                                            <?= htmlspecialchars($m['display_name']) ?>
                                        </h3>
                                        <?php if (!empty($m['rating']) && $m['rating'] > 0): ?>
                                            <div class="flex items-center gap-1 text-yellow-500 text-xs font-bold mt-1">
                                                <span>★</span> <?= round($m['rating'], 1) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="mt-3 text-center">
                                    <h4
                                        class="text-gray-300 text-xs font-bold truncate group-hover:text-red-500 transition-colors">
                                        <?= htmlspecialchars($m['display_name']) ?>
                                    </h4>
                                    <?php if (!empty($m['year'])): ?>
                                        <p class="text-gray-600 text-[10px]"><?= $m['year'] ?></p>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Section Series -->
            <div class="mb-12 relative group/slider">
                <div class="flex items-center justify-between mb-6 px-1">
                    <h2 class="text-2xl font-bold text-gray-100">Popular - Series</h2>
                    <a href="/series"
                        class="text-xs font-bold text-gray-500 hover:text-white transition flex items-center gap-1">See
                        All <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg></a>
                </div>

                <!-- Scroll Buttons -->
                <button
                    class="absolute left-0 top-1/2 -translate-y-1/2 z-10 bg-black/50 hover:bg-red-600/80 text-white p-3 rounded-full opacity-0 group-hover/slider:opacity-100 transition-all duration-300 backdrop-blur-sm -ml-4 shadow-xl border border-white/10 hidden md:hidden">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
                <button
                    class="absolute right-0 top-1/2 -translate-y-1/2 z-10 bg-black/50 hover:bg-red-600/80 text-white p-3 rounded-full opacity-0 group-hover/slider:opacity-100 transition-all duration-300 backdrop-blur-sm -mr-4 shadow-xl border border-white/10 hidden md:block">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>

                <div class="flex gap-5 overflow-x-auto scroll-smooth pb-8 pt-2 scrollbar-hide snap-x">
                    <?php if (empty($recentSeries)): ?>
                        <p class="text-gray-500 text-sm">Aucune série récente trouvée.</p>
                    <?php else: ?>
                        <?php foreach ($recentSeries as $s): ?>
                            <a href="/series/details?id=<?= $s['series_id'] ?>"
                                class="group relative block w-32 md:w-40 lg:w-48 flex-shrink-0 snap-start">
                                <div
                                    class="aspect-[2/3] rounded-xl overflow-hidden bg-[#1a1a1a] shadow-lg shadow-black/50 transition-all duration-300 group-hover:shadow-red-900/20 group-hover:scale-105 ring-1 ring-white/5 group-hover:ring-red-600/40">
                                    <img src="<?= htmlspecialchars($s['cover']) ?>"
                                        class="w-full h-full object-cover transition-transform duration-500" loading="lazy"
                                        onerror="this.src='https://via.placeholder.com/300x450?text=No+Poster'">

                                    <div
                                        class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex flex-col justify-end p-4">
                                        <h3 class="font-bold text-white text-sm leading-tight line-clamp-2 drop-shadow-md">
                                            <?= htmlspecialchars($s['display_name']) ?>
                                        </h3>
                                        <?php if (!empty($s['rating']) && $s['rating'] > 0): ?>
                                            <div class="flex items-center gap-1 text-yellow-500 text-xs font-bold mt-1">
                                                <span>★</span> <?= round($s['rating'], 1) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="mt-3 text-center">
                                    <h4
                                        class="text-gray-300 text-xs font-bold truncate group-hover:text-red-500 transition-colors">
                                        <?= htmlspecialchars($s['display_name']) ?>
                                    </h4>
                                    <?php if (!empty($s['year'])): ?>
                                        <p class="text-gray-600 text-[10px]"><?= $s['year'] ?></p>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const sliders = document.querySelectorAll('.group\\/slider');

        sliders.forEach(slider => {
            const container = slider.querySelector('.overflow-x-auto');
            const leftBtn = slider.querySelector('button:first-of-type');
            const rightBtn = slider.querySelector('button:last-of-type');

            if (!container || !leftBtn) return;

            // Initial check
            updateArrows(container, leftBtn, rightBtn);

            // Listen for scroll
            container.addEventListener('scroll', () => {
                updateArrows(container, leftBtn, rightBtn);
            });

            // Button clicks using the shared buttons
            leftBtn.addEventListener('click', () => {
                container.scrollBy({ left: -500, behavior: 'smooth' });
            });

            rightBtn.addEventListener('click', () => {
                container.scrollBy({ left: 500, behavior: 'smooth' });
            });
        });

        function updateArrows(container, leftBtn, rightBtn) {
            // Hide/Show Left Button
            if (container.scrollLeft > 0) {
                leftBtn.classList.remove('hidden', 'md:hidden');
                leftBtn.classList.add('md:block');
            } else {
                leftBtn.classList.add('hidden', 'md:hidden');
                leftBtn.classList.remove('md:block');
            }

            // Optional: Hide right button if at end? 
            // For now, let's keep the right button always visible as requested, 
            // or we could check (container.scrollLeft + container.clientWidth >= container.scrollWidth - 10)
        }
    });

    // Remove the old inline onclick function since we now use EventListeners
    // function scrollSection(btn, offset) { ... }
</script>

<style>
    /* Hide scrollbar for Chrome, Safari and Opera */
    .scrollbar-hide::-webkit-scrollbar {
        display: none;
    }

    /* Hide scrollbar for IE, Edge and Firefox */
    .scrollbar-hide {
        -ms-overflow-style: none;
        /* IE and Edge */
        scrollbar-width: none;
        /* Firefox */
    }
</style>

<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/layout.php'; ?>