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
        <?php
        $backdrop = '';
        if (!empty($seriesInfo['backdrop_path'])) {
            if (is_array($seriesInfo['backdrop_path']) && count($seriesInfo['backdrop_path']) > 0) {
                $backdrop = $seriesInfo['backdrop_path'][0];
            } elseif (is_string($seriesInfo['backdrop_path'])) {
                $backdrop = $seriesInfo['backdrop_path'];
            }
        }
        if (empty($backdrop)) {
            $backdrop = $seriesInfo['cover'] ?? '';
        }
        ?>

        <?php if ($backdrop): ?>
            <div class="absolute inset-0 bg-cover bg-center transition-opacity duration-1000"
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
            <?php
            $poster = $seriesInfo['cover'] ?? '';
            if (!empty($poster)):
                ?>
                <img src="<?= htmlspecialchars($poster) ?>"
                    class="w-32 md:w-48 rounded-lg shadow-2xl border border-gray-800 hidden md:block shadow-black/50"
                    alt="Poster"
                    onerror="this.onerror=null; this.outerHTML='<div class=\'w-32 md:w-48 aspect-[2/3] rounded-lg shadow-2xl border border-gray-800 hidden md:flex items-center justify-center bg-[#1a1a1a] text-gray-600\'><svg class=\'w-12 h-12\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z\'></path></svg></div>';">
            <?php endif; ?>

            <div class="flex-grow max-w-4xl text-white">
                <!-- Title -->
                <h1 class="text-4xl md:text-6xl font-black mb-4 leading-tight drop-shadow-lg">
                    <?= htmlspecialchars($seriesInfo['name']) ?>
                </h1>

                <!-- Metadata & Language Selector Row -->
                <div class="flex flex-wrap items-center gap-6 mb-6">
                    <!-- Standard Metadata -->
                    <div
                        class="flex flex-wrap gap-4 text-sm font-bold text-gray-300 uppercase tracking-wider items-center">
                        <?php if (isset($seriesInfo['rating_5based']) && $seriesInfo['rating_5based'] > 0): ?>
                            <span class="text-yellow-500 flex items-center gap-1 bg-white/10 px-2 py-1 rounded">★
                                <?= round($seriesInfo['rating_5based'], 1) ?></span>
                        <?php endif; ?>

                        <?php if (isset($seriesInfo['releaseDate']) || isset($seriesInfo['year'])): ?>
                            <span><?= substr($seriesInfo['releaseDate'] ?? $seriesInfo['year'], 0, 4) ?></span>
                        <?php endif; ?>

                        <?php if (isset($seriesInfo['genre'])): ?>
                            <span class="text-red-500 line-clamp-1"><?= htmlspecialchars($seriesInfo['genre']) ?></span>
                        <?php endif; ?>
                    </div>


                    <!-- Language Indicator / Selector -->
                    <?php if (isset($availableVersions) && !empty($availableVersions)): ?>
                        <span class="w-1 h-1 bg-gray-600 rounded-full"></span>

                        <?php
                        // Determine current language tag
                        $currentName = $seriesInfo['name'];
                        $currentTag = 'Anglais'; // Default per user request
                        if (preg_match('/\b(FR|VF|VFF|TRUEFRENCH)\b/i', $currentName))
                            $currentTag = 'FRançais (VF)';
                        elseif (preg_match('/\b(VOSTFR|VOST)\b/i', $currentName))
                            $currentTag = 'VOSTFR';
                        elseif (preg_match('/\b(EN|ENGLISH|VO)\b/i', $currentName))
                            $currentTag = 'English (VO)';
                        elseif (preg_match('/\b(MULTI)\b/i', $currentName))
                            $currentTag = 'Multi-Langues';
                        ?>

                        <?php if (count($availableVersions) > 1): ?>
                            <div class="relative group z-50">
                                <button
                                    class="flex items-center gap-2 bg-white/10 hover:bg-white/20 px-3 py-1 rounded text-white text-xs font-bold transition border border-white/10 hover:border-red-500/50">
                                    <span class="text-red-500"><?= $currentTag ?></span>
                                    <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>

                                <div class="absolute top-full left-0 pt-2 w-48 hidden group-hover:block">
                                    <div class="bg-[#1a1a1a] border border-gray-800 rounded-lg shadow-xl overflow-hidden p-1">
                                        <?php foreach ($availableVersions as $ver): ?>
                                            <?php
                                            $vName = $ver['name'];
                                            $vTag = 'Autre';
                                            if (preg_match('/\b(FR|VF|VFF|TRUEFRENCH)\b/i', $vName))
                                                $vTag = 'FRançais (VF)';
                                            elseif (preg_match('/\b(VOSTFR|VOST)\b/i', $vName))
                                                $vTag = 'VOSTFR';
                                            elseif (preg_match('/\b(EN|ENGLISH|VO)\b/i', $vName))
                                                $vTag = 'English (VO)';
                                            elseif (preg_match('/\b(MULTI)\b/i', $vName))
                                                $vTag = 'Multi-Langues';

                                            $isCurrent = $ver['series_id'] == $seriesId;
                                            ?>
                                            <a href="/series/details?id=<?= $ver['series_id'] ?>"
                                                class="block px-3 py-2 text-xs rounded hover:bg-gray-800 transition flex items-center justify-between <?= $isCurrent ? 'text-red-500 font-bold bg-white/5' : 'text-gray-300' ?>">
                                                <span><?= $vTag ?></span>
                                                <?php if ($isCurrent): ?><span class="text-red-500">✓</span><?php endif; ?>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- Static Badge -->
                            <div class="px-3 py-1 rounded bg-white/5 border border-white/10 text-xs font-bold text-gray-300">
                                <?= $currentTag ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                </div>

                <!-- Description -->
                <p
                    class="text-gray-300 leading-relaxed text-sm md:text-base line-clamp-3 md:line-clamp-none max-w-2xl drop-shadow-md">
                    <?= htmlspecialchars($seriesInfo['plot'] ?? 'Aucune description disponible.') ?>
                </p>

                <!-- Cast Inline -->
                <?php if (!empty($seriesInfo['cast'])): ?>
                    <p class="mt-4 text-xs text-gray-500 line-clamp-1 max-w-2xl">
                        <strong class="text-gray-400">Casting:</strong> <?= htmlspecialchars($seriesInfo['cast']) ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>


    <!-- Seasons & Episodes (Added Below Header) -->
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
                                <a href="/series/watch?id=<?= $ep['id'] ?>&ext=<?= $ep['container_extension'] ?>&s=<?= $saisonNum ?>&e=<?= $ep['episode_num'] ?>&name=<?= urlencode($seriesInfo['name']) ?>&cover=<?= urlencode($seriesInfo['cover']) ?>&series_id=<?= $seriesId ?>&duration=<?= urlencode($ep['info']['duration'] ?? '') ?>"
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

    <!-- Details Section (Left Aligned - from Movies) -->
    <div class="w-full px-8 py-12 md:px-12 bg-black/50">
        <div class="max-w-7xl">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-12 text-gray-400">

                <?php if (!empty($seriesInfo['director'])): ?>
                    <div>
                        <h3
                            class="text-white font-bold text-sm mb-3 uppercase tracking-widest border-l-4 border-[#E50914] pl-3">
                            Directeur</h3>
                        <p class="text-base text-white/90"><?= htmlspecialchars($seriesInfo['director']) ?></p>
                    </div>
                <?php endif; ?>

                <?php if (!empty($seriesInfo['genre'])): ?>
                    <div>
                        <h3
                            class="text-white font-bold text-sm mb-3 uppercase tracking-widest border-l-4 border-[#E50914] pl-3">
                            Genres</h3>
                        <div class="flex flex-wrap gap-2">
                            <?php
                            $genres = explode(',', $seriesInfo['genre']);
                            if (count($genres) <= 1 && strpos($seriesInfo['genre'], '/') !== false) {
                                $genres = explode('/', $seriesInfo['genre']);
                            }
                            foreach ($genres as $genre): ?>
                                <span
                                    class="border border-white/20 px-3 py-1 rounded text-sm hover:bg-white/10 transition cursor-default text-white/80">
                                    <?= trim(htmlspecialchars($genre)) ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($seriesInfo['cast'])): ?>
                    <div class="md:col-span-2 lg:col-span-3">
                        <h3
                            class="text-white font-bold text-sm mb-4 uppercase tracking-widest border-l-4 border-[#E50914] pl-3">
                            Distribution</h3>
                        <div class="flex flex-wrap gap-x-8 gap-y-4">
                            <?php
                            $casts = explode(',', $seriesInfo['cast']);
                            foreach ($casts as $cast):
                                if (empty(trim($cast)))
                                    continue;
                                ?>
                                <div class="flex items-center gap-3 group min-w-[200px]">
                                    <div class="w-1.5 h-1.5 rounded-full bg-[#E50914] group-hover:scale-150 transition"></div>
                                    <span
                                        class="text-white/80 text-sm group-hover:text-white transition"><?= trim(htmlspecialchars($cast)) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<?php $content = ob_get_clean(); ?>

<?php require __DIR__ . '/layout.php'; ?>