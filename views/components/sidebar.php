<aside
    class="w-20 lg:w-64 bg-[#0a0a0a]/90 backdrop-blur-xl border-r border-gray-800 flex flex-col items-center lg:items-stretch py-8 z-50 shrink-0 transition-all duration-300">
    <!-- Logo -->
    <div class="mb-10 px-4 flex justify-center lg:justify-start lg:px-8">
        <img src="/ressources/logo.png" alt="Logo"
            class="h-10 lg:h-12 object-contain drop-shadow-lg transition-transform hover:scale-105">
    </div>

    <!-- Navigation -->
    <nav class="flex-1 w-full space-y-2 px-2 lg:px-4">
        <?php
        $navItems = [
            ['url' => '/', 'icon' => 'home', 'label' => 'Accueil'],
            ['url' => '/live', 'icon' => 'live_tv', 'label' => 'Live TV'],
            ['url' => '/movies', 'icon' => 'movie', 'label' => 'Films'],
            ['url' => '/series', 'icon' => 'tv', 'label' => 'Séries'],
        ];

        $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        ?>

        <?php foreach ($navItems as $item): ?>
            <?php $isActive = $currentPath === $item['url']; ?>
            <a href="<?= $item['url'] ?>"
                class="group flex items-center gap-4 px-3 py-3 rounded-xl transition-all duration-300 
               <?= $isActive ? 'bg-gradient-to-r from-red-600/20 to-transparent text-white border-l-4 border-red-600' : 'text-gray-400 hover:text-white hover:bg-white/5' ?>">

                <!-- Icon -->
                <div class="relative">
                    <?php if ($item['icon'] === 'home'): ?>
                        <svg class="w-6 h-6 <?= $isActive ? 'text-red-500' : 'group-hover:text-red-500' ?> transition-colors"
                            fill="currentColor" viewBox="0 0 24 24">
                            <path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z" />
                        </svg>
                    <?php elseif ($item['icon'] === 'live_tv'): ?>
                        <svg class="w-6 h-6 <?= $isActive ? 'text-red-500' : 'group-hover:text-red-500' ?> transition-colors"
                            fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M21 6h-7.59l3.29-3.29L16 2l-4 4-4-4-.71.71L10.59 6H3a2 2 0 00-2 2v12c0 1.1.9 2 2 2h18c1.1 0 2-.9 2-2V8a2 2 0 00-2-2zm0 14H3V8h18v12zM9 10v8l7-4z" />
                        </svg>
                    <?php elseif ($item['icon'] === 'movie'): ?>
                        <svg class="w-6 h-6 <?= $isActive ? 'text-red-500' : 'group-hover:text-red-500' ?> transition-colors"
                            fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M18 4l2 4h-3l-2-4h-2l2 4h-3l-2-4H8l2 4H7L5 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V4h-4z" />
                        </svg>
                    <?php elseif ($item['icon'] === 'tv'): ?>
                        <svg class="w-6 h-6 <?= $isActive ? 'text-red-500' : 'group-hover:text-red-500' ?> transition-colors"
                            fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M21 3H3c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h5v2h8v-2h5c1.1 0 1.99-.9 1.99-2L23 5a2 2 0 00-2-2zm0 14H3V5h18v12z" />
                        </svg>
                    <?php endif; ?>

                    <?php if ($isActive): ?>
                        <div class="absolute inset-0 bg-red-600 blur-lg opacity-40"></div>
                    <?php endif; ?>
                </div>

                <!-- Label -->
                <span class="hidden lg:block font-medium tracking-wide text-sm <?= $isActive ? 'text-white' : '' ?>">
                    <?= $item['label'] ?>
                </span>
            </a>
        <?php endforeach; ?>
    </nav>

    <!-- Settings / Logout Bottom -->
    <div class="mt-auto px-2 lg:px-4 w-full space-y-2">
        <a href="/logout"
            class="flex items-center gap-4 px-3 py-3 rounded-xl text-gray-400 hover:text-red-500 hover:bg-red-500/10 transition-all duration-300 group">
            <svg class="w-6 h-6 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
            <span class="hidden lg:block font-medium text-sm">Déconnexion</span>
        </a>
    </div>
</aside>