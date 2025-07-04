<?php
require_once 'vendor/autoload.php';
require_once 'config/jwt_config.php';

function sanitizeKey($value)
{
    return preg_replace('/[^a-zA-Z0-9]/', '', $value);
}

$controller = isset($_GET['controller']) ? sanitizeKey($_GET['controller']) : 'feed';
$actiune = isset($_GET['actiune']) ? sanitizeKey($_GET['actiune']) : 'showFeed';
$parametri = isset($_GET['parametri']) ? $_GET['parametri'] : '';
$params = array_filter(array_map('trim', explode(',', $parametri)));

$isApi = isset($_GET['api']) && $_GET['api'] == '1';

if ($isApi) {
    // Make an exception for the public RSS feed, which does not need authentication.
    if ($actiune === 'genereazaRssApi') {
        new ControllerApiFeed($actiune, $params, []); // Call controller without user data.
        exit;
    }

    // All other API calls require JWT authentication.
    require_once 'auth/auth_middleware.php';

    $userData = verify_jwt_and_get_payload();

    new ControllerApiFeed($actiune, $params, $userData);
    exit;
} else {
    $controllerClass = 'Controller' . ucfirst(strtolower($controller));
    if (class_exists($controllerClass)) {
        $ctrl = new $controllerClass($actiune, $params);
    } else {
        echo "Controller-ul '$controllerClass' nu exista.";
    }
}