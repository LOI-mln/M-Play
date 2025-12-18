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

        <?php require __DIR__ . '/components/topbar.php'; ?>

        <!-- Scrollable Content -->
        <div
            class="flex-1 overflow-y-auto overflow-x-hidden pb-10 px-16 scrollbar-thin scrollbar-thumb-red-900 scrollbar-track-transparent">

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
                            <a href="/movies/details?id=<?= $m['stream_id'] ?>&from=home"
                                class="group relative block w-32 md:w-40 lg:w-48 flex-shrink-0 snap-start">
                                <div
                                    class="aspect-[2/3] rounded-xl overflow-hidden bg-[#1a1a1a] shadow-lg shadow-black/50 transition-all duration-300 group-hover:shadow-red-900/20 group-hover:scale-105 ring-1 ring-white/5 group-hover:ring-red-600/40">
                                    <img src="<?= htmlspecialchars($m['stream_icon']) ?>"
                                        class="w-full h-full object-cover transition-transform duration-500" loading="lazy"
                                        onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'w-full h-full bg-[#0f172a] flex flex-col items-center justify-center p-4 text-center\'><svg class=\'w-12 h-12 text-gray-500 mb-2\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'1.5\' d=\'M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z\' /></svg><span class=\'text-gray-300 font-bold text-sm leading-tight line-clamp-2\'><?= addslashes(htmlspecialchars($m['display_name'])) ?></span></div>';">

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