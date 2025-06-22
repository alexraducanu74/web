<?php
use Firebase\JWT\JWT;
class ControllerAuth extends Controller
{
    private ModelLogin $modelLogin;
    private ModelRegister $modelRegister;
    private ViewLogin $viewLogin;
    private ViewRegister $viewRegister;
    private string $action;
    private array $params;
    public function __construct(string $action, array $params)
    {
        parent::__construct();
        $this->modelLogin = new ModelLogin();
        $this->modelRegister = new ModelRegister();
        $this->viewLogin = new ViewLogin();
        $this->viewRegister = new ViewRegister();
        $this->action = $action;
        $this->params = $params;
        if (file_exists(__DIR__ . '/../config/jwt_config.php')) {
            require_once __DIR__ . '/../config/jwt_config.php';
        } else {
            die("JWT Configuration file not found.");
        }
        if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
            require_once __DIR__ . '/../vendor/autoload.php';
        } else {
            die("Composer autoload not found.");
        }
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
        echo $this->viewLogin->render();
    }
    public function showRegisterForm(array $data = []): void
    {
        echo $this->viewRegister->render();
    }
    public function login(): void
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
            return;
        }
        $uid = $_POST["uid"] ?? '';
        $pwd = $_POST["pwd"] ?? '';
        if (empty($uid) || empty($pwd)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Please fill in all fields.']);
            return;
        }
        $userDataOrError = $this->modelLogin->getUser($uid, $pwd);
        if (is_string($userDataOrError)) {
            $errorMessage = "Incorrect username or password.";
            if ($userDataOrError === "stmtfailed") {
                $errorMessage = "An unexpected error occurred. Please try again later.";
            }
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => $errorMessage]);
            return;
        }
        $userId = $userDataOrError['users_id'];
        $username = $userDataOrError['users_uid'];
        $isAdmin = $userDataOrError['is_admin'];

        $issuedAt = time();
        $expirationTime = $issuedAt + JWT_EXPIRATION_TIME_SECONDS;

        $payload = [
            'iss' => JWT_ISSUER,
            'aud' => JWT_AUDIENCE,
            'iat' => $issuedAt,
            'exp' => $expirationTime,
            'data' => [
                'userId' => $userId,
                'username' => $username,
                'is_admin' => $isAdmin
            ]
        ];
        try {
            $jwt = JWT::encode($payload, JWT_SECRET_KEY, JWT_ALGORITHM);

            setcookie('jwt_auth', $jwt, [
                'expires' => $expirationTime,
                'path' => '/',
                'secure' => isset($_SERVER['HTTPS']),
                'httponly' => true, // Prevent JavaScript access
                'samesite' => 'Lax'
            ]);

            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'jwt_token' => $jwt]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Could not process login. Please try again.']);
        }
    }
    public function register(): void
    {
        header('Content-Type: application/json');
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
            return;
        }

        $uid = $_POST["uid"] ?? '';
        $pwd = $_POST["pwd"] ?? '';
        $pwdRepeat = $_POST["pwdrepeat"] ?? '';
        $email = $_POST["email"] ?? '';

        if (empty($uid) || empty($pwd) || empty($pwdRepeat) || empty($email)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Please fill in all fields.']);
            return;
        }
        if (!preg_match("/^[a-zA-Z0-9]*$/", $uid)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Username is invalid. Only alphanumeric characters allowed.']);
            return;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Email address is invalid.']);
            return;
        }
        if ($pwd !== $pwdRepeat) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Passwords do not match.']);
            return;
        }
        if (!$this->modelRegister->checkUser($uid, $email)) {
            http_response_code(409); // 409 Conflict
            echo json_encode(['success' => false, 'error' => 'Username or email already taken.']);
            return;
        }

        try {
            if ($this->modelRegister->setUser($uid, $pwd, $email)) {
                echo json_encode(['success' => true, 'message' => 'Registration successful! You can now log in.']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'An error occurred during registration. Please try again.']);
            }
        } catch (PDOException $e) {
            $errorMsg = $e->getMessage();
            $message = "Database error during registration.";
            if (strpos($errorMsg, 'Numele de utilizator este prea scurt') !== false) {
                $message = "Username must be at least 3 characters long.";
            }
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $message]);
        }
    }
    public function logout(): void
    {
        setcookie('jwt_auth', '', [
            'expires' => time() - 3600,
            'path' => '/',
        ]);
        echo "<script>sessionStorage.removeItem('jwtToken'); window.location.href = 'index.php';</script>";
        exit();
    }
}