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
$bookId = (int) ($params[0] ?? 0);

$isApi = isset($_GET['api']) && $_GET['api'] == '1';

if ($isApi) {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    header('Content-Type: application/json');

    $api = new ControllerApiFeed();

    switch ($actiune) {
        case 'insertBookApi':
            $api->insertBookApi();
            break;

        case 'deleteBookApi':
            $api->deleteBookApi($bookId);
            break;

        case 'updateBookApi':
            $api->updateBookApi($bookId);
            break;

        case 'genereazaRssApi':
            $api->genereazaRssApi();
            break;

        default:
            http_response_code(404);
            echo json_encode(['error' => 'Unknown API action.']);
            break;
    }
    exit;

} else {
    $controllerClass = 'Controller' . ucfirst(strtolower($controller));
    if (class_exists($controllerClass)) {
        $ctrl = new $controllerClass($actiune, $params);
    } else {
        echo "Controller-ul '$controllerClass' nu exista.";
    }
}