<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="view-transition" content="same-origin" /> <!-- Native Page Transitions -->
    <title>M-Play</title>
    <!-- Google Fonts: JetBrains Mono (Nerd Font style) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700;800&display=swap" rel="stylesheet">
    <!-- TailwindCSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            red: '#E50914',
                            black: '#141414',
                            dark: '#0f0f0f'
                        }
                    }
                }
            }
        }
    </script>
    <style>
        /* Custom Scrollbar for WebKit */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #0f0f0f;
        }

        ::-webkit-scrollbar-thumb {
            background: #333;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        /* View Transitions Customization (Optional) */
        ::view-transition-old(root),
        ::view-transition-new(root) {
            animation-duration: 0.3s;
        }
    </style>
    <!-- Vue.js 3 -->
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
</head>

<body class="h-screen flex flex-col font-sans bg-black text-gray-200 selection:bg-red-600 selection:text-white">


    <?php
    $classesMain = isset($modePleinEcran) && $modePleinEcran ? 'w-full h-full p-0 flex-grow bg-black' : 'flex-grow container mx-auto p-4';
    ?>

    <main id="app" class="<?= $classesMain ?>">
        <?= $content ?>
    </main>

    <?php if (!isset($modePleinEcran) || !$modePleinEcran): ?>
        <footer class="bg-black p-6 text-center text-gray-600 text-xs border-t border-gray-900">
            &copy; <?= date('Y') ?> IPTV PLAYER &bull; <span class="text-red-900">POWERED BY PHP</span>
        </footer>
    <?php endif; ?>

    <?= $scripts ?? '' ?>
</body>

</html>