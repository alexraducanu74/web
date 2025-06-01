<?php
// File: auth/auth_middleware.php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/jwt_config.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Firebase\JWT\BeforeValidException;

/**
 * Verifies the JWT token from the Authorization header.
 * If valid, returns the decoded payload data.
 * If invalid or not present, it sends an HTTP 401 response and exits.
 */
function verify_jwt_and_get_payload()
{
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null;

    if (!$authHeader) {
        http_response_code(401); // Unauthorized
        echo json_encode(["status" => "error", "message" => "Authorization header not found."]);
        exit;
    }

    // The header is typically "Bearer <token>"
    $arr = explode(" ", $authHeader);
    if (count($arr) !== 2 || $arr[0] !== 'Bearer') {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Malformed token."]);
        exit;
    }
    $token = $arr[1];

    if (!$token) {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Token not found in Authorization header."]);
        exit;
    }

    try {
        $decoded = JWT::decode($token, new Key(JWT_SECRET_KEY, JWT_ALGORITHM));
        return (array) $decoded->data; // Return user data from token's 'data' claim
    } catch (ExpiredException $e) {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Token has expired."]);
        exit;
    } catch (SignatureInvalidException $e) {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Token signature verification failed."]);
        exit;
    } catch (BeforeValidException $e) {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Token is not yet valid."]);
        exit;
    } catch (Exception $e) { // Other general JWT errors
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Invalid token: " . $e->getMessage()]);
        exit;
    }
}


?>