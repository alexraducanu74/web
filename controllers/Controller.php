<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;

abstract class Controller
{
    protected $model;
    protected $view;

    public function __construct()
    {
        $className = get_class($this);
        if ($className !== 'ControllerAuth') {
            $numeModel = str_replace("Controller", "Model", $className);
            if (class_exists($numeModel)) {
                $this->model = new $numeModel;
            }

            $numeView = str_replace("Controller", "View", $className);
            if (class_exists($numeView)) {
                $this->view = new $numeView;
            }
        }
    }

    protected function getAuthenticatedUser(): ?array
    {
        if (!isset($_COOKIE['jwt_auth'])) {
            return null;
        }

        $token = $_COOKIE['jwt_auth'];

        try {
            $decodedPayload = JWT::decode($token, new Key(JWT_SECRET_KEY, JWT_ALGORITHM));

            if (isset($decodedPayload->data) && isset($decodedPayload->data->userId)) {
                return [
                    'user_id' => $decodedPayload->data->userId,
                    'username' => $decodedPayload->data->username,
                    'is_admin' => $decodedPayload->data->is_admin ?? false,
                ];
            }

            return null;

        } catch (ExpiredException $e) {
            return null;
        } catch (SignatureInvalidException $e) {
            return null;
        } catch (Exception $e) {
            return null;
        }
    }
}