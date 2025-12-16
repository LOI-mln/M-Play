<?php
$title = htmlspecialchars($seriesInfo['name']) . " - M-Play";
$modePleinEcran = true;
ob_start();
?>

<!-- Container principal avec scroll -->
<div class="h-full overflow-y-auto bg-black">

    <!-- Hero Header (Backdrop) -->
    <div class="relative w-full h-[50vh] md:h-[60vh]">
        <!-- Backdrop Image -->
        <?php $backdrop = !empty($seriesInfo['backdrop_path']) && count($seriesInfo['backdrop_path']) > 0 ? $seriesInfo['backdrop_path'][0] : ($seriesInfo['cover'] ?? ''); ?>
        <?php if ($backdrop): ?>
            <div class="absolute inset-0 bg-cover bg-center"
                style="background-image: url('<?= htmlspecialchars($backdrop) ?>');"></div>
        <?php endif; ?>

        <!-- Gradient Overlay -->
        <div class="absolute inset-0 bg-gradient-to-t from-black via-black/60 to-transparent"></div>
        <div class="absolute inset-0 bg-gradient-to-r from-black via-black/40 to-transparent"></div>

        <!-- Back Button -->
        <a href="/series"
            class="absolute top-6 left-6 z-20 text-white/80 hover:text-white flex items-center gap-2 font-bold uppercase tracking-widest transition">
            <span>&larr;</span> Retour
        </a>

        <!-- Content -->
        <div class="absolute bottom-0 left-0 w-full p-8 md:p-12 flex flex-col md:flex-row gap-8 items-end">
            <!-- Poster -->
            <img src="<?= htmlspecialchars($seriesInfo['cover']) ?>"
                class="w-32 md:w-48 rounded-lg shadow-2xl border border-gray-800 hidden md:block" alt="Poster">

            <div class="flex-grow max-w-4xl text-white">
                <h1 class="text-4xl md:text-6xl font-black mb-4 leading-tight">
                    <?= htmlspecialchars($seriesInfo['name']) ?>
                </h1>

                <div class="flex flex-wrap gap-4 text-sm font-bold text-gray-300 uppercase tracking-wider mb-6">
                    <?php if (isset($seriesInfo['rating_5based'])): ?>
                        <span class="text-yellow-500 flex items-center gap-1">★
                            <?= round($seriesInfo['rating_5based'], 1) ?></span>
                    <?php endif; ?>
                    <?php if (isset($seriesInfo['releaseDate'])): ?>
                        <span><?= $seriesInfo['releaseDate'] ?></span>
                    <?php endif; ?>
                    <?php if (isset($seriesInfo['genre'])): ?>
                        <span class="text-red-500"><?= htmlspecialchars($seriesInfo['genre']) ?></span>
                    <?php endif; ?>
                </div>

                <p class="text-gray-300 leading-relaxed text-sm md:text-base line-clamp-3 md:line-clamp-none max-w-2xl">
                    <?= htmlspecialchars($seriesInfo['plot'] ?? 'Aucune description disponible.') ?>
                </p>

                <?php if (!empty($seriesInfo['cast'])): ?>
                    <p class="mt-4 text-xs text-gray-500">
                        <strong class="text-gray-400">Casting:</strong> <?= htmlspecialchars($seriesInfo['cast']) ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Seasons & Episodes -->
    <div class="p-8 md:p-12 max-w-7xl mx-auto" x-data="{ currentSeason: '<?= array_key_first($episodes) ?>' }">

        <!-- Season Selector Header -->
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-white flex items-center gap-3">
                <span class="text-red-600">ÉPISODES</span>
            </h2>

            <!-- Dropdown -->
            <div class="relative">
                <select
                    onchange="document.querySelectorAll('.season-container').forEach(el => el.classList.add('hidden')); document.getElementById('saison-' + this.value).classList.remove('hidden');"
                    class="bg-[#111] text-white border border-gray-800 rounded px-5 py-3 font-bold uppercase tracking-wider focus:outline-none focus:border-red-600 appearance-none pr-12 cursor-pointer min-w-[180px]">
                    <?php foreach ($episodes as $saisonNum => $episodesSaison): ?>
                        <option value="<?= $saisonNum ?>">Saison <?= $saisonNum ?></option>
                    <?php endforeach; ?>
                </select>
                <!-- Custom Arrow -->
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-white">
                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                        <path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="flex flex-col gap-8">

            <?php
            $first = true;
            foreach ($episodes as $saisonNum => $episodesSaison):
                ?>
                <!-- Conteneur Saison (ID unique pour le switch JS) -->
                <div id="saison-<?= $saisonNum ?>"
                    class="season-container bg-[#111] rounded-xl border border-gray-900 overflow-hidden <?= $first ? '' : 'hidden' ?>">

                    <!-- Liste Épisodes -->
                    <div>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 p-4">
                            <?php foreach ($episodesSaison as $ep): ?>
                                <a href="/series/watch?id=<?= $ep['id'] ?>&ext=<?= $ep['container_extension'] ?>"
                                    class="flex items-center gap-4 p-4 rounded-lg hover:bg-white/5 transition group border border-transparent hover:border-gray-800">

                                    <!-- Numéro Épisode -->
                                    <div
                                        class="w-10 h-10 flex items-center justify-center bg-gray-900 text-gray-500 rounded-full shrink-0 font-mono font-bold group-hover:bg-red-900 group-hover:text-red-100 transition">
                                        <?= $ep['episode_num'] ?>
                                    </div>

                                    <div class="flex flex-col justify-center min-w-0 flex-grow">
                                        <h4
                                            class="text-white font-bold text-sm truncate pr-2 group-hover:text-red-500 transition">
                                            <?= htmlspecialchars($ep['title']) ?>
                                        </h4>
                                        <div class="flex items-center gap-2 mt-1">
                                            <?php if (isset($ep['info']['rating']) && $ep['info']['rating'] > 0): ?>
                                                <span class="text-yellow-500 text-xs">★
                                                    <?= round($ep['info']['rating'], 1) ?></span>
                                            <?php endif; ?>
                                            <span
                                                class="text-gray-500 text-xs"><?= isset($ep['info']['duration']) ? $ep['info']['duration'] : '' ?></span>
                                        </div>
                                    </div>

                                    <!-- Petite flèche de lecture -->
                                    <span
                                        class="text-gray-600 group-hover:text-red-500 opacity-0 group-hover:opacity-100 transition">▶</span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php
                $first = false;
            endforeach;
            ?>

        </div>
    </div>
</div>

<?php $content = ob_get_clean(); ?>

<?php require __DIR__ . '/layout.php'; ?>