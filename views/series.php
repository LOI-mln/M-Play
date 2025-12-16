<?php
$title = "Séries - M-Play";
$modePleinEcran = true; // Layout pleine page
ob_start();
?>

<div class="h-full flex flex-col">
    <!-- Header -->
    <div class="p-6 border-b border-gray-900 bg-black/50 backdrop-blur sticky top-0 z-30 flex flex-col md:flex-row justify-between items-center gap-4 shrink-0">
        <h2 class="text-2xl font-bold text-white flex items-center gap-3">
            <a href="/" class="text-gray-500 hover:text-white transition">&larr;</a>
            <span class="text-red-600">SÉRIES</span> TV
        </h2>

        <!-- Search Bar -->
        <form action="/series" method="GET" class="relative">
            <input type="hidden" name="categorie" value="<?= $categorieActuelleId ?>">
            <input type="text" 
                   name="q" 
                   value="<?= htmlspecialchars($searchQuery ?? '') ?>"
                   placeholder="Rechercher une série..." 
                   class="bg-gray-900 text-sm text-gray-200 rounded-full px-4 py-2 pl-10 border border-gray-800 focus:border-red-600 focus:outline-none focus:ring-1 focus:ring-red-600 w-64 transition-all"
                   autocomplete="off"
            >
            <svg class="w-4 h-4 text-gray-500 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
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
                        <a href="/series?categorie=<?= $cat['category_id'] ?>" 
                           class="block px-3 py-2 rounded text-sm transition-colors <?= $categorieActuelleId == $cat['category_id'] ? 'bg-red-900/20 text-red-500 font-bold border-l-2 border-red-500' : 'text-gray-400 hover:bg-gray-900 hover:text-gray-200' ?>">
                            <?= htmlspecialchars($cat['category_name']) ?>
                        </a>
                    <?php endforeach; ?>
                </nav>
            </div>
        </aside>

        <!-- Grid -->
        <div class="flex-grow overflow-y-auto p-6 bg-black">
            <?php if (empty($series)): ?>
                <div class="text-center text-gray-500 mt-20">
                    <p class="text-xl">Aucune série trouvée dans cette catégorie.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-6">
                    <?php foreach ($series as $s): ?>
                        <a href="/series/details?id=<?= $s['series_id'] ?>" class="group relative cursor-pointer block">
                            <!-- Poster Container (Ratio 2:3 standard affiche) -->
                            <div class="aspect-[2/3] rounded-lg overflow-hidden bg-[#111] border border-gray-800 hover:border-red-600 transition-all shadow-lg hover:shadow-red-900/20">
                                <?php if (!empty($s['cover'])): ?>
                                    <img src="<?= htmlspecialchars($s['cover']) ?>" 
                                         alt="<?= htmlspecialchars($s['name']) ?>"
                                         class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                                         loading="lazy"
                                         onerror="this.src='https://via.placeholder.com/300x450/111/666?text=No+Poster'">
                                <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center bg-gray-800 text-gray-600">
                                        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Overlay Hover -->
                                <div class="absolute inset-0 bg-gradient-to-t from-black via-black/40 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex flex-col justify-end p-4">
                                    <h3 class="text-white font-bold text-sm leading-tight line-clamp-2 drop-shadow-md">
                                        <?= htmlspecialchars($s['name']) ?>
                                    </h3>
                                    <?php if (isset($s['rating']) && $s['rating'] > 0): ?>
                                        <div class="flex items-center gap-1 mt-2 text-yellow-500 text-xs font-bold">
                                            <span>★</span> <?= $s['rating'] ?>
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
