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
            <!-- Continue Watching Section -->
            <?php
            // Récupere Films et Séries en cours
            $moviesController = new \App\Controllers\MoviesController();
            $cwMovies = $moviesController->getContinueWatching();
            
            $seriesController = new \App\Controllers\SeriesController();
            $cwSeries = $seriesController->getContinueWatching();

            // Fusionner et Trier par date de visionnage
            $continueWatching = array_merge($cwMovies, $cwSeries);
            
            usort($continueWatching, function($a, $b) {
                // Tri décroissant sur updated_at
                $tA = isset($a['updated_at']) ? strtotime($a['updated_at']) : 0;
                $tB = isset($b['updated_at']) ? strtotime($b['updated_at']) : 0;
                return $tB - $tA;
            });

            if (!empty($continueWatching)): 
            ?>
                <section class="mb-12">
                    <div class="flex items-center gap-3 mb-6 px-4 md:px-0">
                        <div class="w-1 h-6 bg-red-600 rounded-full"></div>
                        <h2 class="text-xl md:text-2xl font-bold text-white tracking-wide">Reprendre la lecture</h2>
                        <div class="h-px bg-gray-800 flex-grow ml-4"></div>
                    </div>

                    <div class="relative group/slider">
                        <!-- Navigation Buttons (Hidden by default, shown on hover) -->
                        <button class="absolute left-0 top-1/2 -translate-y-1/2 z-20 bg-black/50 hover:bg-red-600 text-white p-2 rounded-r-lg opacity-0 group-hover/slider:opacity-100 transition-all duration-300 backdrop-blur-sm" onclick="scrollSlider('slider-cw', -300)">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                        </button>
                        <button class="absolute right-0 top-1/2 -translate-y-1/2 z-20 bg-black/50 hover:bg-red-600 text-white p-2 rounded-l-lg opacity-0 group-hover/slider:opacity-100 transition-all duration-300 backdrop-blur-sm" onclick="scrollSlider('slider-cw', 300)">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                        </button>

                        <div id="slider-cw" class="flex gap-4 overflow-x-auto pb-4 px-4 md:px-0 scrollbar-hide scroll-smooth snap-x snap-mandatory">
                            <?php foreach ($continueWatching as $m): 
                                // Déterminer le lien selon le type
                                $type = $m['type'] ?? 'movie';
                                if ($type === 'series') {
                                    // Lien vers l'épisode spécifique avec métadonnées COMPLÈTES pour éviter le chargement infini
                                    $link = "/series/watch?id=" . $m['stream_id'] 
                                        . "&series_id=" . ($m['series_id'] ?? 0) 
                                        . "&ext=" . ($m['container_extension'] ?? 'mp4')
                                        . "&s=" . ($m['s'] ?? '')
                                        . "&e=" . ($m['e'] ?? '')
                                        . "&name=" . urlencode($m['name'] ?? '')
                                        . "&cover=" . urlencode($m['stream_icon'] ?? '')
                                        . "&duration=" . urlencode($m['duration'] ?? '');
                                } else {
                                    $link = "/movies/watch?id=" . $m['stream_id'] . "&ext=" . ($m['container_extension'] ?? 'mp4');
                                }
                            ?>
                                <a href="<?= $link ?>"
                                    class="group relative block w-32 md:w-40 lg:w-48 flex-shrink-0 snap-start cw-card"
                                    data-stream-id="<?= $m['stream_id'] ?>"
                                    data-type="<?= $type ?>"> <!-- Added data-type for JS deletion -->
                                <div
                                    class="aspect-[2/3] rounded-xl overflow-hidden bg-[#1a1a1a] shadow-lg shadow-black/50 transition-all duration-300 group-hover:shadow-red-900/20 ring-1 ring-white/5 group-hover:ring-red-600/40 relative z-0 transform-gpu">
                                    <div class="absolute inset-0 rounded-xl pointer-events-none border border-white/5 z-40"></div> <!-- Border Fix -->
                                    <img src="<?= !empty($m['stream_icon']) ? $m['stream_icon'] : '/ressources/logo.png' ?>"
                                        alt="<?= htmlspecialchars($m['name']) ?>"
                                        class="w-full h-full object-cover opacity-80 group-hover:opacity-60 transition-all duration-500 will-change-transform"
                                        loading="lazy">

                                        <!-- Hover Overlay (Now behind progress bar) -->
                                        <div
                                            class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex flex-col justify-center items-center">

                                            <!-- Play Icon Hint -->
                                            <div
                                                class="bg-red-600 rounded-full p-3 shadow-lg shadow-red-600/40 transform scale-90 transition-transform duration-300 mb-2 group/play">
                                                <svg class="w-8 h-8 text-white pl-1" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M8 5v14l11-7z" />
                                                </svg>
                                            </div>

                                            <!-- Title & Meta -->
                                            <div class="absolute bottom-4 left-0 w-full px-4 text-center">
                                                <h3
                                                    class="text-white text-sm font-bold leading-tight line-clamp-2 drop-shadow-lg mb-2 tracking-wide font-outfit">
                                                    <?= htmlspecialchars($m['name']) ?>
                                                </h3>
                                                
                                                <!-- Metadata Row -->
                                                <div class="flex items-center justify-center gap-2 text-xs text-gray-200 font-medium flex-wrap">
                                                    
                                                    <!-- Rating -->
                                                    <?php if (isset($m['rating']) && $m['rating'] > 0): ?>
                                                        <div class="hidden md:flex items-center gap-1 bg-black/60 px-2 py-0.5 rounded-md">
                                                            <span class="text-yellow-400">★</span> 
                                                            <span><?= number_format($m['rating'] ?? 0, 1) ?></span>
                                                        </div>
                                                    <?php endif; ?>

                                                    <!-- Progress % -->
                                                    <div class="flex items-center gap-1 bg-black/60 px-2 py-0.5 rounded-md">
                                                        <span><?= round($m['progress_percent']) ?>%</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    <!-- Permanent S/E Badge for Series -->
                                    <?php if (isset($m['s']) && isset($m['e'])): ?>
                                        <div class="absolute top-2 left-2 z-20 bg-red-600/90 backdrop-blur-sm text-white text-[10px] font-bold px-2 py-1 rounded shadow-md border border-red-500/30">
                                            S<?= str_pad($m['s'], 2, '0', STR_PAD_LEFT) ?> E<?= str_pad($m['e'], 2, '0', STR_PAD_LEFT) ?>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Progress Bar (Floating & Rounded) -->
                                    <div
                                        class="absolute bottom-2 left-2 right-2 h-1 bg-gray-700 rounded-full overflow-hidden z-20">
                                        <div class="h-full bg-red-600 rounded-full"
                                            style="width: <?= $m['progress_percent'] ?>%"></div>
                                    </div>

                                    <!-- Remove Button -->
                                    <button
                                        class="btn-remove-cw absolute top-2 right-2 bg-black/60 hover:bg-red-600 text-white rounded-full p-1.5 opacity-0 group-hover:opacity-100 transition-all z-30"
                                        title="Retirer de la liste">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

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
                <div class="flex gap-5 overflow-x-auto scroll-smooth pb-8 pt-2 pl-1 pr-2 scrollbar-hide snap-x">
                    <?php if (empty($recentMovies)): ?>
                        <p class="text-gray-500 text-sm">Aucun film récent trouvé.</p>
                    <?php else: ?>
                        <?php foreach ($recentMovies as $m): ?>
                            <a href="/movies/details?id=<?= $m['stream_id'] ?>&from=home"
                                class="group relative block w-32 md:w-40 lg:w-48 flex-shrink-0 snap-start">
                                <div
                                    class="aspect-[2/3] rounded-xl overflow-hidden bg-[#1a1a1a] shadow-lg shadow-black/50 transition-all duration-300 group-hover:shadow-red-900/20 ring-1 ring-white/5 group-hover:ring-red-600/40">
                                    <img src="<?= htmlspecialchars($m['stream_icon']) ?>"
                                        class="w-full h-full object-cover transition-transform duration-500" loading="lazy"
                                        onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'w-full h-full bg-[#0f172a] flex flex-col items-center justify-center p-4 text-center\'><svg class=\'w-12 h-12 text-gray-500 mb-2\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'1.5\' d=\'M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z\' /></svg><span class=\'text-gray-300 font-bold text-sm leading-tight line-clamp-2\'><?= addslashes(htmlspecialchars($m['display_name'])) ?></span></div>';">

                                    <!-- Overlay -->
                                    <div
                                        class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex flex-col justify-end p-6 pb-8 rounded-xl">
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

                <div class="flex gap-5 overflow-x-auto scroll-smooth pb-8 pt-2 pl-1 pr-2 scrollbar-hide snap-x">
                    <?php if (empty($recentSeries)): ?>
                        <p class="text-gray-500 text-sm">Aucune série récente trouvée.</p>
                    <?php else: ?>
                        <?php foreach ($recentSeries as $s): ?>
                            <a href="/series/details?id=<?= $s['series_id'] ?>&from=home"
                                class="group relative block w-32 md:w-40 lg:w-48 flex-shrink-0 snap-start">
                                <div
                                    class="aspect-[2/3] rounded-xl overflow-hidden bg-[#1a1a1a] shadow-lg shadow-black/50 transition-all duration-300 group-hover:shadow-red-900/20 ring-1 ring-white/5 group-hover:ring-red-600/40">
                                    <img src="<?= htmlspecialchars($s['cover']) ?>"
                                        class="w-full h-full object-cover transition-transform duration-500" loading="lazy"
                                        onerror="this.src='https://via.placeholder.com/300x450?text=No+Poster'">

                                    <div
                                        class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex flex-col justify-end p-6 pb-8 rounded-xl">
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
            if (container.scrollLeft > 120) {
                leftBtn.classList.remove('hidden', 'md:hidden');
                leftBtn.classList.add('md:block');
            } else {
                leftBtn.classList.add('hidden', 'md:hidden');
                leftBtn.classList.remove('md:block');
            }

            // Hide/Show Right Button
            if (container.scrollLeft + container.clientWidth >= container.scrollWidth - 10) {
                rightBtn.classList.add('hidden', 'md:hidden');
                rightBtn.classList.remove('md:block');
            } else {
                rightBtn.classList.remove('hidden', 'md:hidden');
                rightBtn.classList.add('md:block');
            }
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

<script>
    // Feature: Remove from Continue Watching
    document.addEventListener('DOMContentLoaded', () => {
        });
    });
</script>

<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/layout.php'; ?>