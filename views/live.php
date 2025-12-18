<?php
// On récupère les dernières sorties
$modePleinEcran = true; // Layout pleine page to use custom sidebar
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

            <!-- Categories Header -->
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-white flex items-center gap-3">
                    <span class="text-red-600">LIVE</span> TV
                    <span
                        class="text-xs font-normal text-gray-500 bg-gray-900 px-2 py-1 rounded ml-2"><?= count($flux) ?>
                        chaînes</span>
                </h2>
            </div>

            <!-- Categories Horizontal List -->
            <div class="mb-8">
                <div class="flex gap-3 overflow-x-auto pb-4 scrollbar-hide snap-x">
                    <a href="/live"
                        class="px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap transition-all snap-start <?= !isset($_GET['categorie']) ? 'bg-red-600 text-white shadow-lg shadow-red-900/40' : 'bg-[#1a1a1a] text-gray-400 hover:bg-[#252525] hover:text-white border border-white/5' ?>">
                        Tous
                    </a>
                    <?php foreach ($categories as $cat): ?>
                        <a href="/live?categorie=<?= $cat['category_id'] ?>"
                            class="px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap transition-all snap-start <?= (isset($_GET['categorie']) && $_GET['categorie'] == $cat['category_id']) ? 'bg-red-600 text-white shadow-lg shadow-red-900/40' : 'bg-[#1a1a1a] text-gray-400 hover:bg-[#252525] hover:text-white border border-white/5' ?>">
                            <?= htmlspecialchars($cat['category_name']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Grid -->
            <?php if (empty($flux)): ?>
                <div class="flex flex-col items-center justify-center py-20 text-gray-500">
                    <svg class="w-16 h-16 mb-4 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                    </svg>
                    <p class="text-lg font-medium">Aucune chaîne trouvée dans cette catégorie.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-5">
                    <?php foreach ($flux as $chaine): ?>
                        <?php
                        $icone = $chaine['stream_icon'] ?: 'https://via.placeholder.com/300x169/111/333?text=No+Logo';
                        ?>
                        <a href="/live/watch?id=<?= $chaine['stream_id'] ?>"
                            class="group relative bg-[#1a1a1a] rounded-xl overflow-hidden shadow-lg shadow-black/50 hover:shadow-red-900/20 hover:scale-105 transition-all duration-300 ring-1 ring-white/5 hover:ring-red-600/40">
                            <!-- Image aspect ratio 16:9 -->
                            <div class="aspect-video bg-black/50 relative p-4 flex items-center justify-center">
                                <img src="<?= htmlspecialchars($icone) ?>" loading="lazy"
                                    alt="<?= htmlspecialchars($chaine['name']) ?>"
                                    class="max-w-full max-h-full object-contain drop-shadow-md group-hover:drop-shadow-[0_0_15px_rgba(255,255,255,0.1)] transition-all">

                                <!-- Play Overlay -->
                                <div
                                    class="absolute inset-0 bg-black/40 backdrop-blur-[2px] flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                    <div
                                        class="bg-red-600 text-white rounded-full p-3 transform scale-90 group-hover:scale-110 transition-transform shadow-lg shadow-red-900/50">
                                        <svg class="w-6 h-6 ml-0.5" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M8 5v14l11-7z" />
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <div class="px-3 py-3 border-t border-white/5 bg-[#1f1f1f]">
                                <h3 class="text-xs font-bold text-gray-200 truncate group-hover:text-red-500 transition-colors"
                                    title="<?= htmlspecialchars($chaine['name']) ?>">
                                    <?= htmlspecialchars($chaine['name']) ?>
                                </h3>
                                <p class="text-[10px] text-gray-500 mt-0.5 truncate font-mono opacity-60">ID:
                                    <?= $chaine['stream_id'] ?>
                                </p>
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