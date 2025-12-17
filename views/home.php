<?php
$modePleinEcran = true; // Plein écran pour l'accueil aussi
ob_start();
?>

<div class="flex flex-col h-full bg-black">
    <!-- Header Premium -->
    <div class="px-12 py-8 flex flex-col md:flex-row items-center justify-between gap-6 z-20 relative">
        <!-- Gauche: Logo + Nom -->
        <div class="flex items-center gap-1"> <!-- Gap réduit pour coller -->
            <img src="/ressources/logo.png" alt="M" class="h-16 md:h-20 object-contain drop-shadow-md">
        </div>

        <!-- Droite: Infos Playlist -->
        <div class="flex items-center gap-6 bg-white/5 backdrop-blur-md px-6 py-3 rounded-full border border-white/10">
            <!-- Nom du compte -->
            <div class="flex flex-col items-end">
                <span
                    class="text-gray-400 text-xs font-bold uppercase tracking-widest font-['JetBrains_Mono']">Compte</span>
                <span
                    class="text-white font-bold text-lg leading-none font-['JetBrains_Mono']"><?= htmlspecialchars($_SESSION['user']['username'] ?? 'Invité') ?></span>
            </div>

            <!-- Séparateur -->
            <div class="h-8 w-px bg-white/10"></div>

            <!-- Statut En Ligne -->
            <div class="flex items-center gap-2">
                <div class="relative flex h-3 w-3">
                    <span
                        class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                </div>
                <span class="text-green-500 font-bold text-xs uppercase tracking-widest font-['JetBrains_Mono']">En
                    ligne</span>
            </div>
        </div>
    </div>

    <!-- Main Selection Grid -->
    <div class="flex-grow p-8 grid grid-cols-1 md:grid-cols-3 gap-8 relative z-10 w-full max-w-7xl mx-auto">
        <!-- Background Glow Effect behind content -->
        <div
            class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full h-full bg-red-900/10 blur-[100px] -z-10 rounded-full pointer-events-none">
        </div>

        <!-- Live TV -->
        <a href="/live"
            class="group relative overflow-hidden rounded-2xl border border-gray-800 hover:border-red-600 transition-all duration-300 transform hover:scale-[1.02]">
            <!-- Gradient Overlay (z-10) -->
            <div
                class="absolute inset-0 bg-gradient-to-t from-black via-black/50 to-transparent z-10 pointer-events-none">
            </div>

            <!-- Items: Image (z-0) -->
            <img src="/ressources/live.png" alt="Background Live TV"
                class="absolute inset-0 w-full h-full object-cover transition-transform duration-700 group-hover:scale-110 opacity-60 group-hover:opacity-80">

            <div class="absolute bottom-0 left-0 p-8 z-20">
                <h3
                    class="text-4xl font-black text-white uppercase italic tracking-tighter mb-2 group-hover:text-red-600 transition-colors">
                    Live TV</h3>
                <p class="text-gray-300 text-sm">Chaînes en direct du monde entier</p>
            </div>
        </a>

        <!-- Movies -->
        <a href="/movies"
            class="group relative overflow-hidden rounded-2xl border border-gray-800 hover:border-red-600 transition-all duration-300 transform hover:scale-[1.02]">
            <div class="absolute inset-0 bg-gradient-to-t from-black via-black/50 to-transparent z-10"></div>
            <div
                class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1536440136628-849c177e76a1?q=80&w=800&auto=format&fit=crop')] bg-cover bg-center transition-transform duration-700 group-hover:scale-110 opacity-60 group-hover:opacity-80">
            </div>

            <div class="absolute bottom-0 left-0 p-8 z-20">
                <h3
                    class="text-4xl font-black text-white uppercase italic tracking-tighter mb-2 group-hover:text-red-600 transition-colors">
                    Films</h3>
                <p class="text-gray-300 text-sm">Les derniers blockbusters</p>
            </div>
        </a>

        <!-- Series -->
        <a href="/series"
            class="group relative overflow-hidden rounded-2xl border border-gray-800 hover:border-red-600 transition-all duration-300 transform hover:scale-[1.02]">
            <div class="absolute inset-0 bg-gradient-to-t from-black via-black/50 to-transparent z-10"></div>
            <div
                class="absolute inset-0 bg-[url('/ressources/series.png')] bg-cover bg-center transition-transform duration-700 group-hover:scale-110 opacity-60 group-hover:opacity-80">
            </div>

            <div class="absolute bottom-0 left-0 p-8 z-20">
                <h3
                    class="text-4xl font-black text-white uppercase italic tracking-tighter mb-2 group-hover:text-red-600 transition-colors">
                    Séries</h3>
                <p class="text-gray-300 text-sm">Vos émissions préférées</p>
            </div>
        </a>

    </div>
</div>

<?php $content = ob_get_clean(); ?>

<?php ob_start(); ?>
<script>
    const { createApp } = Vue

    createApp({
        data() {
            return {
                count: 0
            }
        }
    }).mount('#app')
</script>
<?php $scripts = ob_get_clean(); ?>

<?php require __DIR__ . '/layout.php'; ?>