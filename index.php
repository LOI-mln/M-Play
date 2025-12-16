<?php
// Autoloader (manual for now, can be replaced by Composer later if needed)
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/src/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0)
        return;
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file))
        require $file;
});

// Routeur Basique
session_start();

$url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// On sert les fichiers statiques si besoin
if (file_exists(__DIR__ . '/public' . $url) && $url !== '/') {
    return false;
}

// Les routes accessibles sans être connecté
$routesPubliques = ['/login', '/auth/verify'];

if (!isset($_SESSION['user']) && !in_array($url, $routesPubliques)) {
    header('Location: /login');
    exit;
}

// Le switch du routeur
switch ($url) {
    case '/':
        require __DIR__ . '/views/home.php';
        break;
    case '/login':
        (new App\Controllers\AuthController())->login();
        break;
    case '/auth/verify':
        (new App\Controllers\AuthController())->authenticate();
        break;
    case '/live':
        (new App\Controllers\LiveController())->index();
        break;
    case '/movies':
        (new App\Controllers\MoviesController())->index();
        break;
    case '/movies/watch':
        (new App\Controllers\MoviesController())->watch();
        break;
    case '/stream/transcode':
        (new App\Controllers\ProxyController())->transcode();
        break;
    case '/live/watch':
        (new App\Controllers\LiveController())->regarder();
        break;
    case '/series':
        (new App\Controllers\SeriesController())->index();
        break;
    case '/series/details':
        (new App\Controllers\SeriesController())->details();
        break;
    case '/series/watch':
        (new App\Controllers\SeriesController())->watch();
        break;
    case '/logout':
        (new App\Controllers\AuthController())->logout();
        break;
    default:
        http_response_code(404);
        echo "404 Not Found";
        break;
}

