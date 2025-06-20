<?php
session_start();
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

$publicApiActions = ['genereazaRssApi'];

if ($isApi) {
    if (in_array($actiune, $publicApiActions)) {
        new ControllerApiFeed($actiune, $params, []); 
        exit;
    } else {
        require_once 'auth/auth_middleware.php';
        $userData = verify_jwt_and_get_payload();
        new ControllerApiFeed($actiune, $params, $userData);
        exit;
    }
} else {
    $controllerClass = 'Controller' . ucfirst(strtolower($controller));
    if (class_exists($controllerClass)) {
        $ctrl = new $controllerClass($actiune, $params);
    } else {
        echo "Controller-ul '$controllerClass' nu exista.";
    }
}