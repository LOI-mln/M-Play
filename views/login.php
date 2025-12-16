<?php ob_start(); ?>

<div class="flex items-center justify-center min-h-full h-full">
    <div class="bg-black p-10 rounded-xl shadow-2xl border border-red-900/50 w-full max-w-md relative overflow-hidden">
        <!-- Decoration lines -->
        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-transparent via-red-600 to-transparent">
        </div>
        <div
            class="absolute bottom-0 right-0 w-full h-1 bg-gradient-to-r from-transparent via-red-600 to-transparent transform rotate-180">
        </div>

        <h2 class="text-3xl font-black text-center text-red-600 mb-8 uppercase tracking-widest">Connexion</h2>

        <?php if (isset($erreur)): ?>
            <div class="bg-red-900/20 border border-red-800 text-red-400 px-4 py-3 rounded mb-6 text-sm text-center">
                <?= htmlspecialchars($erreur) ?>
            </div>
        <?php endif; ?>

        <form action="/auth/verify" method="POST" class="space-y-6">
            <div>
                <label for="host" class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">URL du
                    Serveur</label>
                <input type="url" name="host" id="host" placeholder="http://exemple.com:8080" required
                    class="w-full bg-[#0f0f0f] border border-gray-800 text-white px-4 py-3 rounded focus:outline-none focus:border-red-600 focus:ring-1 focus:ring-red-600 transition-colors placeholder-gray-700">
            </div>

            <div>
                <label for="username"
                    class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Utilisateur</label>
                <input type="text" name="username" id="username" placeholder="Votre identifiant" required
                    class="w-full bg-[#0f0f0f] border border-gray-800 text-white px-4 py-3 rounded focus:outline-none focus:border-red-600 focus:ring-1 focus:ring-red-600 transition-colors placeholder-gray-700">
            </div>

            <div>
                <label for="password" class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Mot de
                    passe</label>
                <input type="password" name="password" id="password" placeholder="••••••••" required
                    class="w-full bg-[#0f0f0f] border border-gray-800 text-white px-4 py-3 rounded focus:outline-none focus:border-red-600 focus:ring-1 focus:ring-red-600 transition-colors placeholder-gray-700">
            </div>

            <button type="submit"
                class="w-full bg-red-700 hover:bg-red-600 text-white font-bold py-4 rounded shadow-[0_0_15px_rgba(220,38,38,0.2)] hover:shadow-[0_0_25px_rgba(220,38,38,0.4)] transition-all duration-300 uppercase tracking-widest">
                Se connecter
            </button>
        </form>
    </div>
</div>

<?php $content = ob_get_clean(); ?>

<?php require __DIR__ . '/layout.php'; ?>