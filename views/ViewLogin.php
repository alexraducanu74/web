<?php
class ViewLogin
{
    private function getLoginForm(): string
    {
        $formHtml = '<section class="index-login">
            <div class="wrapper">
                <div class="index-login-login">
                    <h4>LOGIN</h4>
                    <p>Already have an account? Login here!</p>';

        $formHtml .= '<form action="index.php?controller=auth&actiune=login" method="post">
                        <div><input type="text" name="uid" placeholder="Username or Email" required></div>
                        <div><input type="password" name="pwd" placeholder="Password" required></div>
                        <br>
                        <button type="submit" name="submit">LOGIN</button>
                    </form>
                </div>
            </div>
        </section>';

        $formHtml .= '<script src="assets/js/auth.js" defer></script>';

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
        $content = $this->getLoginForm();
        $authLinks = $this->getGuestAuthLinks();

        $layout = $this->loadTemplate('layout.tpl', [
            'title' => 'Login',
            'content' => $content . '<script src="assets/js/nav.js" defer></script>',
            'authLinks' => $authLinks
        ]);

        echo $layout;
    }
}
?>