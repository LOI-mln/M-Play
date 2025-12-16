<?php
namespace App\Controllers;

class MoviesController
{
    public function index()
    {
        // Augmentation de la mémoire pour charger les gros catalogues VOD
        ini_set('memory_limit', '1024M');

        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit;
        }

        $hote = $_SESSION['host'];
        $utilisateur = $_SESSION['auth_creds']['username'];
        $motDePasse = $_SESSION['auth_creds']['password'];

        // 1. Récupération des catégories VOD
        $urlCategories = "$hote/player_api.php?username=$utilisateur&password=$motDePasse&action=get_vod_categories";
        $donneesCategories = @file_get_contents($urlCategories);
        $allCategories = json_decode($donneesCategories, true) ?? [];

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

        $films = [];
        $isGlobalSearch = !empty($_GET['q']);

        // 3. Récupération des films
        // OPTIMISATION : Si on est sur "TOUT" ('all') ET qu'on ne cherche pas, on ne charge pas tout (trop lent).
        // On charge la première catégorie par défaut pour afficher du contenu.
        // Si on cherche, on charge TOUT pour chercher dedans.

        if ($idCategorieSelectionnee === 'all') {

            if ($isGlobalSearch) {
                // CAS 1 : Recherche Globale -> On doit tout charger (Lent mais nécessaire pour la search)
                $urlFilms = "$hote/player_api.php?username=$utilisateur&password=$motDePasse&action=get_vod_streams";
                $donneesFilms = @file_get_contents($urlFilms);
                $allFilms = json_decode($donneesFilms, true) ?? [];

                // Filtre catégories valides
                $validIdsMap = array_flip($validCategoryIds);
                $films = array_filter($allFilms, function ($film) use ($validIdsMap) {
                    return isset($validIdsMap[$film['category_id']]);
                });
            } else {
                // CAS 2 : "TOUT" sans recherche -> On charge juste la première catégorie pour aller vite
                // On prend la première catégorie réelle (pas 'all')
                // Note : $categories[0] est 'all'. Donc $categories[1] est la première vraie.
                if (isset($categories[1])) {
                    $firstCatId = $categories[1]['category_id'];
                    $urlFilms = "$hote/player_api.php?username=$utilisateur&password=$motDePasse&action=get_vod_streams&category_id=$firstCatId";
                    $donneesFilms = @file_get_contents($urlFilms);
                    $films = json_decode($donneesFilms, true) ?? [];
                }
            }

        } elseif ($idCategorieSelectionnee) {
            // CAS 3 : Catégorie spécifique -> Chargement normal
            $urlFilms = "$hote/player_api.php?username=$utilisateur&password=$motDePasse&action=get_vod_streams&category_id=$idCategorieSelectionnee";
            $donneesFilms = @file_get_contents($urlFilms);
            $films = json_decode($donneesFilms, true) ?? [];
        }

        // Recherche locale (s'applique au résultat récupéré)
        if ($isGlobalSearch) {
            $search = mb_strtolower($_GET['q']);
            $films = array_filter($films, function ($film) use ($search) {
                return strpos(mb_strtolower($film['name']), $search) !== false;
            });
        }

        // On passe les variables à la vue
        $categorieActuelleId = $idCategorieSelectionnee;
        $searchQuery = $_GET['q'] ?? '';

        require __DIR__ . '/../../views/movies.php';
    }

    public function watch()
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit;
        }

        $streamId = $_GET['id'] ?? null;
        $extension = $_GET['ext'] ?? '';

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

        $originalExt = $_GET['ext'] ?? 'mp4';
        if (empty($originalExt))
            $originalExt = 'mp4';

        $streamUrlHls = "$hote/movie/$username/$password/$streamId.m3u8";
        $streamUrlDirect = "$hote/movie/$username/$password/$streamId.$originalExt";

        // Récupération de la durée via l'API (pour la barre de progression transcodée)
        // L'API movies list ne donne pas la durée précise, on fait un get_vod_info rapide.
        $urlInfo = "$hote/player_api.php?username=$username&password=$password&action=get_vod_info&vod_id=$streamId";
        $infoData = @file_get_contents($urlInfo);
        $infoJson = json_decode($infoData, true);
        $duration = $infoJson['info']['duration'] ?? ''; // Format attendu: "01:55:20" ou "115"


        // Transcodage (Fix Audio) par notre proxy local
        // On encode l'URL source Direct pour la passer au proxy
        // IMPORTANT: urlencode après base64_encode pour éviter que les '+' deviennent des espaces
        $sourceUrl = $streamUrlDirect;
        $streamUrlTranscode = "/stream/transcode?url=" . urlencode(base64_encode($sourceUrl));

        require __DIR__ . '/../../views/watch_vod.php';
    }
}
