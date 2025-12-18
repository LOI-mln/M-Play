<?php

namespace App\Controllers;

use App\Services\XtreamClient;
use App\Services\FileCache;

class SeriesController
{
    private $api;
    private $cache;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['host']) || !isset($_SESSION['auth_creds'])) {
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

    // Helper pour nettoyer et normaliser les noms de catégories ou films
    private function normalizeName($name)
    {
        // 0. Nettoyage spécifique "SRS" demandés par l'utilisateur
        $name = str_ireplace('SRS', '', $name);

        // 1. Décodage entités HTML
        $name = html_entity_decode($name, ENT_QUOTES | ENT_HTML5);

        // 2. Suppression des préfixes de langue courants (ex: "FR - ", "EN | ", "[FR] ")
        // Regex explication:
        // ^(...) : au début
        // [\[\(]? : optionnel crochet/parenthèse ouvrante
        // (FR|EN|VO|VF|VFF|VOSTFR|MULTI|TRUEFRENCH|FRENCH|ENGLISH) : langues
        // [\]\)]? : optionnel crochet/parenthèse fermante
        // \s*[-|:]?\s* : séparateur (espace, tiret, pipe, deux-points)
        // ET aussi suffixes à la fin
        $regexLang = '/\b(FR|EN|VO|VF|VFF|VOSTFR|MULTI|TRUEFRENCH|FRENCH|ENGLISH|US|UK|NL|DE|IT|ES|PT|NF|NETFLIX|PL)\b/i';

        // On enlève d'abord les tags "clairs"
        // Ex: "Action [FR]", "FR - Action"
        $name = preg_replace('/^[\[\(]?\b(FR|FRANCE|VF|VFF|VO|VOSTFR|MULTI|TRUEFRENCH|FRENCH|EN|ENGLISH|US|UK|NL|DE|IT|ES|PT|NF|NETFLIX|PL)\b[\]\)]?\s*[-|:]?\s*/i', '', $name);
        $name = preg_replace('/\s*[-|:]?\s*[\[\(]?\b(FR|FRANCE|VF|VFF|VO|VOSTFR|MULTI|TRUEFRENCH|FRENCH|EN|ENGLISH|US|UK|NL|DE|IT|ES|PT|NF|NETFLIX|PL)\b[\]\)]?$/i', '', $name);

        // 3. Suppression des tags techniques ou années entre parenthèses / crochets
        // Ex: (2023), [4K], [HEVC]
        $tagsToRemove = ['h265', 'h264', 'xx', 'hevc', '4k', 'uhd', '1080p', '720p', 'hd', 'sd', 'multisub', '3d', 'complete', 'saison', 'season'];
        $patternTags = '/[\[\(].*?[\]\)]/u'; // Contenu entre () ou []

        // On nettoie d'abord le contenu entre parenthèses si c'est juste une année ou technique
        // Mais attention, parfois le titre EST entre parenthèses (rare). 
        // Simplification: on enlève les blocs connus.

        $name = preg_replace_callback($patternTags, function ($matches) use ($tagsToRemove) {
            $content = mb_strtolower($matches[0]);
            foreach ($tagsToRemove as $tag) {
                if (strpos($content, $tag) !== false)
                    return '';
            }
            // Année (19xx or 20xx)
            if (preg_match('/(19|20)\d{2}/', $content))
                return '';
            return $matches[0]; // On garde si inconnue
        }, $name);

        // 4. Nettoyage final
        $name = trim($name, " -|:");

        // Tout en majuscule pour le premier caractère
        return ucfirst(trim($name));
    }

    public function index()
    {
        // 1. Caching des catégories
        $categoriesVars = $this->cache->get('series_categories_v2');
        if ($categoriesVars === null) {
            $rawCats = $this->api->get('player_api.php', ['action' => 'get_series_categories']);
            $categoriesVars = $rawCats ?: [];
            $this->cache->set('series_categories_v2', $categoriesVars, 86400); // 24h cache (categories don't change often)
        }

        // 2. Filtrage & Normalisation (Global Categories)
        $normalizedCategories = []; // "Action" => "12,45,89" (IDs combinés)
        $validCategoryIds = [];

        foreach ($categoriesVars as $cat) {
            $nameRaw = $cat['category_name'];
            $upperName = mb_strtoupper($nameRaw); // Use UPPERCASE for checks

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

            if (preg_match($regexExclusion, $nameRaw))
                continue;

            // Check for Arabic/Asian chars (sauf si on voulait garder...)
            if (preg_match('/\p{Arabic}|\p{Han}|\p{Cyrillic}/u', $nameRaw))
                continue;

            // FILTRE "PAS DE SRS" (Si le nom c'est juste SRS, on vire, sinon on nettoie)
            if (trim($upperName) === 'SRS')
                continue;

            // INCLUSIONS : Priorité FR
            // On veut ce qui est FR, VF, VOSTFR, MULTI, QUEBEC
            // OU ce qui est générique (Action, Comédie...) SANS tag étranger.

            // Est-ce implicitement étranger ? (ex: "ENGLISH MOVIES")
            if (preg_match('/\b(ENGLISH|GERMAN|SPANISH|ITALIAN|PORTUGUESE|DUTCH|TURKISH|RUSSIAN|POLISH)\b/i', $nameRaw))
                continue;

            // Normalisation
            // Le but: "FR - Action" et "EN - Action" deviennent "Action"
            $cleanName = $this->normalizeName($nameRaw);

            // Si vide après nettoyage, on skip
            if (empty($cleanName))
                continue;

            // Si le nom nettoyé est trop court (genre 1 lettre), suspect
            if (mb_strlen($cleanName) < 2)
                continue;

            // 3. REGROUPEMENT (APPLE, CANAL, ETC.)
            $upperClean = mb_strtoupper($cleanName);

            if (strpos($upperClean, 'APPLE') !== false) {
                $cleanName = 'Apple TV+';
            } elseif (strpos($upperClean, 'CANAL') !== false) {
                $cleanName = 'Canal+';
            } elseif (strpos($upperClean, 'NETFLIX') !== false) {
                $cleanName = 'Netflix';
            } elseif (strpos($upperClean, 'DISNEY') !== false) {
                $cleanName = 'Disney+';
            } elseif (strpos($upperClean, 'AMAZON') !== false || strpos($upperClean, 'PRIME VIDEO') !== false) {
                $cleanName = 'Amazon Prime Video';
            } elseif (strpos($upperClean, 'PARAMOUNT') !== false) {
                $cleanName = 'Paramount+';
            } elseif (strpos($upperClean, 'OOCS') !== false) { // OCS / OOCS spelling
                $cleanName = 'OCS';
            }

            $validCategoryIds[$cat['category_id']] = true;

            $normKey = mb_strtolower($cleanName);
            if (!isset($normalizedCategories[$normKey])) {
                $normalizedCategories[$normKey] = [
                    'name' => $cleanName,
                    'ids' => []
                ];
            }
            $normalizedCategories[$normKey]['ids'][] = $cat['category_id'];
        }

        // Reconversion en tableau indexé pour la Vue
        $finalCategories = [];
        foreach ($normalizedCategories as $entry) {
            $finalCategories[] = [
                'category_name' => $entry['name'],
                'category_id' => implode(',', $entry['ids']) // "12,45"
            ];
        }

        // Tri par IMPORTANCE (Demandé par user)
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

        // Catégorie "TOUT" (Recently Added)
        array_unshift($finalCategories, [
            'category_id' => 'all',
            'category_name' => 'Ajoutés Récemment'
        ]);

        // Sélection courante
        $idCategorieSelectionnee = $_GET['categorie'] ?? 'all';
        $searchQuery = $_GET['q'] ?? '';
        $isGlobalSearch = !empty($searchQuery);

        $rawSeries = [];

        // 3. Récupération des séries
        if ($idCategorieSelectionnee === 'all') {
            // CAS SPECIALE "TOUT" = "Ajoutés Récemment"

            $cacheKeyAll = 'all_series_streams_v2';
            $allSeries = $this->cache->get($cacheKeyAll);

            if ($allSeries === null) {
                $allSeries = $this->api->get('player_api.php', ['action' => 'get_series']);
                if (!$allSeries)
                    $allSeries = [];
                // Cache for 1 hour
                $this->cache->set($cacheKeyAll, $allSeries, 3600);
            }

            // FILTRE: On ne garde que les séries des catégories valides
            $allSeries = array_filter($allSeries, function ($s) use ($validCategoryIds) {
                return isset($validCategoryIds[$s['category_id']]);
            });

            if ($isGlobalSearch) {
                $rawSeries = $allSeries;
            } else {
                // Tri par date d'ajout (descendant)
                /* Note: Series sometimes use 'last_modified' or don't have 'added'. 
                   Usually 'last_modified' is safer for series updates. */
                usort($allSeries, function ($a, $b) {
                    $tA = isset($a['last_modified']) ? (int) $a['last_modified'] : (isset($a['added']) ? (int) $a['added'] : 0);
                    $tB = isset($b['last_modified']) ? (int) $b['last_modified'] : (isset($b['added']) ? (int) $b['added'] : 0);
                    return $tB - $tA;
                });

                // STRICT FILTER: French Only for "Recently Added"
                $regexFrench = '/\b(FR|VFF|VF|VFQ|TRUEFRENCH|FRENCH|VOSTFR|MULTI)\b/i';
                $allSeries = array_filter($allSeries, function ($s) use ($regexFrench) {
                    return preg_match($regexFrench, $s['name']);
                });

                $rawSeries = $allSeries;
            }

        } elseif ($idCategorieSelectionnee) {
            // Catégorie spécifique (peut être multi-ID "12,45")
            $catIds = explode(',', $idCategorieSelectionnee);

            // OPTION 1: Filter from local FULL cache if avail (FASTEST)
            $allSeriesCached = $this->cache->get('all_series_streams_v2');

            if ($allSeriesCached !== null) {
                $neededIds = array_flip($catIds);
                $rawSeries = array_filter($allSeriesCached, function ($s) use ($neededIds) {
                    return isset($neededIds[$s['category_id']]);
                });
            } else {
                // OPTION 2: Fetch individual chunks
                foreach ($catIds as $fid) {
                    $cacheKeyCat = 'series_streams_' . $fid . '_v2';
                    $chunk = $this->cache->get($cacheKeyCat);

                    if ($chunk === null) {
                        $chunk = $this->api->get('player_api.php', [
                            'action' => 'get_series',
                            'category_id' => $fid
                        ]);
                        if ($chunk) {
                            $this->cache->set($cacheKeyCat, $chunk, 3600);
                        }
                    }

                    if ($chunk) {
                        $rawSeries = array_merge($rawSeries, $chunk);
                    }
                }
            }
        }

        // Recherche locale
        if ($isGlobalSearch) {
            $search = mb_strtolower($searchQuery);
            $rawSeries = array_filter($rawSeries, function ($s) use ($search) {
                return strpos(mb_strtolower($s['name']), $search) !== false;
            });
        }

        // 4. Regroupement / Deduplication (TMDB or Name)
        $groupedSeries = [];
        foreach ($rawSeries as $serie) {
            // Priorité TMDB (Often present in 'tmdb' key, sometimes 'tmdb_id' check debug later if needed, assume 'tmdb' same as movies)
            // Note: Series API usually returns 'cover' instead of 'stream_icon', keys might differ slightly.

            if (!empty($serie['tmdb'])) {
                $key = 'tmdb_' . $serie['tmdb'];
            } else {
                $key = 'name_' . mb_strtolower($this->normalizeName($serie['name']));
            }

            if (!isset($groupedSeries[$key])) {
                $serie['display_name'] = $this->normalizeName($serie['name']);
                $groupedSeries[$key] = $serie;
            }
        }

        $finalSeries = array_values($groupedSeries);

        // MODE AJAX : JSON Response
        if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
            header('Content-Type: application/json');
            echo json_encode($finalSeries);
            exit;
        }

        // View Variables
        $categories = $finalCategories;
        $categorieActuelleId = $idCategorieSelectionnee;
        $series = []; // Empty for initial load

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

        // 2. Gestion des Variantes (Langues) - Similaire aux Films
        // On doit retrouver les autres versions de cette série (même TMDB ou même nom normalisé)

        // Nom propre pour l'affichage
        $cleanTitle = $this->normalizeName($seriesInfo['name']);
        $currentTmdb = $seriesInfo['tmdb'] ?? null; // Attention: parfois dans 'info', parfois non.

        $availableVersions = [];

        // On charge la liste complète (cached) pour trouver les frères
        // C'est rapide car en mémoire/fichier
        $allSeries = $this->cache->get('all_series_streams_v2');

        if ($allSeries) {
            // Clé de recherche : TMDB (si dispo) OU Nom Normalisé
            // NOTE: $seriesInfo de get_series_info a pas tjs les mêmes champs que get_series list.
            // On va essayer de matcher avec la liste globale.

            // On filtre ceux qui correspondent
            foreach ($allSeries as $s) {
                // Match par TMDB ?
                if ($currentTmdb && !empty($s['tmdb']) && $s['tmdb'] == $currentTmdb) {
                    $availableVersions[] = $s;
                    continue;
                }

                // Match par Nom Normalisé ?
                $nName = $this->normalizeName($s['name']);
                if (mb_strtolower($nName) === mb_strtolower($cleanTitle)) {
                    $availableVersions[] = $s;
                }
            }
        }

        // Deduplication des versions (par series_id)
        $uniqueVersions = [];
        foreach ($availableVersions as $v) {
            $uniqueVersions[$v['series_id']] = $v;
        }
        $availableVersions = array_values($uniqueVersions);

        // Fallback: Si liste vide (cache expiré ou autre), on met au moins l'actuelle
        if (empty($availableVersions)) {
            $availableVersions[] = [
                'series_id' => $seriesId,
                'name' => $seriesInfo['name'],
                'cover' => $seriesInfo['cover']
            ];
        }

        require __DIR__ . '/../../views/series_details.php';
    }

    public function watch()
    {
        $streamId = $_GET['id'] ?? null;
        $extension = $_GET['ext'] ?? 'mp4';
        $duration = $_GET['duration'] ?? ''; // Passé depuis la vue détails

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
    public function getRecent($limit = 10)
    {
        // 1. Check Cache GLOBAL
        $cacheKey = 'series_popular_fr_tmdb_v3_' . $limit;
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        // 2. Fetch All Series (Cached internally)
        $cacheKeyAll = 'all_series_streams_v2';
        $allSeries = $this->cache->get($cacheKeyAll);

        if ($allSeries === null) {
            $allSeries = $this->api->get('player_api.php', ['action' => 'get_series']);
            if (!$allSeries)
                $allSeries = [];
            $this->cache->set($cacheKeyAll, $allSeries, 3600);
        }

        if (!$allSeries) {
            return [];
        }

        // 3. RECUPERATION TRENDING TMDB
        $tmdbClient = new \App\Services\TmdbClient('d818b3a8af9971ce313537ac5f56d10f');
        $trending = $tmdbClient->getTrendingSeries('week');

        if (empty($trending)) {
            // Fallback: Date logic
            usort($allSeries, function ($a, $b) {
                $tA = isset($a['last_modified']) ? (int) $a['last_modified'] : (isset($a['added']) ? (int) $a['added'] : 0);
                $tB = isset($b['last_modified']) ? (int) $b['last_modified'] : (isset($b['added']) ? (int) $b['added'] : 0);
                return $tB - $tA;
            });
        } else {
            // Mapping Trending -> Local
            $mapTmdb = [];
            $mapName = [];

            foreach ($allSeries as $s) {
                if (!empty($s['tmdb'])) {
                    // Sometimes series don't have tmdb field in list, check fields
                    $mapTmdb[$s['tmdb']][] = $s;
                }
                $norm = preg_replace('/[^a-z0-9]/', '', mb_strtolower($this->normalizeName($s['name'])));
                $mapName[$norm][] = $s;
            }

            $popularLocal = [];
            $inPopular = [];
            $seenTmdb = [];
            $seenNormNames = [];

            foreach ($trending as $t) {
                $found = [];
                if (isset($mapTmdb[$t['id']])) {
                    $found = $mapTmdb[$t['id']];
                } else {
                    $tName = preg_replace('/[^a-z0-9]/', '', mb_strtolower($this->normalizeName($t['name'])));
                    if (isset($mapName[$tName])) {
                        $found = $mapName[$tName];
                    } else {
                        if (isset($t['original_name'])) {
                            $tOrig = preg_replace('/[^a-z0-9]/', '', mb_strtolower($this->normalizeName($t['original_name'])));
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

                            if ($cTmdb && isset($seenTmdb[$cTmdb]))
                                continue;
                            if (isset($seenNormNames[$cNorm]))
                                continue;

                            $id = $cand['series_id'];
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

            // Comblage si pas assez
            if (count($popularLocal) < $limit) {
                usort($allSeries, function ($a, $b) {
                    $tA = isset($a['last_modified']) ? (int) $a['last_modified'] : (isset($a['added']) ? (int) $a['added'] : 0);
                    $tB = isset($b['last_modified']) ? (int) $b['last_modified'] : (isset($b['added']) ? (int) $b['added'] : 0);
                    return $tB - $tA;
                });

                $regexFrench = '/\b(FR|VFF|VF|VFQ|TRUEFRENCH|FRENCH|VOSTFR|MULTI)\b/i';
                $regexExclude = '/\b(AR|ARAB|ARABIC|INDIA|HINDI|LATINO)\b/i';

                foreach ($allSeries as $s) {
                    $id = $s['series_id'];
                    if (isset($inPopular[$id]))
                        continue;

                    if (!preg_match($regexFrench, $s['name']))
                        continue;
                    if (preg_match($regexExclude, $s['name']))
                        continue;

                    // CHECK STRICT DEDUPLICATION
                    $cTmdb = $s['tmdb'] ?? null;
                    $cNorm = preg_replace('/[^a-z0-9]/', '', mb_strtolower($this->normalizeName($s['name'])));

                    if ($cTmdb && isset($seenTmdb[$cTmdb]))
                        continue;
                    if (isset($seenNormNames[$cNorm]))
                        continue;

                    $s['display_name'] = $this->normalizeName($s['name']);
                    $popularLocal[] = $s;

                    $inPopular[$id] = true;
                    if ($cTmdb)
                        $seenTmdb[$cTmdb] = true;
                    $seenNormNames[$cNorm] = true;

                    if (count($popularLocal) >= $limit)
                        break;
                }
            }
            $final = array_slice($popularLocal, 0, $limit);
            $this->cache->set($cacheKey, $final, 3600);
            return $final;
        }

        // Fallback Logic Re-implementation
        $final = [];
        $seen = [];

        $regexFrench = '/\b(FR|VFF|VF|VFQ|TRUEFRENCH|FRENCH|VOSTFR|MULTI)\b/i';
        $regexExclude = '/\b(AR|ARAB|ARABIC|INDIA|HINDI|LATINO)\b/i';

        foreach ($allSeries as $s) {
            $name = $s['name'];

            if (!preg_match($regexFrench, $name))
                continue;
            if (preg_match($regexExclude, $name))
                continue;

            $norm = mb_strtolower($this->normalizeName($name));
            if (isset($seen[$norm]))
                continue;

            $seen[$norm] = true;
            $s['display_name'] = $this->normalizeName($name);
            $final[] = $s;

            if (count($final) >= $limit)
                break;
        }

        // 5. Cache
        $this->cache->set($cacheKey, $final, 300);

        return $final;
    }

    public function searchInternal($query)
    {
        $cacheKeyAll = 'all_series_streams_v2';
        $allSeries = $this->cache->get($cacheKeyAll);

        if ($allSeries === null) {
            $allSeries = $this->api->get('player_api.php', ['action' => 'get_series']);
            if (!$allSeries)
                $allSeries = [];
            $this->cache->set($cacheKeyAll, $allSeries, 3600);
        }

        if (!$allSeries)
            return [];


        $search = mb_strtolower($query);
        $rawResults = [];

        $regexLang = '/\b(FR|VFF|VF|VFQ|TRUEFRENCH|FRENCH|VOSTFR|MULTI|EN|ENGLISH|US|UK|VO)\b/i';
        $regexExclude = '/\b(AR|ARAB|ARABIC|INDIA|HINDI|LATINO|TURK|TURKISH|EGYPT|PERSIAN|FARSI|PL|POLAND|NF|NETFLIX|NL|DUTCH)\b/i';

        foreach ($allSeries as $s) {
            $name = $s['name'];

            if (strpos(mb_strtolower($name), $search) === false) {
                continue;
            }

            if (preg_match($regexExclude, $name)) {
                continue;
            }

            $rawResults[] = $s;
        }

        // Deduplication Logic (Same as Index)
        $groupedSeries = [];
        foreach ($rawResults as $serie) {
            if (!empty($serie['tmdb'])) {
                $key = 'tmdb_' . $serie['tmdb'];
            } else {
                $key = 'name_' . mb_strtolower($this->normalizeName($serie['name']));
            }

            if (!isset($groupedSeries[$key])) {
                $serie['display_name'] = $this->normalizeName($serie['name']);
                $groupedSeries[$key] = $serie;
            }
        }

        return array_values($groupedSeries);
    }
}
