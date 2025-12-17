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

        <!-- Droite: Menu Global (Géré par layout.php) -->
        <div class="flex items-center gap-6">
            <!-- La place est laissée libre pour le menu absolute du layout -->
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