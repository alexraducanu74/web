<?php
session_start();
require_once 'autoload.php';

function sanitizeKey($value)
{
    // Allow only letters and numbers
    return preg_replace('/[^a-zA-Z0-9]/', '', $value);
}

// Sanitize and validate inputs
$controller = isset($_GET['controller']) ? sanitizeKey($_GET['controller']) : 'feed';
$actiune = isset($_GET['actiune']) ? sanitizeKey($_GET['actiune']) : 'showFeed';
$parametrii = isset($_GET['parametrii']) ? $_GET['parametrii'] : '';

// Process parameters
$params = array_filter(array_map('trim', explode(',', $parametrii)));

// Construct controller class name
$controllerClass = 'Controller' . ucfirst(strtolower($controller));

if (class_exists($controllerClass)) {
    $ctrl = new $controllerClass($actiune, $params);
} else {
    echo "Controller-ul '$controllerClass' nu există.";
}