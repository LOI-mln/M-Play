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
            <input type="hidden" name="categorie" value="<?= $categorieActuelleId ?>">
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
                    <?php foreach ($categories as $cat): ?>
                        <a href="/movies?categorie=<?= $cat['category_id'] ?>"
                            class="block px-3 py-2 rounded text-sm transition-colors <?= $categorieActuelleId == $cat['category_id'] ? 'bg-red-900/20 text-red-500 font-bold border-l-2 border-red-500' : 'text-gray-400 hover:bg-gray-900 hover:text-gray-200' ?>">
                            <?= htmlspecialchars($cat['category_name']) ?>
                        </a>
                    <?php endforeach; ?>
                </nav>
            </div>
        </aside>

        <!-- Grid -->
        <div class="flex-grow overflow-y-auto p-6 bg-black">
            <?php if (empty($films)): ?>
                <div class="text-center text-gray-500 mt-20">
                    <p class="text-xl">Aucun film trouvé dans cette catégorie.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-6">
                    <?php foreach ($films as $film): ?>
                        <a href="/movies/details?id=<?= $film['stream_id'] ?>" class="group relative cursor-pointer block">
                            <!-- Poster Container (Ratio 2:3 standard affiche) -->
                            <div
                                class="aspect-[2/3] rounded-lg overflow-hidden bg-[#111] border border-gray-800 hover:border-red-600 transition-all shadow-lg hover:shadow-red-900/20">
                                <?php if (!empty($film['stream_icon'])): ?>
                                    <div class="relative w-full h-full">
                                        <img src="<?= htmlspecialchars($film['stream_icon']) ?>"
                                            alt="<?= htmlspecialchars($film['name']) ?>"
                                            class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                                            loading="lazy"
                                            onerror="this.style.display='none'; this.nextElementSibling.classList.remove('hidden');">

                                        <!-- Fallback CSS Poster (Hidden by default, shown on error) -->
                                        <div
                                            class="hidden absolute inset-0 w-full h-full bg-[#0f172a] flex flex-col items-center justify-center p-4 text-center border border-gray-800">
                                            <svg class="w-12 h-12 text-gray-500 mb-2" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                    d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
                                            </svg>
                                            <span class="text-gray-300 font-bold text-sm leading-tight line-clamp-3">
                                                <?= htmlspecialchars($film['name']) ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <!-- No Image Defined -> Show CSS Poster directly -->
                                    <div
                                        class="w-full h-full bg-[#0f172a] flex flex-col items-center justify-center p-4 text-center border border-gray-800 transition-colors group-hover:bg-[#1e293b]">
                                        <svg class="w-12 h-12 text-gray-500 mb-2" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
                                        </svg>
                                        <span class="text-gray-300 font-bold text-sm leading-tight line-clamp-3">
                                            <?= htmlspecialchars($film['name']) ?>
                                        </span>
                                    </div>
                                <?php endif; ?>

                                <!-- Overlay Hover -->
                                <div
                                    class="absolute inset-0 bg-gradient-to-t from-black via-black/40 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex flex-col justify-end p-4">
                                    <h3
                                        class="text-white text-sm font-medium truncate mt-2 group-hover:text-red-500 transition-colors">
                                        <?= htmlspecialchars($film['display_name'] ?? $film['name']) ?>
                                    </h3>
                                    <?php if (isset($film['rating']) && $film['rating'] > 0): ?>
                                        <div class="flex items-center gap-1 mt-2 text-yellow-500 text-xs font-bold">
                                            <span>★</span> <?= $film['rating'] ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); ?>

<?php require __DIR__ . '/layout.php'; ?>