<?php
namespace App\Controllers;

use App\Services\FileCache;

class SearchController
{
    public function index()
    {
        $query = $_GET['q'] ?? '';

        if (empty($query)) {
            // Si pas de recherche, on redirige vers l'accueil ou on affiche vide
            header('Location: /');
            exit;
        }

        // On instancie les contrôleurs pour récupérer leurs données
        $moviesController = new MoviesController();
        $seriesController = new SeriesController();

        // On va tricher un peu pour réutiliser la logique de filtrage existante 
        // en simulant une recherche globale sur les méthode index/search si elles existent.
        // Mais index() fait un require view, ce qui ne va pas.
        // Il faut qu'on expose une méthode publique 'search($query)' ou 'getAll($filter)' dans les controlleurs.

        // Pour l'instant, faisons le manuellement ici en utilisant le cache public s'il existe, 
        // ou en appelant une methode helper qu'on va ajouter.

        // Approche propre : Ajouter une méthode `searchInternal($query)` dans Movies et Series Controller

        $results = [
            'movies' => $moviesController->searchInternal($query),
            'series' => $seriesController->searchInternal($query)
        ];

        // Vue
        $searchQuery = $query;
        $title = "Recherche : " . htmlspecialchars($query) . " - M-Play";
        $modePleinEcran = true;

        require __DIR__ . '/../../views/search.php';
    }
}
