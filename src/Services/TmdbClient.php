<?php

namespace App\Services;

class TmdbClient
{
    private $apiKey;
    private $baseUrl = 'https://api.themoviedb.org/3';
    private $cache;

    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
        $this->cache = new FileCache();
    }

    private function get($endpoint, $params = [])
    {
        $params['api_key'] = $this->apiKey;
        $params['language'] = 'fr-FR'; // On demande les infos en FR si possible pour le matching de nom
        $url = $this->baseUrl . $endpoint . '?' . http_build_query($params);

        $cacheKey = 'tmdb_' . md5($url);
        $cached = $this->cache->get($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        $opts = [
            "http" => [
                "timeout" => 5,
                "ignore_errors" => true
            ]
        ];
        $context = stream_context_create($opts);
        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            return null;
        }

        $data = json_decode($response, true);

        // Cache pour 12h (trends change daily/weekly)
        if ($data) {
            $this->cache->set($cacheKey, $data, 43200);
        }

        return $data;
    }

    public function getTrendingMovies($window = 'week')
    {
        // window: day, week
        $data = $this->get("/trending/movie/$window");
        return $data['results'] ?? [];
    }

    public function getTrendingSeries($window = 'week')
    {
        $data = $this->get("/trending/tv/$window");
        return $data['results'] ?? [];
    }
}
