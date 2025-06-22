<?php
class ViewRegister
{
    private function getRegisterForm(): string
    {
        $formHtml = '<section class="index-login">
            <div class="wrapper">
                <div class="index-login-signup">
                    <h4>SIGN UP</h4>
                    <p>Don\'t have an account yet? Sign up here!</p>';

        $formHtml .= '<form action="index.php?controller=auth&actiune=register" method="post">
                        <div><input type="text" name="uid" placeholder="Username" required></div>
                        <div><input type="password" name="pwd" placeholder="Password" required></div>
                        <div><input type="password" name="pwdrepeat" placeholder="Repeat Password" required></div>
                        <div><input type="text" name="email" placeholder="E-mail" required></div>
                        <br>
                        <button type="submit" name="submit">SIGN UP</button>
                    </form>
                </div>
            </div>
        </section>';

        return $formHtml;
    }

    private function getGuestAuthLinks(): string
    {
        return '
            <a href="index.php?controller=auth&actiune=showLoginForm">Login</a>
            <a href="index.php?controller=auth&actiune=showRegisterForm">Register</a>';
    }

    private function loadTemplate(string $filePath, array $data): string
    {
        $actualFilePath = __DIR__ . '/' . basename($filePath);
        if (!file_exists($actualFilePath)) {
            return "Error: Template file not found at {$actualFilePath}";
        }

        $template = file_get_contents($actualFilePath);
        foreach ($data as $key => $value) {
            $template = str_replace('{$' . $key . '}', (string) $value, $template);
        }
        return $template;
    }

    public function render(): void
    {
        $content = $this->getRegisterForm();
        $authLinks = $this->getGuestAuthLinks();

        $scripts = '<script src="assets/js/nav.js" defer></script>' .
            '<script src="assets/js/auth.js" defer></script>';

        $layout = $this->loadTemplate('layout.tpl', [
            'title' => 'Sign Up',
            'content' => $content . $scripts,
            'authLinks' => $authLinks
        ]);

        echo $layout;
    }
}
?>