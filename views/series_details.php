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
        <?php
        $backLink = '/series';
        $backLabel = 'Retour';
        if (isset($_GET['from'])) {
            if ($_GET['from'] === 'home') {
                $backLink = '/';
                $backLabel = 'Accueil';
            } elseif ($_GET['from'] === 'search') {
                $q = urlencode($_GET['q'] ?? '');
                $backLink = "/search?q=$q";
                $backLabel = 'Recherche';
            }
        }
        ?>
        <a href="<?= $backLink ?>"
            class="absolute top-6 left-6 z-20 text-white/80 hover:text-white flex items-center gap-2 font-bold uppercase tracking-widest transition">
            <span>&larr;</span> <?= $backLabel ?>
        </a>

        <!-- Content -->
        <div class="absolute bottom-0 left-0 w-full p-8 md:p-12 flex flex-col md:flex-row gap-8 items-end">
            <!-- Poster -->
            <img src="<?= htmlspecialchars($seriesInfo['cover']) ?>"
                class="w-32 md:w-48 rounded-lg shadow-2xl border border-gray-800 hidden md:block" alt="Poster"
                onerror="this.onerror=null; this.outerHTML='<div class=\'w-32 md:w-48 aspect-[2/3] rounded-lg shadow-2xl border border-gray-800 hidden md:flex items-center justify-center bg-[#1a1a1a] text-gray-600\'><svg class=\'w-12 h-12\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z\'></path></svg></div>';">

            <div class="flex-grow max-w-4xl text-white">
                <h1 class="text-4xl md:text-6xl font-black mb-4 leading-tight">
                    <?= htmlspecialchars($seriesInfo['name']) ?>
                </h1>

                <div
                    class="flex flex-wrap items-center gap-4 text-sm font-bold text-gray-300 uppercase tracking-wider mb-6">
                    <?php if (isset($seriesInfo['rating_5based'])): ?>
                        <span class="text-yellow-500 flex items-center gap-1 bg-white/10 px-2 py-1 rounded">★
                            <?= round($seriesInfo['rating_5based'], 1) ?></span>
                    <?php endif; ?>
                    <div class="flex flex-wrap items-center gap-4 text-sm text-gray-400 mb-6">
                        <?php if (!empty($seriesInfo['year']) || !empty($seriesInfo['releaseDate'])): ?>
                            <span><?= substr($seriesInfo['releaseDate'] ?? $seriesInfo['year'], 0, 4) ?></span>
                            <span class="w-1 h-1 bg-gray-600 rounded-full"></span>
                        <?php endif; ?>
                        <?php if (!empty($seriesInfo['genre'])): ?>
                            <span><?= htmlspecialchars($seriesInfo['genre']) ?></span>
                        <?php endif; ?>

                        <!-- Language Selector -->
                        <?php if (count($availableVersions) > 1): ?>
                            <span class="w-1 h-1 bg-gray-600 rounded-full"></span>
                            <div class="relative group z-50">
                                <button
                                    class="flex items-center gap-2 bg-white/10 hover:bg-white/20 px-3 py-1 rounded text-white text-xs font-bold transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" />
                                    </svg>
                                    <span>LANGUE</span>
                                    <svg class="w-3 h-3 text-gray-400 -mr-1" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>

                                <!-- Dropdown -->
                                <div class="absolute left-0 pt-2 w-48 hidden group-hover:block">
                                    <div class="bg-[#1a1a1a] border border-gray-800 rounded-lg shadow-xl overflow-hidden">
                                        <?php foreach ($availableVersions as $ver): ?>
                                            <?php
                                            // Extract Tag (FR, EN, etc.)
                                            $name = $ver['name'];
                                            // USER REQUEST: Default to Anglais
                                            $tag = 'Anglais';
                                            if (preg_match('/\b(FR|VF|VFF|TRUEFRENCH)\b/i', $name))
                                                $tag = 'FRançais (VF)';
                                            elseif (preg_match('/\b(VOSTFR|VOST)\b/i', $name))
                                                $tag = 'VOSTFR';
                                            elseif (preg_match('/\b(EN|ENGLISH|VO)\b/i', $name))
                                                $tag = 'English (VO)';
                                            elseif (preg_match('/\b(MULTI)\b/i', $name))
                                                $tag = 'Multi-Langues';

                                            $isCurrent = $ver['series_id'] == $seriesId;
                                            ?>
                                            <a href="/series/details?id=<?= $ver['series_id'] ?>"
                                                class="block px-4 py-2 text-xs hover:bg-gray-800 transition flex items-center justify-between <?= $isCurrent ? 'text-red-500 font-bold bg-white/5' : 'text-gray-300' ?>">
                                                <span><?= $tag ?></span>
                                                <?php if ($isCurrent): ?><span class="text-red-500">✓</span><?php endif; ?>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
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
                                <a href="/series/watch?id=<?= $ep['id'] ?>&ext=<?= $ep['container_extension'] ?>&duration=<?= urlencode($ep['info']['duration'] ?? '') ?>"
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