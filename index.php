<?php
session_start();
require_once 'autoload.php';

function sanitizeKey($value)
{
    // Allow only letters and numbers
    return preg_replace('/[^a-zA-Z0-9]/', '', $value);
}

$controller = isset($_GET['controller']) ? sanitizeKey($_GET['controller']) : 'feed';
$actiune = isset($_GET['actiune']) ? sanitizeKey($_GET['actiune']) : 'showFeed';
$parametrii = isset($_GET['parametrii']) ? $_GET['parametrii'] : '';
$params = array_filter(array_map('trim', explode(',', $parametrii)));
$bookId = (int) ($params[0] ?? 0);

// Check if this is an API request
$isApi = isset($_GET['api']) && $_GET['api'] == '1';

if ($isApi) {
    // Handle API requests here
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

        default:
            http_response_code(404);
            echo json_encode(['error' => 'Unknown API action.']);
            break;
    }
    exit; // Important: exit after API handling to prevent HTML output

} else {
    // Normal web requests handled here
    $controllerClass = 'Controller' . ucfirst(strtolower($controller));
    if (class_exists($controllerClass)) {
        $ctrl = new $controllerClass($actiune, $params);
    } else {
        echo "Controller-ul '$controllerClass' nu există.";
    }
}
