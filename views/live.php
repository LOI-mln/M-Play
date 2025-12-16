<?php
$modePleinEcran = true; // Layout pleine page (pas de container centré, pas de footer)
ob_start();
?>

<div class="h-full flex flex-col">
    <!-- Header -->
    <div
        class="p-6 border-b border-gray-900 bg-black/50 backdrop-blur sticky top-0 z-30 flex flex-col md:flex-row justify-between items-center gap-4 shrink-0">
        <h2 class="text-2xl font-bold text-white flex items-center gap-3">
            <a href="/" class="text-gray-500 hover:text-white transition">&larr;</a>
            <span class="text-red-600">LIVE</span> TV
        </h2>

        <span class="text-xs text-gray-500 bg-gray-900 px-2 py-1 rounded whitespace-nowrap"><?= count($flux) ?>
            chaînes</span>
    </div>

    <!-- Layout Container -->
    <div class="flex flex-grow overflow-hidden">

        <!-- Sidebar Categories -->
        <aside class="w-64 bg-[#0a0a0a] border-r border-gray-900 overflow-y-auto hidden md:block shrink-0">
            <div class="p-4">
                <h3 class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-4 px-2">Catégories</h3>
                <nav class="space-y-1">
                    <?php foreach ($categories as $cat): ?>
                        <a href="/live?categorie=<?= $cat['category_id'] ?>"
                            class="block px-3 py-2 rounded text-sm transition-colors <?= $categorieActuelleId == $cat['category_id'] ? 'bg-red-900/20 text-red-500 font-bold border-l-2 border-red-500' : 'text-gray-400 hover:bg-gray-900 hover:text-gray-200' ?>">
                            <?= htmlspecialchars($cat['category_name']) ?>
                        </a>
                    <?php endforeach; ?>
                </nav>
            </div>
        </aside>

        <!-- Grid -->
        <div class="flex-grow overflow-y-auto p-6 bg-black">
            <?php if (empty($flux)): ?>
                <div class="text-center text-gray-500 mt-20">
                    <p class="text-xl">Aucune chaîne trouvée dans cette catégorie.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                    <?php foreach ($flux as $chaine): ?>
                        <?php
                        // $urlFluxDirect = $_SESSION['host'] . "/live/" . $_SESSION['auth_creds']['username'] . "/" . $_SESSION['auth_creds']['password'] . "/" . $chaine['stream_id'] . ".ts";
                        $icone = $chaine['stream_icon'] ?: 'https://via.placeholder.com/300x169/111/333?text=No+Logo';
                        ?>
                        <a href="/live/watch?id=<?= $chaine['stream_id'] ?>"
                            class="group relative bg-[#111] rounded-lg overflow-hidden border border-gray-800 hover:border-red-600 transition-all cursor-pointer hover:shadow-lg hover:shadow-red-900/20 block">
                            <!-- Image aspect ratio 16:9 -->
                            <div class="aspect-video bg-black relative">
                                <img src="<?= htmlspecialchars($icone) ?>" loading="lazy"
                                    alt="<?= htmlspecialchars($chaine['name']) ?>"
                                    class="w-full h-full object-contain group-hover:scale-105 transition-transform duration-300">
                                <!-- Play Overlay -->
                                <div
                                    class="absolute inset-0 bg-black/60 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                    <span class="bg-red-600 text-white rounded-full p-3">▶</span>
                                </div>
                            </div>

                            <div class="p-3">
                                <h3 class="text-sm font-semibold text-gray-200 truncate group-hover:text-red-500 transition-colors"
                                    title="<?= htmlspecialchars($chaine['name']) ?>">
                                    <?= htmlspecialchars($chaine['name']) ?>
                                </h3>
                                <p class="text-xs text-gray-500 mt-1 truncate">ID: <?= $chaine['stream_id'] ?></p>
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