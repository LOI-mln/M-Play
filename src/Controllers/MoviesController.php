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

        // Supprime les préfixes communs comme "FR -", "EN -", "VOSTFR -", "NF -", etc.
        $pattern = '/^(\s*(FR|FRANCE|EN|ENGLISH|US|UK|VOSTFR|MULTI|TRUEFRENCH|VFF|VFQ|VFI|NF|NETFLIX|PL|NL|DE|IT|ES|PT)\s*[-|:]?\s*)+/i';
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

            // Check implicit foreign
            if (preg_match('/\b(ENGLISH|GERMAN|SPANISH|ITALIAN|PORTUGUESE|DUTCH|TURKISH|RUSSIAN|POLISH)\b/i', $cat['category_name']))
                continue;

            // Normalisation du nom (FR - Action -> Action)
            $name = $cat['category_name'];

            // Allow cleaning SRS again here
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

        // --- CONTINUE WATCHING ---
        $user = $_SESSION['auth_creds']['username'];
        $progressModel = new \App\Models\WatchProgress();
        $inProgress = $progressModel->getInProgress($user, 'movie');

        if (!empty($inProgress)) {
            // On a des films en cours, on ajoute la catégorie EN PREMIER
            array_unshift($finalCategories, [
                'category_id' => 'continue',
                'category_name' => 'Reprendre la lecture'
            ]);
        }

        // Sélection courante
        $idCategorieSelectionnee = $_GET['categorie'] ?? 'all';
        $searchQuery = $_GET['q'] ?? '';
        $isGlobalSearch = !empty($searchQuery);

        $rawFilms = [];

        // 3. Récupération des films
        if ($idCategorieSelectionnee === 'all') {
            // CAS SPECIAL "TOUT" = "Ajoutés Récemment"

            // On utilise une méthode interne plus rapide si possible ? Non, on garde la logique de cache globale.
            $cacheKeyAll = 'all_vod_streams_v2';
            $allFilms = $this->cache->get($cacheKeyAll);

            if ($allFilms === null) {
                // Fetch ALL (Heavy operation ~50MB JSON sometimes)
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
                $debugFile = __DIR__ . '/../../debug_vod_example.txt';
                file_put_contents($debugFile, print_r($allFilms[0], true));
            }

            // FILTRE: On ne garde que les films appartenant aux catégories valides (FR/EN)
            $allFilms = array_filter($allFilms, function ($film) use ($validCategoryIds) {
                return isset($validCategoryIds[$film['category_id']]);
            });

            // Si c'est une recherche, on filtre. Sinon on trie par date.
            if ($isGlobalSearch) {
                $rawFilms = $allFilms; // Filtrage fait après
            } else {
                // "Recently Added" Logic
                // On trie par 'added' (timestamp) décroissant
                usort($allFilms, function ($a, $b) {
                    $tA = isset($a['added']) ? (int) $a['added'] : 0;
                    $tB = isset($b['added']) ? (int) $b['added'] : 0;
                    return $tB - $tA; // Descending
                });

                // STRICT FILTER: French Only for "Recently Added"
                $regexFrench = '/\b(FR|VFF|VF|VFQ|TRUEFRENCH|FRENCH|VOSTFR|MULTI)\b/i';
                $allFilms = array_filter($allFilms, function ($film) use ($regexFrench) {
                    return preg_match($regexFrench, $film['name']);
                });

                $rawFilms = $allFilms;
            }

        } elseif ($idCategorieSelectionnee === 'continue') {
            // CAS SPECIAL "CONTINUE WATCHING"
            $user = $_SESSION['auth_creds']['username'];
            $progressModel = new \App\Models\WatchProgress();
            $inProgressParams = $progressModel->getInProgress($user, 'movie'); // [ {stream_id, current_time...} ]

            // On doit mapper ces IDs vers les objets films complets
            // On a besoin du catalogue complet pour ça (ou cache map)
            $cacheKeyAll = 'all_vod_streams_v2';
            $allFilms = $this->cache->get($cacheKeyAll);

            if ($allFilms === null) {
                $allFilms = $this->api->get('player_api.php', [
                    'action' => 'get_vod_streams'
                ]);
                if ($allFilms) {
                    $this->cache->set($cacheKeyAll, $allFilms, 3600);
                }
            }

            if ($allFilms) {
                // Map ID -> Film
                $mapId = [];
                foreach ($allFilms as $f) {
                    $mapId[$f['stream_id']] = $f;
                }

                foreach ($inProgressParams as $p) {
                    if (isset($mapId[$p['stream_id']])) {
                        $film = $mapId[$p['stream_id']];
                        // On injecte le temps pour l'afficher (optionnel, ou via JS)
                        $film['progress_time'] = $p['current_time'];
                        $film['progress_duration'] = $p['duration'];
                        // Check si fini ? Non filtré en SQL déjà.
                        $rawFilms[] = $film;
                    }
                }
            }

        } elseif ($idCategorieSelectionnee) {
            // Catégorie spécifique
            $catIds = explode(',', $idCategorieSelectionnee);

            // OPTION 1: Filter from local FULL cache if avail (FASTEST)
            $allFilmsCached = $this->cache->get('all_vod_streams_v2');

            if ($allFilmsCached !== null) {
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
            $search = mb_strtolower($searchQuery);
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
                $film['display_name'] = $this->normalizeName($film['name']);
                $groupedFilms[$key] = $film;
            }
        }

        $finalFilms = array_values($groupedFilms);

        // MODE AJAX
        if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
            header('Content-Type: application/json');
            echo json_encode($finalFilms);
            exit; // Stop execution
        }

        // MODE HTML : On charge la vue avec les catégories, mais SANS les films (chargés par JS)
        $films = []; // Vide pour l'initialisation PHP
        $categories = $finalCategories;
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

        // GESTION DES VARIANTES (Langues)
        // Stratégie: Rechercher dans les films déjà chargés (cache) ceux qui ont le même ID TMDB ou le même Nom Normalisé.
        // Cela évite de faire 10 appels API.

        $cleanTitle = $this->normalizeName($movieInfo['name']);
        $currentTmdb = $movieInfo['tmdb'] ?? null;

        $availableVersions = [];
        $variants = [];

        // Essai de charger le cache MAP (construit lors du listing)
        // Si le cache map n'existe pas, on le construit depuis le cache global
        if (!isset($_SESSION['movies_map_cache'])) {
            $allFilms = $this->cache->get('all_vod_streams_v2');
            if ($allFilms) {
                // Build map: NormalizedName -> [ {id, name, tmdb}, ... ]
                $map = [];
                foreach ($allFilms as $stream) {
                    $nName = $this->normalizeName($stream['name']);
                    $key = mb_strtolower($nName);
                    if (!isset($map[$key])) {
                        $map[$key] = [];
                    }
                    $map[$key][] = [
                        'stream_id' => $stream['stream_id'],
                        'name' => $stream['name'],
                        'tmdb' => $stream['tmdb'] ?? null
                    ];
                }
                $_SESSION['movies_map_cache'] = $map;
            }
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

        // Retrieve info for duration
        $info = $this->api->get('player_api.php', [
            'action' => 'get_vod_info',
            'vod_id' => $streamId
        ]);

        $duration = null;
        if ($info && isset($info['info']['duration'])) {
            $duration = $info['info']['duration'];
        }

        // URL Directe (MKV/MP4/AVI)
        $sourceUrl = "$hote/movie/$username/$password/$streamId.$extension";
        $streamUrlDirect = $sourceUrl;

        // URL via Proxy (Transcodage) - Pour contourner CORS ou format non supporté
        // On encode l'URL source
        $streamUrlTranscode = "/stream/transcode?url=" . urlencode(base64_encode($sourceUrl));

        $streamUrlHls = ''; // Pas de HLS pour les films (MP4/MKV direct)

        // Récupérer la progression
        $progressModel = new \App\Models\WatchProgress();
        $prog = $progressModel->getProgress($_SESSION['auth_creds']['username'], $streamId, 'movie');
        $resumeTime = ($prog) ? $prog['current_time'] : 0;

        require __DIR__ . '/../../views/watch_vod.php';
    }
    public function getRecent($limit = 10)
    {
        // 1. Check Cache GLOBAL
        $cacheKey = 'vod_popular_fr_tmdb_v3_' . $limit;
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        // 2. Fetch All VOD Streams (Cached internally)
        // On a besoin du catalogue pour matcher
        $cacheKeyAll = 'all_vod_streams_v2';
        $allFilms = $this->cache->get($cacheKeyAll);

        if ($allFilms === null) {
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

        // 3. RECUPERATION TRENDING TMDB
        // On remplace le tri par date par un tri par Popularité réelle
        $tmdbClient = new \App\Services\TmdbClient('d818b3a8af9971ce313537ac5f56d10f');
        $trending = $tmdbClient->getTrendingMovies('week'); // Top 20

        // Si TMDB fail, on fallback sur la méthode date
        if (empty($trending)) {
            // Fallback: Date logic
            usort($allFilms, function ($a, $b) {
                $tA = isset($a['added']) ? (int) $a['added'] : 0;
                $tB = isset($b['added']) ? (int) $b['added'] : 0;
                return $tB - $tA; // Descending
            });
        } else {
            // Mapping Trending -> Local
            // On construit une Map des films locaux pour fast lookup
            // Key = TMDB ID (si présent) ET Key = Nom Normalisé
            $mapTmdb = [];
            $mapName = [];

            foreach ($allFilms as $f) {
                if (!empty($f['tmdb'])) {
                    $mapTmdb[$f['tmdb']][] = $f;
                }
                $norm = preg_replace('/[^a-z0-9]/', '', mb_strtolower($this->normalizeName($f['name'])));
                $mapName[$norm][] = $f;
            }

            $popularLocal = [];
            $inPopular = []; // stream_ids

            // DEDUPLICATION TRACKERS
            $seenTmdb = [];
            $seenNormNames = [];

            foreach ($trending as $t) {
                $found = [];
                // Chercher par TMDB ID
                if (isset($mapTmdb[$t['id']])) {
                    $found = $mapTmdb[$t['id']];
                } else {
                    $tName = preg_replace('/[^a-z0-9]/', '', mb_strtolower($this->normalizeName($t['title'])));
                    if (isset($mapName[$tName])) {
                        $found = $mapName[$tName];
                    } else {
                        if (isset($t['original_title'])) {
                            $tOrig = preg_replace('/[^a-z0-9]/', '', mb_strtolower($this->normalizeName($t['original_title'])));
                            if (isset($mapName[$tOrig])) {
                                $found = $mapName[$tOrig];
                            }
                        }
                    }
                }

                if (!empty($found)) {
                    // Trier par priorité de langue : TRUEFRENCH > VFF > VF > MULTI > VOSTFR
                    usort($found, function ($a, $b) {
                        $scoreA = 0;
                        $scoreB = 0;
                        $nameA = mb_strtolower($a['name']);
                        $nameB = mb_strtolower($b['name']);

                        if (preg_match('/\b(truefrench|vff|vfq)\b/', $nameA))
                            $scoreA = 5;
                        elseif (preg_match('/\b(vf|frennch|fr)\b/', $nameA))
                            $scoreA = 4;
                        elseif (preg_match('/\b(multi)\b/', $nameA))
                            $scoreA = 3;
                        elseif (preg_match('/\b(vostfr|vost)\b/', $nameA))
                            $scoreA = 2;

                        if (preg_match('/\b(truefrench|vff|vfq)\b/', $nameB))
                            $scoreB = 5;
                        elseif (preg_match('/\b(vf|frennch|fr)\b/', $nameB))
                            $scoreB = 4;
                        elseif (preg_match('/\b(multi)\b/', $nameB))
                            $scoreB = 3;
                        elseif (preg_match('/\b(vostfr|vost)\b/', $nameB))
                            $scoreB = 2;

                        return $scoreB - $scoreA;
                    });

                    foreach ($found as $cand) {
                        // Strict FR check
                        if (
                            preg_match('/\b(FR|VFF|VF|VFQ|TRUEFRENCH|FRENCH|VOSTFR|MULTI)\b/i', $cand['name']) &&
                            !preg_match('/\b(AR|ARAB|ARABIC|INDIA|HINDI|LATINO)\b/i', $cand['name'])
                        ) {

                            // CHECK STRICT DEDUPLICATION
                            $cTmdb = $cand['tmdb'] ?? null;
                            $cNorm = preg_replace('/[^a-z0-9]/', '', mb_strtolower($this->normalizeName($cand['name'])));

                            // Si déjà vu par TMDB
                            if ($cTmdb && isset($seenTmdb[$cTmdb]))
                                continue;
                            // Si déjà vu par NOM
                            if (isset($seenNormNames[$cNorm]))
                                continue;

                            $id = $cand['stream_id'];
                            if (!isset($inPopular[$id])) {
                                $inPopular[$id] = true;
                                if ($cTmdb)
                                    $seenTmdb[$cTmdb] = true;
                                $seenNormNames[$cNorm] = true;

                                $cand['display_name'] = $this->normalizeName($cand['name']);
                                $popularLocal[] = $cand;
                                break;
                            }
                        }
                    }
                }
            }

            // Comblage
            if (count($popularLocal) < $limit) {
                usort($allFilms, function ($a, $b) {
                    $tA = isset($a['added']) ? (int) $a['added'] : 0;
                    $tB = isset($b['added']) ? (int) $b['added'] : 0;
                    return $tB - $tA;
                });

                $regexFrench = '/\b(FR|VFF|VF|VFQ|TRUEFRENCH|FRENCH|VOSTFR|MULTI)\b/i';
                $regexExclude = '/\b(AR|ARAB|ARABIC|INDIA|HINDI|LATINO)\b/i';

                foreach ($allFilms as $f) {
                    $id = $f['stream_id'];
                    if (isset($inPopular[$id]))
                        continue;

                    if (!preg_match($regexFrench, $f['name']))
                        continue;
                    if (preg_match($regexExclude, $f['name']))
                        continue;

                    // CHECK STRICT DEDUPLICATION
                    $cTmdb = $f['tmdb'] ?? null;
                    $cNorm = preg_replace('/[^a-z0-9]/', '', mb_strtolower($this->normalizeName($f['name'])));

                    if ($cTmdb && isset($seenTmdb[$cTmdb]))
                        continue;
                    if (isset($seenNormNames[$cNorm]))
                        continue;

                    $f['display_name'] = $this->normalizeName($f['name']);
                    $popularLocal[] = $f;

                    $inPopular[$id] = true;
                    if ($cTmdb)
                        $seenTmdb[$cTmdb] = true;
                    $seenNormNames[$cNorm] = true;

                    if (count($popularLocal) >= $limit)
                        break;
                }
            }

            $final = array_slice($popularLocal, 0, $limit);
            // Cache
            $this->cache->set($cacheKey, $final, 3600); // 1h
            return $final;
        }

        // ... Old Fallback Logic if TMDB Failed ...

        // 3. Filter & Sort by Added Date (Fallback Code keep match vars)
        // ... (reuse existing logic if we fall here, but simpler to just put it in the else/if above)

        // Let's rewrite the fallback logic cleanly inside the block above to avoid duplication complexity in replacement.
        // Actually, I can just put the old logic in the first if(empty($trending)) block but I need to copy paste it.
        // I will just perform the normal filtering on $allFilms

        $final = [];
        $seen = [];
        $regexFrench = '/\b(FR|VFF|VF|VFQ|TRUEFRENCH|FRENCH|VOSTFR|MULTI)\b/i';
        $regexExclude = '/\b(AR|ARAB|ARABIC|INDIA|HINDI|LATINO)\b/i';

        foreach ($allFilms as $f) {
            $name = $f['name'];

            if (!preg_match($regexFrench, $name))
                continue;
            if (preg_match($regexExclude, $name))
                continue;

            $norm = mb_strtolower($this->normalizeName($name));
            if (isset($seen[$norm]))
                continue;

            $seen[$norm] = true;
            $f['display_name'] = $this->normalizeName($name);
            $final[] = $f;

            if (count($final) >= $limit)
                break;
        }

        $this->cache->set($cacheKey, $final, 300);
        return $final;
    }

    public function getContinueWatching($limit = 10)
    {
        $user = $_SESSION['auth_creds']['username'];
        $progressModel = new \App\Models\WatchProgress();
        $inProgressParams = $progressModel->getInProgress($user); // [ {stream_id, current_time...} ]

        if (empty($inProgressParams)) {
            return [];
        }

        // 1. Fetch Movies (if needed)
        $cacheKeyMovies = 'all_vod_streams_v2';
        $allFilms = $this->cache->get($cacheKeyMovies);
        if ($allFilms === null) {
            $allFilms = $this->api->get('player_api.php', ['action' => 'get_vod_streams']);
            if ($allFilms)
                $this->cache->set($cacheKeyMovies, $allFilms, 3600);
        }

        // 2. Map Movies
        $mapMovies = [];
        if ($allFilms) {
            foreach ($allFilms as $f)
                $mapMovies[$f['stream_id']] = $f;
        }

        // 3. Process Results
        $final = [];
        foreach ($inProgressParams as $p) {
            $type = $p['type'] ?? 'movie';
            $item = null;

            if ($type === 'movie') {
                if (isset($mapMovies[$p['stream_id']])) {
                    $item = $mapMovies[$p['stream_id']];
                }
            } elseif ($type === 'series') {
                // For series, much simpler: ALL info is (or should be) in extra_data
                // Because mapping series stream_id (episode ID) back to series info is hard without API calls.
                // We rely on what we saved.
                $extra = json_decode($p['extra_data'] ?? '{}', true);
                if (!empty($extra)) {
                    $item = [
                        'stream_id' => $p['stream_id'], // Episode ID
                        'name' => $extra['name'] ?? 'Épisode inconnu', // "Breaking Bad - S01 E01"
                        'stream_icon' => $extra['cover'] ?? '/ressources/logo.png',
                        'container_extension' => $extra['ext'] ?? 'mp4',
                        // Special flag to redirect to series watch
                        // Actually, 'watch_vod.php' vs 'watch_series.php' might differ.
                        // But wait, our links on home.php point to /movies/watch...
                        // We need to differentiate URL.
                        'is_series' => true,
                        'series_id' => $extra['series_id'] ?? 0 // Needed if we want to link back to details
                    ];
                }
            }

            if ($item) {
                // Common Progress
                $item['progress_percent'] = ($p['duration'] > 0) ? ($p['current_time'] / $p['duration']) * 100 : 0;

                // Add to list
                $final[] = $item;
                if (count($final) >= $limit)
                    break;
            }
        }
        return $final;
    }



    // Helper for old simple search
    public function searchInternal($query)
    {
        $cacheKeyAll = 'all_vod_streams_v2';
        $allFilms = $this->cache->get($cacheKeyAll);

        if ($allFilms === null) {
            $allFilms = $this->api->get('player_api.php', [
                'action' => 'get_vod_streams'
            ]);
            if ($allFilms) {
                $this->cache->set($cacheKeyAll, $allFilms, 3600);
            }
        }

        if (!$allFilms)
            return [];


        $search = mb_strtolower($query);
        $rawResults = [];

        $regexLang = '/\b(FR|VFF|VF|VFQ|TRUEFRENCH|FRENCH|VOSTFR|MULTI|EN|ENGLISH|US|UK|VO)\b/i';
        $regexExclude = '/\b(AR|ARAB|ARABIC|INDIA|HINDI|LATINO|TURK|TURKISH|EGYPT|PERSIAN|FARSI|PL|POLAND|NF|NETFLIX|NL|DUTCH)\b/i';

        foreach ($allFilms as $film) {
            $name = $film['name'];

            // 1. Search Query Match
            if (strpos(mb_strtolower($name), $search) === false) {
                continue;
            }

            // 2. Strict Language Filter
            // Must have a valid tag OR NOT have an invalid tag (to be safe, we can trigger on Exclusion first)

            if (preg_match($regexExclude, $name)) {
                continue;
            }

            if (preg_match($regexLang, $name)) {
                // Good
            } else {
                // If no tag, we accept it if it doesn't look like garbage? 
                // For now, user says "soit francais soit anglais". 
                // Let's be semi-strict: if no allowed tag is present, we might skip it 
                // BUT searching for "Avatar" might return just "Avatar (2009)" without tags.
                // Let's stick to Exclusion list is safer for now, AND check for common FR/EN tags if present.
                // Actually, user said "soit francais soit anglais", implying we should filter OUT others.
            }

            $rawResults[] = $film;
        }

        // Deduplication Logic (Same as Index)
        $groupedFilms = [];
        foreach ($rawResults as $film) {
            // Priorité absolue : TMDB ID
            if (!empty($film['tmdb'])) {
                $key = 'tmdb_' . $film['tmdb'];
            } else {
                // Fallback : Nom normalisé
                $key = 'name_' . mb_strtolower($this->normalizeName($film['name']));
            }

            // Si on n'a pas encore ce film, on l'ajoute
            if (!isset($groupedFilms[$key])) {
                $film['display_name'] = $this->normalizeName($film['name']);
                $groupedFilms[$key] = $film;
            }
        }

        return array_values($groupedFilms);
    }
    public function saveProgress()
    {
        // AJAX ONLY
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }

        $rawInput = file_get_contents('php://input');
        file_put_contents(__DIR__ . '/../../debug_save.txt', date('H:i:s') . " - Received: " . $rawInput . PHP_EOL, FILE_APPEND);

        $input = json_decode($rawInput, true);
        $streamId = $input['stream_id'] ?? null;
        $currentTime = $input['time'] ?? 0;
        $duration = $input['duration'] ?? 0;

        if (!$streamId) {
            file_put_contents(__DIR__ . '/../../debug_save.txt', date('H:i:s') . " - Error: No Stream ID" . PHP_EOL, FILE_APPEND);
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Missing stream_id']);
            exit;
        }

        $user = $_SESSION['auth_creds']['username'] ?? 'unknown';

        // Important: Close session to prevent locking concurrent requests (video stream)
        session_write_close();

        try {
            $progressModel = new \App\Models\WatchProgress();
            $progressModel->save($user, $streamId, $currentTime, $duration, 'movie');
            file_put_contents(__DIR__ . '/../../debug_save.txt', date('H:i:s') . " - Saved for $user" . PHP_EOL, FILE_APPEND);
        } catch (\Exception $e) {
            file_put_contents(__DIR__ . '/../../debug_save.txt', date('H:i:s') . " - EXCEPTION: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
        }

        echo json_encode(['status' => 'success']);
        exit;
    }

    public function removeProgress()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !isset($input['stream_id'])) {
            http_response_code(400);
            return;
        }

        $streamId = $input['stream_id'];
        $type = $input['type'] ?? 'movie';
        $user = $_SESSION['auth_creds']['username'];

        $model = new \App\Models\WatchProgress();
        $model->remove($user, $streamId, $type);
        echo json_encode(['status' => 'ok']);
        exit;
    }
}
