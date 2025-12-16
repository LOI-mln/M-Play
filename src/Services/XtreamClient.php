<?php

namespace App\Services;

class XtreamClient
{
    private $host;
    private $username;
    private $password;

    public function __construct($host, $username, $password)
    {
        $this->host = rtrim($host, '/');
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * Effectue une requête GET vers l'API Xtream Codes
     * 
     * @param string $endpoint (ex: player_api.php)
     * @param array $params (ex: ['action' => 'get_series'])
     * @return array|null
     */
    public function get($endpoint, $params = [])
    {
        // On ajoute toujours les identifiants
        $defaultParams = [
            'username' => $this->username,
            'password' => $this->password
        ];

        $queryParams = array_merge($defaultParams, $params);
        $queryString = http_build_query($queryParams);

        $url = "{$this->host}/{$endpoint}?{$queryString}";

        // Augmentation timeout et suppression erreur warning
        $opts = [
            "http" => [
                "timeout" => 15, // Plus long pour les grosses listes
                "ignore_errors" => true
            ]
        ];
        $context = stream_context_create($opts);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            return null;
        }

        return json_decode($response, true);
    }
}
