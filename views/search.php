<?php
$title = 'Résultats de recherche - ' . htmlspecialchars($query);
$modePleinEcran = true; // Pour désactiver le wrapper par défaut du layout
ob_start();
?>

<div class="flex h-screen overflow-hidden bg-[#0b0b0b]">
    <!-- Sidebar -->
    <?php require __DIR__ . '/components/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col relative overflow-hidden">

        <?php
        // Force search placeholder context
        $searchPlaceholder = "Rechercher films, séries...";
        require __DIR__ . '/components/topbar.php';
        ?>

        <!-- Grid Content -->
        <div
            class="flex-1 overflow-y-auto overflow-x-hidden p-8 scrollbar-thin scrollbar-thumb-red-900 scrollbar-track-transparent">

            <div class="max-w-7xl mx-auto space-y-12">
                <!-- Header Résultats -->
                <div class="flex items-center justify-between pb-6 border-b border-white/10">
                    <div>
                        <h1 class="text-3xl font-bold text-white mb-2">Résultats pour "<span
                                class="text-red-500"><?= htmlspecialchars($query) ?></span>"</h1>
                        <p class="text-gray-400">
                            <?= (isset($results['movies']) ? count($results['movies']) : 0) + (isset($results['series']) ? count($results['series']) : 0) ?>
                            résultats trouvés
                        </p>
                    </div>
                </div>

                <?php if ((empty($results['movies']) && empty($results['series']))): ?>
                    <div class="text-center py-20">
                        <div class="text-6xl mb-6 opacity-30">🔍</div>
                        <h2 class="text-2xl text-white font-bold mb-2">Aucun résultat trouvé</h2>
                        <p class="text-gray-400">Essayez avec d'autres mots-clés ou vérifiez l'orthographe.</p>
                    </div>
                <?php endif; ?>

                <!-- Films -->
                <?php if (!empty($results['movies'])): ?>
                    <section>
                        <div class="flex items-center gap-3 mb-6">
                            <h2 class="text-2xl font-bold text-white">Films</h2>
                            <span class="px-2 py-0.5 rounded-full bg-red-600 text-white text-xs font-bold">
                                <?= count($results['movies']) ?>
                            </span>
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-6">
                            <?php foreach ($results['movies'] as $movie): ?>
                                <a href="/movies/details?id=<?= $movie['stream_id'] ?>&from=search&q=<?= urlencode($query) ?>"
                                    class="group relative block aspect-[2/3] bg-[#1a1a1a] rounded-xl overflow-hidden shadow-lg hover:shadow-red-900/40 hover:scale-105 transition-all duration-300 ring-1 ring-white/10 hover:ring-red-500">
                                    <?php if (!empty($movie['stream_icon'])): ?>
                                        <img src="<?= htmlspecialchars($movie['stream_icon']) ?>"
                                            alt="<?= htmlspecialchars($movie['display_name']) ?>"
                                            class="w-full h-full object-cover transition-transform duration-500 group-hover:opacity-80"
                                            loading="lazy"
                                            onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'w-full h-full bg-[#0f172a] flex flex-col items-center justify-center p-4 text-center\'><svg class=\'w-12 h-12 text-gray-500 mb-2\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'1.5\' d=\'M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z\' /></svg><span class=\'text-gray-300 font-bold text-sm leading-tight line-clamp-2\'><?= addslashes(htmlspecialchars($movie['display_name'])) ?></span></div>';">
                                    <?php else: ?>
                                        <div class="w-full h-full flex items-center justify-center bg-[#1a1a1a] text-gray-600">
                                            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z">
                                                </path>
                                            </svg>
                                        </div>
                                    <?php endif; ?>

                                    <div
                                        class="absolute inset-x-0 bottom-0 p-4 bg-gradient-to-t from-black/90 via-black/60 to-transparent">
                                        <h3 class="text-white font-bold text-sm truncate drop-shadow-md">
                                            <?= htmlspecialchars($movie['display_name']) ?>
                                        </h3>
                                        <?php if (isset($movie['rating']) && $movie['rating'] > 0): ?>
                                            <div class="flex items-center gap-1 text-yellow-500 text-xs font-bold mt-1">
                                                <span>★</span>
                                                <span><?= number_format($movie['rating'], 1) ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>

                <!-- Séries -->
                <?php if (!empty($results['series'])): ?>
                    <section>
                        <div class="flex items-center gap-3 mb-6">
                            <h2 class="text-2xl font-bold text-white">Séries</h2>
                            <span class="px-2 py-0.5 rounded-full bg-red-600 text-white text-xs font-bold">
                                <?= count($results['series']) ?>
                            </span>
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-6">
                            <?php foreach ($results['series'] as $serie): ?>
                                <a href="/series/details?id=<?= $serie['series_id'] ?>&from=search&q=<?= urlencode($query) ?>"
                                    class="group relative block aspect-[2/3] bg-[#1a1a1a] rounded-xl overflow-hidden shadow-lg hover:shadow-red-900/40 hover:scale-105 transition-all duration-300 ring-1 ring-white/10 hover:ring-red-500">
                                    <?php if (!empty($serie['cover'])): ?>
                                        <img src="<?= htmlspecialchars($serie['cover']) ?>"
                                            alt="<?= htmlspecialchars($serie['display_name']) ?>"
                                            class="w-full h-full object-cover transition-transform duration-500 group-hover:opacity-80"
                                            loading="lazy"
                                            onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'w-full h-full bg-[#0f172a] flex flex-col items-center justify-center p-4 text-center\'><svg class=\'w-12 h-12 text-gray-500 mb-2\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'1.5\' d=\'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z\' /></svg><span class=\'text-gray-300 font-bold text-sm leading-tight line-clamp-2\'><?= addslashes(htmlspecialchars($serie['display_name'])) ?></span></div>';">
                                    <?php else: ?>
                                        <div class="w-full h-full flex items-center justify-center bg-[#1a1a1a] text-gray-600">
                                            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                                                </path>
                                            </svg>
                                        </div>
                                    <?php endif; ?>

                                    <div
                                        class="absolute inset-x-0 bottom-0 p-4 bg-gradient-to-t from-black/90 via-black/60 to-transparent">
                                        <h3 class="text-white font-bold text-sm truncate drop-shadow-md">
                                            <?= htmlspecialchars($serie['display_name']) ?>
                                        </h3>
                                        <?php if (isset($serie['rating']) && $serie['rating'] > 0): ?>
                                            <div class="flex items-center gap-1 text-yellow-500 text-xs font-bold mt-1">
                                                <span>★</span>
                                                <span><?= number_format($serie['rating'], 1) ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/layout.php'; ?>