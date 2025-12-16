<?php
namespace App\Controllers;

class LiveController
{

    public function index()
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit;
        }

        $hote = $_SESSION['host']; // Pas de slash à la fin
        $utilisateur = $_SESSION['auth_creds']['username'];
        $motDePasse = $_SESSION['auth_creds']['password'];

        // 1. On récupère les catégories
        $urlCategories = "$hote/player_api.php?username=$utilisateur&password=$motDePasse&action=get_live_categories";
        $donneesCategories = @file_get_contents($urlCategories);
        $categories = json_decode($donneesCategories, true) ?? [];

        // 2. Filtrage strict selon la liste demandée
        $categoriesAutorisees = [
            'FR CANAL+ LIVE | TV',
            'FR CINEMA | TV',
            'FR DAZN PPV | TV',
            'FR DOCUMENTAIRE | TV',
            'FR INFOS | TV',
            'FR KIDS | TV',
            'FR L\'EQUIPE LIVE | TV',
            'FR LIGUE 1+ VIP | TV',
            'FR LIGUE1 + | TV',
            'FR MUSIQUE | TV',
            'FR SPORTS | TV',
            'FRANCE FHD | TV',
            'FRANCE RADIO'
        ];

        $categories = array_filter($categories, function ($cat) use ($categoriesAutorisees) {
            $nom = strtoupper(trim($cat['category_name']));
            // On vérifie si le nom exact est dans la liste (ou contient le nom pour être plus souple)
            // Étant donné que les noms IPTV varient, on va être un peu souple:
            // Si le nom de la catégorie contient un des noms autorisés
            foreach ($categoriesAutorisees as $autorise) {
                if (strpos($nom, strtoupper($autorise)) !== false) {
                    return true;
                }
            }
            return false;
        });

        // Nettoyage des noms (On enlève le "FR " devant)
        $categories = array_map(function ($cat) {
            $nom = $cat['category_name'];

            // Exception pour FRANCE FHD
            if (strpos($nom, 'FRANCE FHD') !== false) {
                $nom = str_replace(' | TV', '', $nom);
                $cat['category_name'] = trim($nom);
                return $cat;
            }

            // On enlève "FR " ou "FRANCE " au début, et aussi " | TV" à la fin pour faire propre
            $nom = preg_replace('/^(FR\s+|FRANCE\s+)/', '', $nom);
            $nom = str_replace(' | TV', '', $nom);
            $cat['category_name'] = trim($nom);
            return $cat;
        }, $categories);

        // On trie par nom propre
        usort($categories, function ($a, $b) {
            // FRANCE FHD toujours en premier
            if ($a['category_name'] === 'FRANCE FHD')
                return -1;
            if ($b['category_name'] === 'FRANCE FHD')
                return 1;

            return strcmp($a['category_name'], $b['category_name']);
        });

        // Recherche & Catégorie
        $idCategorieSelectionnee = $_GET['categorie'] ?? null;

        $tousLesFlux = [];

        // Si aucune catégorie sélectionnée, on prend la première de la liste
        if (!$idCategorieSelectionnee && !empty($categories)) {
            // On cherche la première catégorie (la sorting a mis FRANCE FHD en premier normalement)
            $premiereCat = reset($categories);
            $idCategorieSelectionnee = $premiereCat['category_id'];
        }

        // Récupération des flux de la catégorie
        if ($idCategorieSelectionnee) {
            $urlFlux = "$hote/player_api.php?username=$utilisateur&password=$motDePasse&action=get_live_streams&category_id=$idCategorieSelectionnee";
            $donneesFlux = @file_get_contents($urlFlux);
            $tousLesFlux = json_decode($donneesFlux, true) ?? [];
        }

        // On passe tout ça à la vue
        $flux = $tousLesFlux;
        $categorieActuelleId = $idCategorieSelectionnee;

        require __DIR__ . '/../../views/live.php';
    }

    public function regarder()
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit;
        }

        $idFlux = $_GET['stram'] ?? null; // 'stream' faute de frappe possible dans l'URL, on check
        $idFlux = $_GET['id'] ?? null;

        if (!$idFlux) {
            header('Location: /live');
            exit;
        }

        // On récupère les infos du stream pour le titre (optionnel mais sympa)
        // Pour l'instant on fait simple, on passe juste l'ID et on construit l'URL

        $hote = $_SESSION['host'];
        $utilisateur = $_SESSION['auth_creds']['username'];
        $motDePasse = $_SESSION['auth_creds']['password'];

        // URL du flux en m3u8 pour HLS.js (souvent mieux supporté en web)
        $urlFluxM3u8 = "$hote/live/$utilisateur/$motDePasse/$idFlux.m3u8";

        require __DIR__ . '/../../views/watch.php';
    }
}
