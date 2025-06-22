document.addEventListener('DOMContentLoaded', function () {
    const loginForm = document.querySelector('form[action*="actiune=login"]');
    if (loginForm) {
        const loginWrapper = document.querySelector('.index-login-login');
        if (loginWrapper && !loginWrapper.querySelector('.error-message')) {
            const p = document.createElement('p');
            p.className = 'error-message';
            p.style.color = 'red';
            p.style.display = 'none';
            loginWrapper.insertBefore(p, loginWrapper.querySelector('form'));
        }

        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(loginForm);
            const errorElement = loginWrapper.querySelector('.error-message');

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
                    if (errorElement) {
                        errorElement.textContent = result.error || 'Login failed!';
                        errorElement.style.display = 'block';
                    } else {
                        alert(result.error || 'Login failed!');
                    }
                }
            } catch (error) {
                console.error('Network Error:', error);
                if (errorElement) {
                    errorElement.textContent = 'A network error occurred during login.';
                    errorElement.style.display = 'block';
                } else {
                    alert('A network error occurred during login.');
                }
            }
        });
    }

    const registerForm = document.querySelector('form[action*="actiune=register"]');
    if (registerForm) {
        const registerWrapper = document.querySelector('.index-login-signup');

        if (registerWrapper && !registerWrapper.querySelector('.message-area')) {
            const p = document.createElement('p');
            p.className = 'message-area';
            p.style.display = 'none';
            registerWrapper.insertBefore(p, registerWrapper.querySelector('form'));
        }

        registerForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(registerForm);
            const messageElement = registerWrapper.querySelector('.message-area');
            messageElement.style.display = 'none';

            try {
                const response = await fetch('index.php?controller=auth&actiune=register', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (response.ok) {
                    messageElement.textContent = result.message || 'Success!';
                    messageElement.style.color = 'green';
                    messageElement.style.display = 'block';
                    registerForm.reset();
                } else {
                    messageElement.textContent = result.error || 'Registration failed!';
                    messageElement.style.color = 'red';
                    messageElement.style.display = 'block';
                }
            } catch (error) {
                console.error('Network Error:', error);
                messageElement.textContent = 'A network error occurred during registration.';
                messageElement.style.color = 'red';
                messageElement.style.display = 'block';
            }
        });
    }
});