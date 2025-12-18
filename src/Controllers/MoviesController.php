<?php
namespace App\Controllers;

use App\Services\XtreamClient;
use App\Services\FileCache;

class MoviesController
{
    private $api;
    private $cache;

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

        // on s'assure que les variables de session existent
        if (!isset($_SESSION['host']) || !isset($_SESSION['auth_creds'])) {
            // Session corrompue ou incomplète -> redirection
            session_destroy();
            header('Location: /login');
            exit;
        }

        $this->api = new XtreamClient(
            $_SESSION['host'],
            $_SESSION['auth_creds']['username'],
            $_SESSION['auth_creds']['password']
        );

        $this->cache = new FileCache();
    }

    private function normalizeName($name)
    {
        // 0. Nettoyage spécifique "SRS" demandés par l'utilisateur
        $name = str_ireplace('SRS', '', $name);

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
        // 1. Récupération des catégories VOD (Cached)
        $cacheKeyCats = 'vod_categories_v2';
        $allCategories = $this->cache->get($cacheKeyCats);

        if ($allCategories === null) {
            $allCategories = $this->api->get('player_api.php', [
                'action' => 'get_vod_categories'
            ]);
            // Cache pour 24h (86400s) car ça bouge peu
            if ($allCategories) {
                $this->cache->set($cacheKeyCats, $allCategories, 86400);
            }
        }

        if (!$allCategories) {
            $allCategories = [];
        }

        // --- GROUPEMENT DES CATEGORIES GLOBALES ---
        $groupedCategories = []; // Format: "Nom Normalisé" => [ 'ids' => [], 'name' => "Nom Normalisé" ]
        $validCategoryIds = []; // Pour filtrer les films globaux

        foreach ($allCategories as $cat) {
            $originalName = strtoupper($cat['category_name']); // Upper for checks

            // EXCLUSIONS STRICTES (Adulte, Pays Etrangers non demandés)
            // Pays exclus: Une longue liste pour nettoyer la vue
            $patternsExclusion = [
                'ARAB',
                'AFRIC',
                'INDIA',
                'LATINO',
                'ADULT',
                'PORN',
                'XXX',
                '18\+',
                'HENTAI',
                'ALBAN',
                'BULGAR',
                'ROMAN',
                'BALKAN',
                'EX-YU',
                'YUGO',
                'GREC',
                'GREEK',
                'GREECE',
                'SCANDI',
                'SWED',
                'NORW',
                'DANISH',
                'FINN',
                'DUTCH',
                'NETHERLAND',
                'BRAZIL',
                'PORTUGAL',
                'SPAIN',
                'ITALY',
                'GERMANY',
                'POLAND',
                'RUSSIA',
                'TURKEY',
                'UK ',
                'US ',
                'USA', // Avec espace pour éviter de tuer des mots
                'DE ',
                'IT ',
                'ES ',
                'PT ',
                'NL ',
                'TR ',
                'RU ',
                'PL ', // Codes pays avec espace
                '\[AF\]',
                '\[ALB?\]',
                '\[BG\]',
                '\[RO\]',
                '\[GR\]', // Codes spécifiques brackets
                'ASIAN',
                'CHINA',
                'KOREA',
                'JAPAN',
                'VIET',
                'THAI'
            ];

            $regexExclusion = '/(' . implode('|', $patternsExclusion) . ')/i';

            if (preg_match($regexExclusion, $cat['category_name']))
                continue;

            if (preg_match('/\p{Arabic}|\p{Han}|\p{Cyrillic}/u', $cat['category_name']))
                continue;

            // FILTRE "PAS DE SRS"
            if (trim($originalName) === 'SRS')
                continue;

            // FILTER: keep only relevant languages (FR / EN / US / UK / VOSTFR / MULTI ...)
            // et si on n'a pas filtré avant.
            // Note: On accepte un peu plus large pour les films car parfois "Thriller" n'a pas de tag FR
            // Mais si RegexExclusion n'a pas matché, c'est bon signe.

            // Check implicit foreign
            if (preg_match('/\b(ENGLISH|GERMAN|SPANISH|ITALIAN|PORTUGUESE|DUTCH|TURKISH|RUSSIAN|POLISH)\b/i', $cat['category_name']))
                continue;


            // Normalisation du nom (FR - Action -> Action)
            $name = $cat['category_name'];

            // Re-enabled per user request: Clean prefixes again including SRS
            $name = str_ireplace('SRS', '', $name);

            $name = preg_replace('/^[\[\(]?\b(FR|FRANCE|VF|VFF|VO|VOSTFR|MULTI|TRUEFRENCH|FRENCH|EN|ENGLISH|US|UK|NL|DE|IT|ES|PT)\b[\]\)]?\s*[-|:]?\s*/i', '', $name);
            $name = preg_replace('/\s*[-|:]?\s*[\[\(]?\b(FR|FRANCE|VF|VFF|VO|VOSTFR|MULTI|TRUEFRENCH|FRENCH|EN|ENGLISH|US|UK|NL|DE|IT|ES|PT)\b[\]\)]?$/i', '', $name);
            // 3. Remove "VOD"
            $name = preg_replace('/(\s*[-|]?\s*\bVOD\b\s*[-|]?\s*)/i', '', $name);

            $cleanedName = trim($name, " -|()");

            if (mb_strlen($cleanedName) < 2)
                continue;

            // 3. REGROUPEMENT (APPLE, CANAL, ETC.)
            $upperClean = mb_strtoupper($cleanedName);

            if (strpos($upperClean, 'APPLE') !== false) {
                $cleanedName = 'Apple TV+';
            } elseif (strpos($upperClean, 'CANAL') !== false) {
                $cleanedName = 'Canal+';
            } elseif (strpos($upperClean, 'NETFLIX') !== false) {
                $cleanedName = 'Netflix';
            } elseif (strpos($upperClean, 'DISNEY') !== false) {
                $cleanedName = 'Disney+';
            } elseif (strpos($upperClean, 'AMAZON') !== false || strpos($upperClean, 'PRIME VIDEO') !== false) {
                $cleanedName = 'Amazon Prime Video';
            } elseif (strpos($upperClean, 'PARAMOUNT') !== false) {
                $cleanedName = 'Paramount+';
            } elseif (strpos($upperClean, 'OOCS') !== false) { // OCS / OOCS spelling
                $cleanedName = 'OCS';
            }

            // Clé unique pour le regroupement
            $key = mb_strtolower($cleanedName);

            if (!isset($groupedCategories[$key])) {
                $groupedCategories[$key] = [
                    'name' => $cleanedName,
                    'ids' => []
                ];
            }
            $groupedCategories[$key]['ids'][] = $cat['category_id'];
            $validCategoryIds[$cat['category_id']] = true; // Map for fast lookup
        }

        // Construction de la liste finale pour la vue
        $finalCategories = [];
        foreach ($groupedCategories as $group) {
            $finalCategories[] = [
                'category_name' => $group['name'],
                'category_id' => implode(',', $group['ids']) // Multiple IDs
            ];
        }

        // Tri par IMPORTANCE (Comme pour les séries)
        $priorityOrder = [
            'Netflix',
            'Amazon Prime Video',
            'Disney+',
            'Canal+',
            'Apple TV+',
            'Paramount+',
            'OCS'
        ];

        usort($finalCategories, function ($a, $b) use ($priorityOrder) {
            $nameA = $a['category_name'];
            $nameB = $b['category_name'];

            $posA = array_search($nameA, $priorityOrder);
            $posB = array_search($nameB, $priorityOrder);

            // Si les deux sont dans la liste prioritaire
            if ($posA !== false && $posB !== false) {
                return $posA - $posB;
            }

            // Si A est prioritaire
            if ($posA !== false)
                return -1;
            // Si B est prioritaire
            if ($posB !== false)
                return 1;

            // Sinon alphabétique
            return strcmp($nameA, $nameB);
        });

        // Ajout de la catégorie "TOUT" (qui sera "Ajoutés Récemment")
        array_unshift($finalCategories, [
            'category_id' => 'all',
            'category_name' => 'Ajoutés Récemment'
        ]);

        $categories = $finalCategories;


        // 2. Gestion de la catégorie sélectionnée
        $idCategorieSelectionnee = $_GET['categorie'] ?? 'all';

        $rawFilms = [];
        $isGlobalSearch = !empty($_GET['q']);

        // 3. Récupération des films
        if ($idCategorieSelectionnee === 'all') {
            // CAS SPECIALE "TOUT" = "Ajoutés Récemment" (Global)

            // Try Cache First
            $cacheKeyAll = 'all_vod_streams_v2';
            $allFilms = $this->cache->get($cacheKeyAll);

            if ($allFilms === null) {
                // Not in cache, fetch from API (Heavy operation)
                $allFilms = $this->api->get('player_api.php', [
                    'action' => 'get_vod_streams'
                ]);

                if (!$allFilms)
                    $allFilms = [];

                // Cache for 1 hour
                $this->cache->set($cacheKeyAll, $allFilms, 3600);
            }

            // DEBUG: Check structure for TMDB/IMDB (Saved to project root)
            if (!empty($allFilms)) {
                $debugFile = __DIR__ . '/../../debug_movie_data.txt';
                file_put_contents($debugFile, print_r($allFilms[0], true));
            }

            // FILTRE: On ne garde que les films appartenant aux catégories valides (FR/EN)
            $allFilms = array_filter($allFilms, function ($film) use ($validCategoryIds) {
                return isset($validCategoryIds[$film['category_id']]);
            });

            // Si c'est une recherche, on filtre. Sinon on trie par date.
            if ($isGlobalSearch) {
                // Recherche sur tout
                $rawFilms = $allFilms; // Filtrage fait après
            } else {
                // "Recently Added" Logic
                // On trie par 'added' (timestamp) décroissant
                usort($allFilms, function ($a, $b) {
                    $tA = isset($a['added']) ? (int) $a['added'] : 0;
                    $tB = isset($b['added']) ? (int) $b['added'] : 0;
                    return $tB - $tA; // Descending
                });

                $rawFilms = $allFilms;
            }

        } elseif ($idCategorieSelectionnee) {
            // Catégorie spécifique
            $catIds = explode(',', $idCategorieSelectionnee);

            // OPTION 1: Check if we have the FULL catalog cached. If so, filter locally (FASTEST)
            $allFilmsCached = $this->cache->get('all_vod_streams_v2');

            if ($allFilmsCached !== null) {
                // We have everything! Just filter locally.
                $neededIds = array_flip($catIds); // ID => true
                $rawFilms = array_filter($allFilmsCached, function ($film) use ($neededIds) {
                    return isset($neededIds[$film['category_id']]);
                });
            } else {
                // OPTION 2: Fetch individual categories (Cached per category)
                foreach ($catIds as $fid) {
                    $cacheKeyCat = 'vod_streams_' . $fid;
                    $chunk = $this->cache->get($cacheKeyCat);

                    if ($chunk === null) {
                        $chunk = $this->api->get('player_api.php', [
                            'action' => 'get_vod_streams',
                            'category_id' => $fid
                        ]);
                        if ($chunk) {
                            $this->cache->set($cacheKeyCat, $chunk, 3600);
                        }
                    }

                    if ($chunk) {
                        $rawFilms = array_merge($rawFilms, $chunk);
                    }
                }
            }
        }

        // Recherche locale
        if ($isGlobalSearch) {
            $search = mb_strtolower($_GET['q']);
            $rawFilms = array_filter($rawFilms, function ($film) use ($search) {
                return strpos(mb_strtolower($film['name']), $search) !== false;
            });
        }

        // 4. Regroupement des films (Deduplication par TMDB ID ou Nom)
        $groupedFilms = [];
        foreach ($rawFilms as $film) {
            // Priorité absolue : TMDB ID
            if (!empty($film['tmdb'])) {
                $key = 'tmdb_' . $film['tmdb'];
            } else {
                // Fallback : Nom normalisé
                $key = 'name_' . mb_strtolower($this->normalizeName($film['name']));
            }

            // Si on n'a pas encore ce film, on l'ajoute
            if (!isset($groupedFilms[$key])) {
                // On injecte le nom normalisé pour l'affichage propre
                $film['display_name'] = $this->normalizeName($film['name']);
                $groupedFilms[$key] = $film;
            }
        }

        $finalFilms = array_values($groupedFilms);

        // MODE AJAX : On renvoie du JSON
        if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
            header('Content-Type: application/json');
            echo json_encode($finalFilms);
            exit; // Stop execution
        }

        // MODE HTML : On charge la vue avec les catégories, mais SANS les films (chargés par JS)
        $films = []; // Vide pour l'initialisation PHP

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
    public function getRecent($limit = 10)
    {
        // 1. Check Cache
        $cacheKey = 'movies_recent_fr_' . $limit;
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        // 2. Fetch All VOD Streams (Cached internally)
        $cacheKeyAll = 'all_vod_streams_v2';
        $allFilms = $this->cache->get($cacheKeyAll);

        if ($allFilms === null) {
            // Note: This fetches seemingly 20k+ movies, so filter loop must be efficient
            $allFilms = $this->api->get('player_api.php', [
                'action' => 'get_vod_streams'
            ]);
            if ($allFilms) {
                $this->cache->set($cacheKeyAll, $allFilms, 3600);
            }
        }

        if (!$allFilms) {
            return [];
        }

        // 3. Filter & Sort by Added Date
        usort($allFilms, function ($a, $b) {
            $tA = isset($a['added']) ? (int) $a['added'] : 0;
            $tB = isset($b['added']) ? (int) $b['added'] : 0;
            return $tB - $tA; // Descending
        });

        // 4. Slice & Normalize with STRICT LANGUAGE FILTER
        // We iterate through sorted list until we fill $limit
        $final = [];
        $seen = [];

        // Keywords that MUST be present to be considered French
        // "FR", "VFF", "VFQ", "TRUEFRENCH", "FRENCH", "VOSTFR"
        // Also negative lookahead for "AR", "Arabic", etc handled if needed by positive match
        // But simpler to just match French tags.
        $regexFrench = '/\b(FR|VFF|VF|VFQ|TRUEFRENCH|FRENCH|VOSTFR|MULTI)\b/i';

        // Keywords to EXCLUDE explicitly (AR, Arab, India) if they slip through
        $regexExclude = '/\b(AR|ARAB|ARABIC|INDIA|HINDI|LATINO)\b/i';

        foreach ($allFilms as $film) {
            $name = $film['name'];

            // CHECK 1: Must have French tag
            // Note: Some French movies might just have the title without tags (rare in IPTV lists).
            // Usually valid ones have tags like "Inception (2010) [FRENCH]"
            if (!preg_match($regexFrench, $name)) {
                continue;
            }

            // CHECK 2: Must NOT have Foreign tags (double check)
            if (preg_match($regexExclude, $name)) {
                continue;
            }

            $norm = mb_strtolower($this->normalizeName($name));
            if (isset($seen[$norm]))
                continue;

            $seen[$norm] = true;
            $film['display_name'] = $this->normalizeName($name);
            $final[] = $film;

            if (count($final) >= $limit)
                break;
        }

        // 5. Cache Result
        $this->cache->set($cacheKey, $final, 300);

        return $final;
    }
}
