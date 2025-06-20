document.addEventListener('DOMContentLoaded', function () {
    const loginForm = document.querySelector('form[action*="actiune=login"]');
    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault(); // Opreste trimiterea standard a formularului

            const formData = new FormData(loginForm);

            try {
                const response = await fetch('index.php?controller=auth&actiune=login', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (response.ok && result.success && result.jwt_token) {
                    sessionStorage.setItem('jwtToken', result.jwt_token);

                    window.location.href = 'index.php?controller=feed&actiune=showFeed';
                } else {
                    const errorElement = document.querySelector('.index-login-login .error-message');
                    if (errorElement) {
                        errorElement.textContent = result.error || 'Login a esuat!';
                        errorElement.style.display = 'block';
                    } else {
                        alert(result.error || 'Login a esuat!');
                    }
                }
            } catch (error) {
                console.error('Network Error:', error);
                alert('A network error occurred during login.');
            }
        });
    }

    const loginWrapper = document.querySelector('.index-login-login');
    if (loginWrapper && !loginWrapper.querySelector('.error-message')) {
        const p = document.createElement('p');
        p.className = 'error-message';
        p.style.color = 'red';
        p.style.display = 'none';
        loginWrapper.insertBefore(p, loginWrapper.querySelector('form'));
    }
});