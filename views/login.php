<?php ob_start(); ?>

<div class="min-h-screen flex items-center justify-center bg-[#0f0f0f] relative overflow-hidden">

    <!-- Subtle Background Gradient -->
    <div
        class="absolute inset-0 z-0 bg-[radial-gradient(ellipse_at_center,_var(--tw-gradient-stops))] from-red-900/30 via-[#0a0a0a] to-black">
    </div>

    <!-- Login Container -->
    <div class="w-full max-w-4xl p-12 relative z-10">

        <!-- Header: Logo Left, Text Right -->
        <div class="flex justify-between items-end mb-24">
            <!-- Logo Left -->
            <img src="/ressources/logo.png" alt="Logo" class="h-24 object-contain">

            <!-- Text Right -->
            <div class="text-right">
                <h2 class="text-4xl font-bold text-white mb-2 font-['JetBrains_Mono'] tracking-tight uppercase">
                    Connexion</h2>
                <p class="text-gray-400 text-sm font-['JetBrains_Mono']">Renseignez vos identifiants.</p>
            </div>
        </div>

        <?php if (isset($erreur)): ?>
            <div
                class="bg-red-500/10 border-l-4 border-red-500 text-red-400 px-6 py-4 rounded-r-lg mb-8 text-sm backdrop-blur-sm">
                <?= htmlspecialchars($erreur) ?>
            </div>
        <?php endif; ?>

        <form action="/auth/verify" method="POST" class="space-y-6">

            <!-- Playlist Name (1st) -->
            <div class="group/input">
                <label for="playlist_name"
                    class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-2 ml-4 transition-colors group-focus-within/input:text-white">Nom
                    de la playlist (Optionnel)</label>
                <input type="text" name="playlist_name" id="playlist_name" placeholder="Ex: Salon, Chambre..."
                    class="w-full bg-white/5 border border-white/10 text-white px-6 py-4 rounded-full focus:outline-none focus:border-red-600 focus:bg-white/10 transition-all duration-300 placeholder-gray-700 font-['JetBrains_Mono'] text-sm">
            </div>

            <!-- Username (2nd) -->
            <div class="group/input">
                <label for="username"
                    class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-2 ml-4 transition-colors group-focus-within/input:text-white">Utilisateur</label>
                <input type="text" name="username" id="username" placeholder="Identifiant" required
                    class="w-full bg-white/5 border border-white/10 text-white px-6 py-4 rounded-full focus:outline-none focus:border-red-600 focus:bg-white/10 transition-all duration-300 placeholder-gray-700 font-['JetBrains_Mono'] text-sm">
            </div>

            <!-- Password (3rd) -->
            <div class="group/input">
                <label for="password"
                    class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-2 ml-4 transition-colors group-focus-within/input:text-white">Mot
                    de passe</label>
                <input type="password" name="password" id="password" placeholder="Mot de passe" required
                    class="w-full bg-white/5 border border-white/10 text-white px-6 py-4 rounded-full focus:outline-none focus:border-red-600 focus:bg-white/10 transition-all duration-300 placeholder-gray-700 font-['JetBrains_Mono'] text-sm">
            </div>

            <!-- URL (4th - Unified Style) -->
            <div class="group/input">
                <label for="host"
                    class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-2 ml-4 transition-colors group-focus-within/input:text-white">URL
                    du Serveur</label>
                <input type="url" name="host" id="host" placeholder="http://exemple.com:8080" required
                    class="w-full bg-white/5 border border-white/10 text-white px-6 py-4 rounded-full focus:outline-none focus:border-red-600 focus:bg-white/10 transition-all duration-300 placeholder-gray-700 font-['JetBrains_Mono'] text-sm">
            </div>

            <div class="pt-8 flex justify-between items-center">
                <button type="submit"
                    class="group flex items-center gap-4 text-white font-bold text-xl uppercase tracking-widest hover:text-red-600 transition-colors pl-2">
                    <span>Se connecter</span>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                        stroke="currentColor" class="w-6 h-6 transform group-hover:translate-x-2 transition-transform">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M17.25 8.25L21 12m0 0l-3.75 3.75M21 12H3" />
                    </svg>
                </button>

                <?php if (isset($_SESSION['user'])): ?>
                    <a href="/"
                        class="text-gray-500 hover:text-white text-xs font-bold uppercase tracking-widest transition-colors">
                        Retour
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<?php $content = ob_get_clean(); ?>

<?php require __DIR__ . '/layout.php'; ?>