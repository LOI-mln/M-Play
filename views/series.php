<?php
$title = "Séries - M-Play";
$modePleinEcran = true; // Layout pleine page
// Define search context for topbar
$searchAction = '/series';
$searchPlaceholder = 'Rechercher une série...';

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
            class="flex-1 overflow-y-auto overflow-x-hidden pb-10 px-8 lg:px-12 scrollbar-thin scrollbar-thumb-red-900 scrollbar-track-transparent">

            <!-- Title & Categories Header -->
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-white flex items-center gap-3">
                    <span class="text-red-600">SÉRIES</span> TV
                </h2>
            </div>

            <!-- Categories Horizontal List -->
            <?php if (!empty($categories)): ?>
                <div class="mb-8">
                    <div class="flex gap-3 overflow-x-auto pb-4 scrollbar-hide snap-x">
                        <a href="/series"
                            class="px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap transition-all snap-start <?= !isset($_GET['categorie']) ? 'bg-red-600 text-white shadow-lg shadow-red-900/40' : 'bg-[#1a1a1a] text-gray-400 hover:bg-[#252525] hover:text-white border border-white/5' ?>">
                            Tous
                        </a>
                        <?php foreach ($categories as $cat): ?>
                            <a href="/series?categorie=<?= $cat['category_id'] ?>"
                                class="px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap transition-all snap-start <?= (isset($_GET['categorie']) && $_GET['categorie'] == $cat['category_id']) ? 'bg-red-600 text-white shadow-lg shadow-red-900/40' : 'bg-[#1a1a1a] text-gray-400 hover:bg-[#252525] hover:text-white border border-white/5' ?>">
                                <?= htmlspecialchars($cat['category_name']) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Loading Spinner -->
            <div id="loading-spinner" class="col-span-full flex flex-col items-center justify-center py-20">
                <div class="animate-spin rounded-full h-16 w-16 border-t-2 border-b-2 border-[#E50914]"></div>
                <p class="mt-4 text-gray-400 animate-pulse">Chargement des séries...</p>
            </div>

            <!-- Series Grid Container -->
            <div id="series-grid"
                class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-6">
                <!-- Javascript will populate this -->
            </div>

        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const grid = document.getElementById('series-grid');
        const spinner = document.getElementById('loading-spinner');

        // Construct AJAX URL based on current parameters
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.set('ajax', '1');
        const fetchUrl = window.location.pathname + '?' + urlParams.toString();

        fetch(fetchUrl)
            .then(response => response.json())
            .then(series => {
                spinner.style.display = 'none';

                if (!series || series.length === 0) {
                    grid.innerHTML = '<div class="col-span-full text-center text-gray-500 py-10">Aucune série trouvée.</div>';
                    return;
                }

                // Progressive Rendering
                let index = 0;
                const batchSize = 50;

                function renderBatch() {
                    const batch = series.slice(index, index + batchSize);
                    if (batch.length === 0) return;

                    let html = '';
                    batch.forEach(s => {
                        const title = s.display_name || s.name;
                        const cleanTitle = title.replace(/"/g, '&quot;');
                        const seriesId = s.series_id;
                        const poster = s.cover; // Series uses 'cover', not 'stream_icon'
                        const rating = s.rating ? parseFloat(s.rating) : 0;
                        const ratingHtml = rating > 0 ? `<div class="flex items-center gap-1 text-yellow-500 text-xs font-bold"><span>★</span> ${rating}</div>` : '';

                        // Year extraction (Series often relies on name parser or has 'releaseDate')
                        // Assuming 'year' might not be in the list API for series, we try standard fields or fallback.
                        const year = s.releaseDate ? s.releaseDate.substring(0, 4) : '';

                        html += `
                    <a href="/series/details?id=${seriesId}" class="group relative cursor-pointer block">
                        <div class="aspect-[2/3] rounded-lg overflow-hidden bg-[#111] border border-gray-800 hover:border-red-600 transition-all shadow-lg hover:shadow-red-900/20 relative">
                            <img src="${poster}" 
                                 class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                                 alt="${cleanTitle}"
                                 loading="lazy"
                                 onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'w-full h-full bg-[#0f172a] flex flex-col items-center justify-center p-4 text-center\'><svg class=\'w-12 h-12 text-gray-500 mb-2\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'1.5\' d=\'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z\' /></svg><span class=\'text-gray-300 font-bold text-sm leading-tight line-clamp-3\'>${cleanTitle}</span></div>';">
                            
                            <!-- Overlay -->
                            <div class="absolute inset-0 bg-gradient-to-t from-black via-black/40 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex flex-col justify-end p-4">
                                <h3 class="text-white text-sm font-medium truncate mt-2 group-hover:text-red-500 transition-colors drop-shadow-md">${cleanTitle}</h3>
                                <div class="flex items-center justify-between mt-2">
                                    ${ratingHtml}
                                    ${year ? `<span class="text-gray-400 text-xs font-bold">${year}</span>` : ''}
                                </div>
                            </div>
                        </div>
                    </a>
                    `;
                    });

                    grid.insertAdjacentHTML('beforeend', html);
                    index += batchSize;

                    if (index < series.length) {
                        requestAnimationFrame(renderBatch);
                    }
                }

                renderBatch();
            })
            .catch(err => {
                console.error(err);
                spinner.innerHTML = '<p class="text-red-500">Erreur de chargement. Veuillez rafraîchir.</p>';
            });
    });
</script>

<?php $content = ob_get_clean(); ?>

<?php require __DIR__ . '/layout.php'; ?>