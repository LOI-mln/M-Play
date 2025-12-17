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

                    <!-- Language Selector (Integrated) -->
                    <?php if (isset($availableVersions) && count($availableVersions) > 1): ?>
                        <div class="relative group ml-auto md:ml-0">
                            <button
                                class="flex items-center gap-2 bg-white/10 hover:bg-white/20 px-3 py-1.5 rounded text-sm font-medium transition text-gray-200">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                    stroke="currentColor" class="size-4">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418" />
                                </svg>
                                <span>Langues</span>
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                    stroke="currentColor" class="size-3 text-gray-400">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                </svg>
                            </button>
                            <!-- Dropdown -->
                            <div class="absolute left-0 top-full pt-2 w-64 hidden group-hover:block z-50">
                                <div class="bg-[#1a1a1a] rounded-lg shadow-xl border border-white/10 overflow-hidden">
                                    <div class="p-1 max-h-64 overflow-y-auto custom-scrollbar">
                                        <div
                                            class="px-3 py-2 text-xs font-bold text-gray-500 uppercase tracking-widest border-b border-white/5">
                                            Choisir une version</div>
                                        <?php foreach ($availableVersions as $ver): ?>
                                            <a href="/movies/details?id=<?= $ver['stream_id'] ?>"
                                                class="flex items-center px-3 py-2 text-sm hover:bg-white/10 transition gap-2 <?= ($ver['stream_id'] == $movieInfo['stream_id']) ? 'text-[#E50914] font-bold bg-[#E50914]/10' : 'text-gray-300' ?>">
                                                <?php if ($ver['stream_id'] == $movieInfo['stream_id']): ?>
                                                    <div class="w-1.5 h-1.5 rounded-full bg-[#E50914]"></div>
                                                <?php else: ?>
                                                    <div class="w-1.5 h-1.5 rounded-full bg-transparent"></div>
                                                <?php endif; ?>
                                                <?= htmlspecialchars($ver['name']) ?>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
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