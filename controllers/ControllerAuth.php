<?php
// File: controllers/AuthController.php

use Firebase\JWT\JWT; // For JWT generation

class ControllerAuth extends Controller // Assuming you have a base Controller class
{
    private ModelLogin $modelLogin;
    private ModelSignup $modelSignup;
    private ViewLogin $viewLogin;
    private ViewRegister $viewRegister;
    private string $action;
    private array $params;

    public function __construct(string $action, array $params)
    {
        parent::__construct(); // Call parent constructor if it exists
        $this->modelLogin = new ModelLogin();
        $this->modelSignup = new ModelSignup();
        $this->viewLogin = new ViewLogin();
        $this->viewRegister = new ViewRegister();
        $this->action = $action;
        $this->params = $params;

        // Ensure JWT config is loaded. Adjust path if necessary.
        if (file_exists(__DIR__ . '/../config/jwt_config.php')) {
            require_once __DIR__ . '/../config/jwt_config.php';
        } else {
            die("JWT Configuration file not found.");
        }
        if (file_exists(__DIR__ . '/../vendor/autoload.php')) { // Path to Composer's autoloader
            require_once __DIR__ . '/../vendor/autoload.php';
        } else {
            die("Composer autoload not found.");
        }

        // Call the requested action
        $this->processAction();
    }

    private function processAction(): void
    {
        if ($this->action === 'showLoginForm') {
            $this->showLoginForm();
        } elseif ($this->action === 'showRegisterForm') {
            $this->showRegisterForm();
        } elseif ($this->action === 'login') {
            $this->login();
        } elseif ($this->action === 'register') {
            $this->register();
        } elseif ($this->action === 'logout') {
            $this->logout();
        }
    }

    public function showLoginForm(array $data = []): void
    {
        echo $this->viewLogin->render($data);
    }

    public function showRegisterForm(array $data = []): void
    {
        echo $this->viewRegister->render($data);
    }

    public function login(): void
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            $this->showLoginForm(['error_message' => 'Invalid request method.']);
            return;
        }

        $uid = $_POST["uid"] ?? '';
        $pwd = $_POST["pwd"] ?? '';

        if (empty($uid) || empty($pwd)) {
            $this->showLoginForm(['error_message' => "Please fill in all fields."]); // From LoginContr
            return;
        }

        $userDataOrError = $this->modelLogin->getUser($uid, $pwd);

        if (is_string($userDataOrError)) { // Error from ModelLogin
            $errorMessage = "Incorrect username or password."; // General message
            if ($userDataOrError === "stmtfailed") {
                // Log this server-side for debugging
                $errorMessage = "An unexpected error occurred. Please try again later.";
            }
            $this->showLoginForm(['error_message' => $errorMessage]);
            return;
        }

        // User authenticated, $userDataOrError is an array with user details
        // Generate JWT
        $userId = $userDataOrError['users_id'];
        $username = $userDataOrError['users_uid'];

        $issuedAt = time();
        $expirationTime = $issuedAt + JWT_EXPIRATION_TIME_SECONDS; // From jwt_config.php

        $payload = [
            'iss' => JWT_ISSUER,
            'aud' => JWT_AUDIENCE,
            'iat' => $issuedAt,
            'exp' => $expirationTime,
            'data' => ['userId' => $userId, 'username' => $username]
        ];

        try {
            $jwt = JWT::encode($payload, JWT_SECRET_KEY, JWT_ALGORITHM);
            $this->showLoginForm(['jwt_token' => $jwt]);
        } catch (Exception $e) {
            // Log $e->getMessage()
            $this->showLoginForm(['error_message' => 'Could not process login. Please try again.']);
        }
    }

    public function register(): void
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            $this->showRegisterForm(['error_message' => 'Invalid request method.']);
            return;
        }

        $uid = $_POST["uid"] ?? '';
        $pwd = $_POST["pwd"] ?? '';
        $pwdRepeat = $_POST["pwdrepeat"] ?? '';
        $email = $_POST["email"] ?? '';

        // Validations from SignupContr
        if (empty($uid) || empty($pwd) || empty($pwdRepeat) || empty($email)) {
            $this->showRegisterForm(['error_message' => "Please input all the fields."]); //
            return;
        }
        if (!preg_match("/^[a-zA-Z0-9]*$/", $uid)) {
            $this->showRegisterForm(['error_message' => "Username is invalid. Only alphanumeric characters allowed."]); //
            return;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->showRegisterForm(['error_message' => "Email address is invalid."]); //
            return;
        }
        if ($pwd !== $pwdRepeat) {
            $this->showRegisterForm(['error_message' => "Passwords do not match."]); //
            return;
        }
        if (!$this->modelSignup->checkUser($uid, $email)) { // uidTakenCheck
            $this->showRegisterForm(['error_message' => "Username or email already taken."]); //
            return;
        }

        // Attempt to create user
        if ($this->modelSignup->setUser($uid, $pwd, $email)) {
            // Optionally, log the user in directly by generating a JWT here
            // Or redirect to login page with a success message
            $this->showRegisterForm(['success_message' => "Signup successful! You can now login."]);
        } else {
            $this->showRegisterForm(['error_message' => "An error occurred during registration. Please try again."]);
        }
    }

    public function logout(): void
    {
        // For JWT, logout is primarily client-side (removing the token).
        // Server-side, you might want to redirect.
        // The original logout.inc.php just redirected.
        // Consider if you need to add the token to a blacklist (more advanced).
        header("Location: index.php?action=showLogin&status=loggedout"); // Redirect to login page
        exit();
    }
}