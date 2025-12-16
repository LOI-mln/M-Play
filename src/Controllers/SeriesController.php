<?php

namespace App\Controllers;

class SeriesController
{
    private $api;

    public function __construct()
    {
        // On réutilise la même logique d'auth que les autres contrôleurs
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

    public function index()
    {
        // 1. Récupérer les catégories de séries
        $categories = $this->api->get('player_api.php', [
            'action' => 'get_series_categories'
        ]);

        if (!$categories) {
            $categories = [];
        }

        // 2. Gestion du filtrage par catégorie (LOGIQUE STRICTE FR)
        $fr_cats = [];

        foreach ($categories as $cat) {
            $nom = strtoupper($cat['category_name']);

            // Exclusion explicite (AFRICAN, AFRICA, AF)
            if (preg_match('/(AFRICAN|AFRICA|AF\s|AF-)/', $nom)) {
                continue;
            }

            // Check French (FR, FRANCE, VF, VFF)
            if (preg_match('/(FR|FRANCE|VF|VFF)/', $nom)) {
                // Clean Name
                $cleanedName = preg_replace('/^(FR\s*[-|]?\s*|FRANCE\s*[-|]?\s*|VF\s*[-|]?\s*)|(\s*[-|]?\s*\bVOD\b\s*[-|]?\s*)/i', '', $cat['category_name']);
                $cat['category_name'] = trim($cleanedName, " -|");
                $fr_cats[] = $cat;
            }
        }

        // Tri alphabétique
        usort($fr_cats, function ($a, $b) {
            return strcmp($a['category_name'], $b['category_name']);
        });

        // On ne garde QUE les FR
        $categories = $fr_cats;

        // Extraction des IDs valides pour le filtrage "TOUT" (qui ne sera que du TOUT FR)
        $validCategoryIds = array_column($categories, 'category_id');

        // Ajout de la catégorie "TOUT"
        array_unshift($categories, [
            'category_id' => 'all',
            'category_name' => 'TOUT (FR)'
        ]);

        // Sélection par défaut
        $categorieActuelleId = $_GET['categorie'] ?? 'all';

        // 3. Récupérer les séries
        $series = [];
        $params = ['action' => 'get_series'];

        if ($categorieActuelleId !== 'all') {
            $params['category_id'] = $categorieActuelleId;
            $allSeries = $this->api->get('player_api.php', $params);
            $series = $allSeries ?: [];
        } else {
            // Si 'all', on doit charger TOUTES les séries de TOUTES les cats FR
            // Attention : charger get_series sans category_id retourne TOUT le serveur.
            // On doit filtrer post-requête ou charger catégorie par catégorie.
            // Vu le volume potentiel, on va faire le filtre post-requête sur le gros appel 'get_series'
            // SI et SEULEMENT SI l'API supporte le dump global. Sinon on prend la 1ère cat par défaut pour pas crash.

            // Stratégie "Optimisée" : 
            // - Si Recherche : On charge tout et on filtre.
            // - Si Pas Recherche : On charge juste la 1ère catégorie FR pour l'affichage "Default".

            if (!empty($_GET['q'])) {
                $allSeries = $this->api->get('player_api.php', ['action' => 'get_series']);
                if ($allSeries) {
                    $validIdsMap = array_flip($validCategoryIds);
                    $series = array_filter($allSeries, function ($s) use ($validIdsMap) {
                        return isset($validIdsMap[$s['category_id']]);
                    });
                }
            } else {
                // Pour éviter d'attendre 10s le chargement de "TOUTES LES SERIES DU MONDE",
                // En mode "TOUT", on charge en fait la première catégorie de la liste.
                // OU, si on veut vraiment tout, on charge tout.
                // On va charger la première pour l'instant pour la perf, comme pour les Films.
                if (isset($categories[1])) {
                    $firstId = $categories[1]['category_id'];
                    $series = $this->api->get('player_api.php', ['action' => 'get_series', 'category_id' => $firstId]) ?: [];
                }
            }
        }

        // 4. Recherche locale
        $searchQuery = $_GET['q'] ?? '';

        if (!empty($searchQuery)) {
            $filteredSeries = [];
            foreach ($series as $s) {
                if (stripos($s['name'], $searchQuery) !== false) {
                    $filteredSeries[] = $s;
                }
            }
            $series = $filteredSeries;
        }

        // Pagination ? (Pour l'instant on affiche tout, le scroll infini ou pagination JS serait mieux pour 10k items)

        // Vue
        require __DIR__ . '/../../views/series.php';
    }

    public function details()
    {
        $seriesId = $_GET['id'] ?? null;
        if (!$seriesId) {
            header('Location: /series');
            exit;
        }

        // 1. Récupérer les infos de la série (Info + Saisons + Épisodes)
        // action=get_series_info&series_id=X
        $info = $this->api->get('player_api.php', [
            'action' => 'get_series_info',
            'series_id' => $seriesId
        ]);

        if (!$info || empty($info['episodes'])) {
            die("Série introuvable ou vide.");
        }

        $seriesInfo = $info['info'];
        $episodes = $info['episodes']; // Groupés par saison "1" => [...], "2" => [...]

        require __DIR__ . '/../../views/series_details.php';
    }

    public function watch()
    {
        $streamId = $_GET['id'] ?? null;
        $extension = $_GET['ext'] ?? 'mp4';

        if (!$streamId) {
            die("Épisode introuvable.");
        }

        // Récupérer les infos de l'épisode pour le titre (facultatif mais mieux)
        // Note: L'API series_info donne tout, c'est lourd juste pour un titre.
        // On peut passer le titre en GET ou faire sans.
        // On va faire simple : lecture directe.

        // Construction des URLs (similaire à MoviesController)
        $hote = $_SESSION['host'];
        $username = $_SESSION['auth_creds']['username'];
        $password = $_SESSION['auth_creds']['password'];

        // URL Directe (MKV/MP4)
        $streamUrlDirect = "$hote/series/$username/$password/$streamId.$extension";

        // URL HLS (On suppose que le serveur supporte le HLS pour les séries aussi, souvent via /series/...)
        // En général Xtream Codes gère le HLS pour les vod/series de la même façon.
        // Parfois c'est /series/$u/$p/$id.m3u8 ou juste /movie/...
        // TEST: Pour les séries, souvent l'URL de base est /series/ mais le stream réel est traité comme une VOD.
        // On tente le format standard HLS VOD.
        $streamUrlHls = "$hote/series/$username/$password/$streamId.m3u8";

        // URL Transcodage (via notre Proxy)
        // On encode l'URL source directe en base64 pour la passer au proxy
        $sourceUrlEncoded = urlencode(base64_encode($streamUrlDirect));
        $streamUrlTranscode = "/stream/transcode?url=$sourceUrlEncoded";

        require __DIR__ . '/../../views/watch_series.php';
    }
}
