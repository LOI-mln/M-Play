<?php
$title = "Films - M-Play";
$modePleinEcran = true; // Layout pleine page
ob_start();
?>

<div class="h-full flex flex-col">
    <!-- Header -->
    <div
        class="p-6 border-b border-gray-900 bg-black/50 backdrop-blur sticky top-0 z-30 flex flex-col md:flex-row justify-between items-center gap-4 shrink-0">
        <h2 class="text-2xl font-bold text-white flex items-center gap-3">
            <a href="/" class="text-gray-500 hover:text-white transition">&larr;</a>
            <span class="text-red-600">FILMS</span> VOD
        </h2>

        <!-- Search Bar -->
        <form action="/movies" method="GET" class="relative">
            <input type="hidden" name="categorie" value="<?= htmlspecialchars($categorieActuelleId ?? 'all') ?>">
            <input type="text" name="q" value="<?= htmlspecialchars($searchQuery ?? '') ?>"
                placeholder="Rechercher un film..."
                class="bg-gray-900 text-sm text-gray-200 rounded-full px-4 py-2 pl-10 border border-gray-800 focus:border-red-600 focus:outline-none focus:ring-1 focus:ring-red-600 w-64 transition-all"
                autocomplete="off">
            <svg class="w-4 h-4 text-gray-500 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none"
                stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
        </form>
    </div>

    <!-- Layout Container -->
    <div class="flex flex-grow overflow-hidden">

        <!-- Sidebar Categories -->
        <aside class="w-64 bg-[#0a0a0a] border-r border-gray-900 overflow-y-auto hidden md:block shrink-0">
            <div class="p-4">
                <h3 class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-4 px-2">Genres</h3>
                <nav class="space-y-1">
                    <?php if (!empty($categories)): ?>
                        <?php foreach ($categories as $cat): ?>
                            <a href="/movies?categorie=<?= $cat['category_id'] ?>"
                                class="block px-3 py-2 rounded text-sm transition-colors <?= ($categorieActuelleId ?? 'all') == $cat['category_id'] ? 'bg-red-900/20 text-red-500 font-bold border-l-2 border-red-500' : 'text-gray-400 hover:bg-gray-900 hover:text-gray-200' ?>">
                                <?= htmlspecialchars($cat['category_name']) ?>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </nav>
            </div>
        </aside>

        <!-- Grid -->
        <div class="flex-grow overflow-y-auto p-6 bg-black">

            <!-- Loading Spinner -->
            <div id="loading-spinner" class="col-span-full flex flex-col items-center justify-center py-20">
                <div class="animate-spin rounded-full h-16 w-16 border-t-2 border-b-2 border-[#E50914]"></div>
                <p class="mt-4 text-gray-400 animate-pulse">Chargement des films...</p>
            </div>

            <!-- Movies Grid Container -->
            <div id="movies-grid"
                class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-6">
                <!-- Javascript will populate this -->
            </div>

        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const grid = document.getElementById('movies-grid');
        const spinner = document.getElementById('loading-spinner');

        // Construct AJAX URL based on current parameters
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.set('ajax', '1');
        const fetchUrl = window.location.pathname + '?' + urlParams.toString();

        fetch(fetchUrl)
            .then(response => response.json())
            .then(movies => {
                spinner.style.display = 'none';

                if (!movies || movies.length === 0) {
                    grid.innerHTML = '<div class="col-span-full text-center text-gray-500 py-10">Aucun film trouvé dans cette catégorie.</div>';
                    return;
                }

                // Progressive Rendering
                let index = 0;
                const batchSize = 50; // Render 50 items at a time

                function renderBatch() {
                    const batch = movies.slice(index, index + batchSize);
                    if (batch.length === 0) return;

                    let html = '';
                    batch.forEach(film => {
                        const title = film.display_name || film.name;
                        // Escape quotes for JS string safetiness
                        const cleanTitle = title.replace(/"/g, '&quot;');
                        const streamId = film.stream_id;
                        const poster = film.stream_icon;
                    const year = film.year || '';
                    const rating = film.rating ? parseFloat(film.rating) : 0;
                    const ratingHtml = rating > 0 ? `<div class="flex items-center gap-1 text-yellow-500 text-xs font-bold"><span>★</span> ${rating}</div>` : '';

                    html += `
                    <a href="/movies/details?id=${streamId}" class="group relative cursor-pointer block">
                        <div class="aspect-[2/3] rounded-lg overflow-hidden bg-[#111] border border-gray-800 hover:border-red-600 transition-all shadow-lg hover:shadow-red-900/20 relative">
                            <img src="${poster}" 
                                 class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                                 alt="${cleanTitle}"
                                 loading="lazy"
                                 onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\\'w-full h-full bg-[#0f172a] flex flex-col items-center justify-center p-4 text-center\\'><svg class=\\'w-12 h-12 text-gray-500 mb-2\\' fill=\\'none\\' stroke=\\'currentColor\\' viewBox=\\'0 0 24 24\\'><path stroke-linecap=\\'round\\' stroke-linejoin=\\'round\\' stroke-width=\\'1.5\\' d=\\'M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z\\' /></svg><span class=\\'text-gray-300 font-bold text-sm leading-tight line-clamp-3\\'>${cleanTitle}</span></div>';">
                            
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

                    if (index < movies.length) {
                        requestAnimationFrame(renderBatch); // Continue rendering next frame
                    }
                }

                renderBatch(); // Start rendering
            })
            .catch(err => {
                console.error(err);
                spinner.innerHTML = '<p class="text-red-500">Erreur de chargement. Veuillez rafraîchir.</p>';
            });
    });
</script>

<?php $content = ob_get_clean(); ?>

<?php require __DIR__ . '/layout.php'; ?>