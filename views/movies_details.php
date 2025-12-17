<?php
$title = htmlspecialchars($movieInfo['name']) . " - M-Play";
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
        if (!empty($movieInfo['backdrop_path'])) {
            if (is_array($movieInfo['backdrop_path']) && count($movieInfo['backdrop_path']) > 0) {
                $backdrop = $movieInfo['backdrop_path'][0];
            } elseif (is_string($movieInfo['backdrop_path'])) {
                $backdrop = $movieInfo['backdrop_path'];
            }
        }
        if (empty($backdrop)) {
            $backdrop = $movieInfo['stream_icon'] ?? '';
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
        <a href="/movies"
            class="absolute top-6 left-6 z-20 text-white/80 hover:text-white flex items-center gap-2 font-bold uppercase tracking-widest transition">
            <span>&larr;</span> Retour
        </a>

        <!-- Content -->
        <div class="absolute bottom-0 left-0 w-full p-8 md:p-12 flex flex-col md:flex-row gap-8 items-end">
            <!-- Poster (Identical to Series) -->
            <?php
            $poster = $movieInfo['stream_icon'] ?? $movieInfo['movie_image'] ?? '';
            if (!empty($poster)):
                ?>
                <img src="<?= htmlspecialchars($poster) ?>"
                    class="w-32 md:w-48 rounded-lg shadow-2xl border border-gray-800 hidden md:block shadow-black/50"
                    alt="Poster">
            <?php endif; ?>

            <div class="flex-grow max-w-4xl text-white">
                <!-- Title (Normalized) -->
                <h1 class="text-4xl md:text-6xl font-black mb-4 leading-tight drop-shadow-lg">
                    <?= htmlspecialchars($cleanTitle ?? $movieInfo['name']) ?>
                </h1>

                <!-- Metadata & Language Selector Row -->
                <div class="flex flex-wrap items-center gap-6 mb-6">
                    <!-- Standard Metadata -->
                    <div
                        class="flex flex-wrap gap-4 text-sm font-bold text-gray-300 uppercase tracking-wider items-center">
                        <?php if (isset($movieInfo['rating']) && $movieInfo['rating'] > 0): ?>
                            <span class="text-yellow-500 flex items-center gap-1">★
                                <?= round($movieInfo['rating'], 1) ?></span>
                        <?php endif; ?>

                        <?php if (isset($movieInfo['releasedate'])): ?>
                            <span><?= substr($movieInfo['releasedate'], 0, 4) ?></span>
                        <?php elseif (isset($movieInfo['added'])): ?>
                            <span><?= date('Y', $movieInfo['added']) ?></span>
                        <?php endif; ?>

                        <?php if (isset($movieInfo['duration'])): ?>
                            <span><?= $movieInfo['duration'] ?></span>
                        <?php endif; ?>

                        <?php if (isset($movieInfo['genre'])): ?>
                            <span class="text-red-500 line-clamp-1"><?= htmlspecialchars($movieInfo['genre']) ?></span>
                        <?php endif; ?>
                    </div>


                    <!-- Language Indicator / Selector -->
                    <?php if (isset($availableVersions) && !empty($availableVersions)): ?>
                        <span class="w-1 h-1 bg-gray-600 rounded-full"></span>

                        <?php
                        // Determine current language tag
                        $currentName = $movieInfo['name'];
                        // USER REQUEST: Default to "Anglais" instead of "Inconnue"
                        $currentTag = 'Anglais';
                        if (preg_match('/\b(FR|VF|VFF|TRUEFRENCH)\b/i', $currentName))
                            $currentTag = 'VF';
                        elseif (preg_match('/\b(VOSTFR|VOST)\b/i', $currentName))
                            $currentTag = 'VOSTFR';
                        elseif (preg_match('/\b(EN|ENGLISH|VO)\b/i', $currentName))
                            $currentTag = 'VO';
                        elseif (preg_match('/\b(MULTI)\b/i', $currentName))
                            $currentTag = 'MULTI';
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

                                            $isCurrent = $ver['stream_id'] == $movieInfo['stream_id'];
                                            ?>
                                            <a href="/movies/details?id=<?= $ver['stream_id'] ?>"
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

                <!-- Description (Identical to Series: text-gray-300 text-sm md:text-base max-w-2xl) -->
                <p
                    class="text-gray-300 leading-relaxed text-sm md:text-base line-clamp-3 md:line-clamp-none max-w-2xl drop-shadow-md">
                    <?= htmlspecialchars($movieInfo['plot'] ?? 'Aucune description disponible.') ?>
                </p>

                <!-- Cast Inline (Like Series) -->
                <?php if (!empty($movieInfo['cast'])): ?>
                    <p class="mt-4 text-xs text-gray-500 line-clamp-1 max-w-2xl">
                        <strong class="text-gray-400">Casting:</strong> <?= htmlspecialchars($movieInfo['cast']) ?>
                    </p>
                <?php endif; ?>
            </div>

            <!-- ACTIONS (Play Button on the Right) -->
            <div class="flex-shrink-0 mb-4 md:mb-0 md:self-center ml-auto">
                <a href="/movies/watch?id=<?= $movieInfo['stream_id'] ?>&ext=<?= $movieInfo['container_extension'] ?>"
                    class="group relative block">
                    <div
                        class="w-20 h-20 md:w-24 md:h-24 bg-[#E50914] hover:bg-red-700 rounded-full flex items-center justify-center shadow-[0_0_40px_rgba(229,9,20,0.5)] transition-all duration-300 hover:scale-110 md:group-hover:scale-110 z-10 relative">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                            class="w-10 h-10 md:w-12 md:h-12 text-white ml-1">
                            <path fill-rule="evenodd"
                                d="M4.5 5.653c0-1.426 1.529-2.33 2.779-1.643l11.54 6.348c1.295.712 1.295 2.573 0 3.285L7.28 19.991c-1.25.687-2.779-.217-2.779-1.643V5.653z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <!-- Pulse effect -->
                    <div class="absolute inset-0 bg-[#E50914] rounded-full animate-ping opacity-20"></div>
                </a>
            </div>
        </div>
    </div>

    <!-- Details Section (Left Aligned) -->
    <div class="w-full px-8 py-12 md:px-12 bg-black/50">
        <div class="max-w-7xl">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-12 text-gray-400">

                <?php if (!empty($movieInfo['director'])): ?>
                    <div>
                        <h3
                            class="text-white font-bold text-sm mb-3 uppercase tracking-widest border-l-4 border-[#E50914] pl-3">
                            Directeur</h3>
                        <p class="text-base text-white/90"><?= htmlspecialchars($movieInfo['director']) ?></p>
                    </div>
                <?php endif; ?>

                <?php if (!empty($movieInfo['genre'])): ?>
                    <div>
                        <h3
                            class="text-white font-bold text-sm mb-3 uppercase tracking-widest border-l-4 border-[#E50914] pl-3">
                            Genres</h3>
                        <div class="flex flex-wrap gap-2">
                            <?php
                            $genres = explode(',', $movieInfo['genre']);
                            if (count($genres) <= 1 && strpos($movieInfo['genre'], '/') !== false) {
                                $genres = explode('/', $movieInfo['genre']);
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

                <?php if (!empty($movieInfo['cast'])): ?>
                    <div class="md:col-span-2 lg:col-span-3">
                        <h3
                            class="text-white font-bold text-sm mb-4 uppercase tracking-widest border-l-4 border-[#E50914] pl-3">
                            Distribution</h3>
                        <div class="flex flex-wrap gap-x-8 gap-y-4">
                            <?php
                            $casts = explode(',', $movieInfo['cast']);
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