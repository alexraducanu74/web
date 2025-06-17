<?php
use Firebase\JWT\JWT;
class ControllerAuth extends Controller
{
    private ModelLogin $modelLogin;
    private ModelSignup $modelSignup;
    private ViewLogin $viewLogin;
    private ViewRegister $viewRegister;
    private string $action;
    private array $params;
    public function __construct(string $action, array $params)
    {
        parent::__construct();
        $this->modelLogin = new ModelLogin();
        $this->modelSignup = new ModelSignup();
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
            $this->showLoginForm(['error_message' => "Please fill in all fields."]);
            return;
        }
        $userDataOrError = $this->modelLogin->getUser($uid, $pwd);
        if (is_string($userDataOrError)) {
            $errorMessage = "Incorrect username or password.";
            if ($userDataOrError === "stmtfailed") {
                $errorMessage = "An unexpected error occurred. Please try again later.";
            }
            $this->showLoginForm(['error_message' => $errorMessage]);
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
            $_SESSION['jwt'] = $jwt;
            $_SESSION['user_id'] = $userId;
            $_SESSION['username'] = $username;
            $_SESSION['is_admin'] = $isAdmin;
            $this->showLoginForm(['jwt_token' => $jwt]);
        } catch (Exception $e) {
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
        if (empty($uid) || empty($pwd) || empty($pwdRepeat) || empty($email)) {
            $this->showRegisterForm(['error_message' => "Please input all the fields."]);
            return;
        }
        if (!preg_match("/^[a-zA-Z0-9]*$/", $uid)) {
            $this->showRegisterForm(['error_message' => "Username is invalid. Only alphanumeric characters allowed."]);
            return;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->showRegisterForm(['error_message' => "Email address is invalid."]);
            return;
        }
        if ($pwd !== $pwdRepeat) {
            $this->showRegisterForm(['error_message' => "Passwords do not match."]);
            return;
        }
        if (!$this->modelSignup->checkUser($uid, $email)) {
            $this->showRegisterForm(['error_message' => "Username or email already taken."]);
            return;
        }
        try {
            if ($this->modelSignup->setUser($uid, $pwd, $email)) {
                header("Location: index.php");
                exit();
            } else {
                $this->showRegisterForm(['error_message' => "An error occurred during registration. Please try again."]);
            }
        } catch (PDOException $e) {
            $errorMsg = $e->getMessage();

            if (strpos($errorMsg, 'Numele de utilizator este prea scurt') !== false) {
                $message = "Numele de utilizator trebuie sa aiba cel putin 3 caractere.";
            } else {
                $message = "Eroare la inregistrare. incearca din nou.";
            }

            $this->showRegisterForm(['error_message' => $message]);
        }
    }
    public function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION = array();

        session_destroy();

        header("Location: index.php");
        exit();
    }
}