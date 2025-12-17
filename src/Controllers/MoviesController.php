<?php
namespace App\Controllers;

class MoviesController
{
    private $api;

    public function __construct()
    {
        // Augmentation de la mémoire pour charger les gros catalogues VOD
        ini_set('memory_limit', '1024M');

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit;
        }

        // On instancie l'API avec les crédentiels en session
        $this->api = new \App\Services\XtreamClient(
            $_SESSION['host'],
            $_SESSION['auth_creds']['username'],
            $_SESSION['auth_creds']['password']
        );
    }

    private function normalizeName($name)
    {
        // Supprime les préfixes communs comme "FR -", "EN -", "VOSTFR -", etc.
        // Regex explication:
        // ^(...) : Au début
        // \s* : Espaces optionnels
        // (FR|EN|...) : Liste des tags
        // \s*[-|:]?\s* : Séparateur optionnel (- ou | ou :) et espaces
        $pattern = '/^(\s*(FR|FRANCE|EN|ENGLISH|US|UK|VOSTFR|MULTI|TRUEFRENCH|VFF|VFQ|VFI)\s*[-|:]?\s*)+/i';
        $cleaned = preg_replace($pattern, '', $name);
        return trim($cleaned);
    }

    public function index()
    {
        // 1. Récupération des catégories VOD
        $allCategories = $this->api->get('player_api.php', [
            'action' => 'get_vod_categories'
        ]);

        if (!$allCategories) {
            $allCategories = [];
        }

        // 2. Filtrage et Tri (FR en premier, puis EN)
        $fr_cats = [];
        $en_cats = [];

        foreach ($allCategories as $cat) {
            $nom = strtoupper($cat['category_name']);

            // Exclusion explicite (AFRICAN)
            if (strpos($nom, 'AFRICAN') !== false) {
                continue;
            }

            // Check French
            if (preg_match('/(FR|FRANCE)/', $nom)) {
                // Clean Name (FR, FRANCE, VOD)
                $cleanedName = preg_replace('/^(FR\s*[-|]?\s*|FRANCE\s*[-|]?\s*)|(\s*[-|]?\s*\bVOD\b\s*[-|]?\s*)/i', '', $cat['category_name']);
                $cat['category_name'] = trim($cleanedName, " -|");
                $fr_cats[] = $cat;
            }
            // Check English (UK, US, EN)
            elseif (preg_match('/(UK|US|EN\s|ENGLISH)/', $nom)) {
                // Clean Name
                $cleanedName = preg_replace('/^(UK\s*[-|]?\s*|US\s*[-|]?\s*|EN\s*[-|]?\s*|ENGLISH\s*[-|]?\s*)|(\s*[-|]?\s*\bVOD\b\s*[-|]?\s*)/i', '', $cat['category_name']);
                $cat['category_name'] = trim($cleanedName, " -|");
                $en_cats[] = $cat;
            }
        }

        // Tri alphabétique interne pour chaque groupe
        $sortFunc = function ($a, $b) {
            return strcmp($a['category_name'], $b['category_name']);
        };

        usort($fr_cats, $sortFunc);
        usort($en_cats, $sortFunc);

        // Fusion : FR d'abord, ensuite EN
        $categories = array_merge($fr_cats, $en_cats);

        // Extraction des IDs valides pour le filtrage global
        $validCategoryIds = array_column($categories, 'category_id');

        // Ajout de la catégorie "TOUT" au début
        array_unshift($categories, [
            'category_id' => 'all',
            'category_name' => 'TOUT'
        ]);


        // 2. Gestion de la catégorie sélectionnée
        $idCategorieSelectionnee = $_GET['categorie'] ?? 'all';

        $rawFilms = [];
        $isGlobalSearch = !empty($_GET['q']);

        // 3. Récupération des films
        if ($idCategorieSelectionnee === 'all') {
            if ($isGlobalSearch) {
                $allFilms = $this->api->get('player_api.php', [
                    'action' => 'get_vod_streams'
                ]);
                if (!$allFilms)
                    $allFilms = [];

                $validIdsMap = array_flip($validCategoryIds);
                $rawFilms = array_filter($allFilms, function ($film) use ($validIdsMap) {
                    return isset($validIdsMap[$film['category_id']]);
                });
            } else {
                if (isset($categories[1])) {
                    $firstCatId = $categories[1]['category_id'];
                    $rawFilms = $this->api->get('player_api.php', [
                        'action' => 'get_vod_streams',
                        'category_id' => $firstCatId
                    ]);
                    if (!$rawFilms)
                        $rawFilms = [];
                }
            }
        } elseif ($idCategorieSelectionnee) {
            $rawFilms = $this->api->get('player_api.php', [
                'action' => 'get_vod_streams',
                'category_id' => $idCategorieSelectionnee
            ]);
            if (!$rawFilms)
                $rawFilms = [];
        }

        // Recherche locale
        if ($isGlobalSearch) {
            $search = mb_strtolower($_GET['q']);
            $rawFilms = array_filter($rawFilms, function ($film) use ($search) {
                return strpos(mb_strtolower($film['name']), $search) !== false;
            });
        }

        // 4. GROUPING LOGIC (New)
        // On groupe les films par nom normalisé.
        // On garde uniquement la version "preferée" pour l'affichage (Card), 
        // mais on ne stocke pas les variantes ici car l'index n'en a pas besoin.

        $uniqueFilms = [];
        $seen = [];

        foreach ($rawFilms as $film) {
            $normName = $this->normalizeName($film['name']);
            // Clé unique basée sur le nom normalisé
            $key = mb_strtolower($normName);

            if (!isset($seen[$key])) {
                // C'est le premier qu'on voit (donc notre "représentant" pour l'instant)
                // Note: Idéalement on préférerait la version FR si on a le choix, 
                // mais le tri naturel de l'API met souvent FR avant ou après selon le nom.
                // Pour l'instant on prend le premier arrivé.

                // On injecte le nom normalisé pour l'affichage propre
                $film['display_name'] = $normName;
                $uniqueFilms[] = $film;
                $seen[$key] = true;
            }
        }

        $films = $uniqueFilms;

        // On passe les variables à la vue
        $categorieActuelleId = $idCategorieSelectionnee;
        $searchQuery = $_GET['q'] ?? '';

        require __DIR__ . '/../../views/movies.php';
    }

    public function details()
    {
        $movieId = $_GET['id'] ?? null;
        if (!$movieId) {
            header('Location: /movies');
            exit;
        }

        // Récupérer les infos du film
        $info = $this->api->get('player_api.php', [
            'action' => 'get_vod_info',
            'vod_id' => $movieId
        ]);

        if (!$info || empty($info['movie_data'])) {
            die("Film introuvable.");
        }

        $movieInfo = array_merge($info['info'], $info['movie_data']);

        // --- LOGIQUE MULTI-LANGUE ---
        // 1. Normaliser le nom du film actuel
        $currentName = $movieInfo['name'];
        $cleanTitle = $this->normalizeName($currentName); // Pour l'affichage

        // 2. Chercher les variantes (autres langues)
        // OPTIMISATION : Utilisation d'un cache de session pour éviter de recharger l'API à chaque fois
        // On stocke la map "Nom Normalisé" -> "Liste de streams"

        $variants = [];

        // On vérifie si on a déjà un cache frais (moins de 5 min ?)
        // Pour faire simple : on cache tant que la session est là. 
        if (!isset($_SESSION['movies_map_cache'])) {
            // C'est lourd : on le fait une fois
            $allStreams = $this->api->get('player_api.php', [
                'action' => 'get_vod_streams'
            ]);

            $map = [];
            if ($allStreams) {
                foreach ($allStreams as $stream) {
                    $nName = $this->normalizeName($stream['name']);
                    $key = mb_strtolower($nName);
                    if (!isset($map[$key])) {
                        $map[$key] = [];
                    }
                    $map[$key][] = [
                        'stream_id' => $stream['stream_id'],
                        'name' => $stream['name'],
                        'container_extension' => $stream['container_extension']
                    ];
                }
            }
            $_SESSION['movies_map_cache'] = $map;
        }

        // Récupération depuis le cache
        $searchKey = mb_strtolower($cleanTitle);
        if (isset($_SESSION['movies_map_cache'][$searchKey])) {
            $variants = $_SESSION['movies_map_cache'][$searchKey];
        }

        // Fallback: Si rien trouvé ou cache vide (bizarre), on se met soi-même
        if (empty($variants)) {
            $variants[] = [
                'stream_id' => $movieInfo['stream_id'],
                'name' => $movieInfo['name'],
                'container_extension' => $movieInfo['container_extension']
            ];
        }

        // On passe les variantes à la vue
        $availableVersions = $variants;

        require __DIR__ . '/../../views/movies_details.php';
    }

    public function watch()
    {
        $streamId = $_GET['id'] ?? null;
        $extension = $_GET['ext'] ?? 'mp4';

        if (empty($extension)) {
            $extension = 'mp4';
        }

        if (!$streamId) {
            header('Location: /movies');
            exit;
        }

        $hote = $_SESSION['host'];
        $username = $_SESSION['auth_creds']['username'];
        $password = $_SESSION['auth_creds']['password'];

        // Stratégie "Hybrid Fallback" :
        // 1. On tente HLS (.m3u8) pour le son/buffer optimal.
        // 2. Si ça fail (404/NotSupported), le JS basculera sur le Direct (.ext).

        $streamUrlHls = "$hote/movie/$username/$password/$streamId.m3u8";
        $streamUrlDirect = "$hote/movie/$username/$password/$streamId.$extension";

        // Récupération de la durée via l'API (pour la barre de progression transcodée)
        // L'API movies list ne donne pas la durée précise, on fait un get_vod_info rapide.
        $info = $this->api->get('player_api.php', [
            'action' => 'get_vod_info',
            'vod_id' => $streamId
        ]);

        $duration = $info['info']['duration'] ?? ''; // Format attendu: "01:55:20" ou "115"

        // Transcodage (Fix Audio) par notre proxy local
        // On encode l'URL source Direct pour la passer au proxy
        // IMPORTANT: urlencode après base64_encode pour éviter que les '+' deviennent des espaces
        $sourceUrl = $streamUrlDirect;
        $streamUrlTranscode = "/stream/transcode?url=" . urlencode(base64_encode($sourceUrl));

        require __DIR__ . '/../../views/watch_vod.php';
    }
}
