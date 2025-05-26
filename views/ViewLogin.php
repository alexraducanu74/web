<?php
// File: views/ViewLogin.php

class ViewLogin
{
    public function render(array $data = []): string
    {
        $error_message = $data['error_message'] ?? '';
        $jwt_token = $data['jwt_token'] ?? null;
        // Assuming BASE_URL is defined in your front controller (index.php)
        // For direct use, ensure correct paths or define BASE_URL here.
        // Example: define('BASE_URL', ''); // If running directly in subfolder.
        // Or calculate it: $baseURL = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');

        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="en">

        <head>
            <meta charset="UTF-8">
            <title>Login</title>
            <link rel="stylesheet" href="/assets/web/style.css">
        </head>

        <body>
            <?php
            // Path to navbar.php assumes 'nav' is a sibling directory to 'views' parent.
            // If index.php is in root, and nav is in root, this path from views/ViewLogin.php is:
            // include __DIR__ . '/../../nav/navbar.php';
            // For simplicity, using the original relative path, assuming include paths are set up
            // or you adjust it based on your front controller's execution directory.
            if (file_exists(__DIR__ . '/../../nav/navbar.php')) {
                include __DIR__ . '/../../nav/navbar.php';
            } else if (file_exists('../nav/navbar.php')) { // Original path relative to a root file
                include '../nav/navbar.php';
            }
            ?>
            <section class="index-login">
                <div class="wrapper">
                    <div class="index-login-login">
                        <h4>LOGIN</h4>
                        <p>Already have an account? Login here!</p>

                        <?php if (!empty($error_message)): ?>
                            <p style='color:red;'><?php echo htmlspecialchars($error_message); ?></p>
                        <?php endif; ?>

                        <form action="index.php?action=login" method="post">
                            <div><input type="text" name="uid" placeholder="Username or Email" required></div>
                            <div><input type="password" name="pwd" placeholder="Password" required></div>
                            <br>
                            <button type="submit" name="submit">LOGIN</button>
                        </form>
                    </div>
                </div>
            </section>

            <?php if ($jwt_token): ?>
                <script>
                    const token = <?php echo json_encode($jwt_token); ?>;
                    if (token) {
                        localStorage.setItem('jwtToken', token);
                        alert('Login successful! Token stored. Redirecting...');
                        // Redirect to a protected page (e.g., dashboard)
                        // Adjust the redirection URL as needed
                        window.location.href = 'index.php?action=dashboard'; // Example dashboard route
                    }
                </script>
            <?php endif; ?>

        </body>

        </html>
        <?php
        return ob_get_clean();
    }
}
?>