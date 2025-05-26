<?php
// File: views/ViewRegister.php

class ViewRegister
{
    public function render(array $data = []): string
    {
        $error_message = $data['error_message'] ?? '';
        $success_message = $data['success_message'] ?? '';
        // $baseURL = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');

        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="en">

        <head>
            <meta charset="UTF-8">
            <title>Sign Up</title>
            <link rel="stylesheet" href="/assets/web/style.css">
        </head>

        <body>
            <?php
            if (file_exists(__DIR__ . '/../../nav/navbar.php')) {
                include __DIR__ . '/../../nav/navbar.php';
            } else if (file_exists('../nav/navbar.php')) {
                include '../nav/navbar.php';
            }
            ?>
            <section class="index-login">
                <div class="wrapper">
                    <div class="index-login-signup">
                        <h4>SIGN UP</h4>
                        <p>Don't have an account yet? Sign up here!</p>

                        <?php if (!empty($error_message)): ?>
                            <p style='color:red;'><?php echo htmlspecialchars($error_message); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($success_message)): ?>
                            <p style='color:green;'><?php echo htmlspecialchars($success_message); ?></p>
                        <?php endif; ?>

                        <form action="index.php?action=register" method="post">
                            <div><input type="text" name="uid" placeholder="Username" required></div>
                            <div><input type="password" name="pwd" placeholder="Password" required></div>
                            <div><input type="password" name="pwdrepeat" placeholder="Repeat Password" required></div>
                            <div><input type="text" name="email" placeholder="E-mail" required></div>
                            <br>
                            <button type="submit" name="submit">SIGN UP</button>
                        </form>
                    </div>
                </div>
            </section>
        </body>

        </html>
        <?php
        return ob_get_clean();
    }
}
?>