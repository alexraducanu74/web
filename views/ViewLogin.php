<?php
class ViewLogin
{
    public function render(array $data = []): string
    {
        $error_message = $data['error_message'] ?? '';
        $jwt_token = $data['jwt_token'] ?? null;
        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="en">

        <head>
            <meta charset="UTF-8">
            <title>Login</title>
            <link rel="stylesheet" href="assets/style.css">
        </head>

        <body>
            <section class="index-login">
                <div class="wrapper">
                    <div class="index-login-login">
                        <h4>LOGIN</h4>
                        <p>Already have an account? Login here!</p>
                        <?php if (!empty($error_message)): ?>
                            <p style='color:red;'><?php echo htmlspecialchars($error_message); ?></p>
                        <?php endif; ?>
                        <form action="index.php?controller=auth&actiune=login" method="post">
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
                    // Pass the JWT token to a global JavaScript variable
                    var jwtTokenToStore = <?php echo json_encode($jwt_token); ?>;
                </script>
            <?php endif; ?>

            <script src="assets/js/auth.js" defer></script>
        </body>

        </html>
        <?php
        return ob_get_clean();
    }
}
?>