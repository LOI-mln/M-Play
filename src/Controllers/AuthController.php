<?php
namespace App\Controllers;

use App\Models\Playlist;

class AuthController
{

    public function login()
    {
        require __DIR__ . '/../../views/login.php';
    }

    public function autoLogin($hote, $utilisateur, $motDePasse, $nomPlaylist = 'M-Play Desktop')
    {
        // On vérifie avec l'API Xtream Codes
        $urlApi = "$hote/player_api.php?username=$utilisateur&password=$motDePasse";

        // On supprime les erreurs PHP pour gérer ça nous-mêmes
        $reponse = @file_get_contents($urlApi);

        if ($reponse === false) {
            return false;
        }

        $donnees = json_decode($reponse, true);

        if (isset($donnees['user_info']['auth']) && $donnees['user_info']['auth'] == 1) {
            // C'est validé !
            if (session_status() === PHP_SESSION_NONE)
                session_start();

            $_SESSION['user'] = $donnees['user_info'];
            $_SESSION['server_info'] = $donnees['server_info'];
            $_SESSION['host'] = $hote;
            $_SESSION['playlist_name'] = $nomPlaylist;
            $_SESSION['auth_creds'] = [
                'username' => $utilisateur,
                'password' => $motDePasse
            ];

            // On sauvegarde en base de données
            $playlistModel = new Playlist();
            $playlistModel->creer($hote, $utilisateur, $motDePasse, $nomPlaylist);

            return true;
        } else {
            return false;
        }
    }

    public function authenticate()
    {
        $hote = rtrim($_POST['host'] ?? '', '/');
        $utilisateur = $_POST['username'] ?? '';
        $motDePasse = $_POST['password'] ?? '';
        $nomPlaylist = $_POST['playlist_name'] ?? 'Ma Playlist';

        if (!$hote || !$utilisateur || !$motDePasse) {
            $erreur = "Tous les champs sont requis.";
            require __DIR__ . '/../../views/login.php';
            return;
        }

        if ($this->autoLogin($hote, $utilisateur, $motDePasse, $nomPlaylist)) {
            header('Location: /');
            exit;
        } else {
            $erreur = "Identifiants invalides.";
            require __DIR__ . '/../../views/login.php';
        }
    }

    public function logout()
    {
        session_start();
        session_destroy();
        header('Location: /login');
        exit;
    }
}
