document.addEventListener('DOMContentLoaded', function () {
    // Check if the PHP has set a JWT token to be stored
    if (typeof jwtTokenToStore !== 'undefined' && jwtTokenToStore) {
        try {
            sessionStorage.setItem('jwtToken', jwtTokenToStore);
            // Optionally, you can remove the global variable after storing it
            // delete window.jwtTokenToStore;

            // Alert and redirect only if the token was successfully stored
            // and the current page is indeed the login page (to avoid alerts on other pages if this script is reused)
            // You might want to add a specific identifier to the login page's body or a meta tag
            // to confirm this is the login context before alerting and redirecting.
            if (window.location.pathname.includes('index.php') && new URLSearchParams(window.location.search).get('controller') === 'auth' && (new URLSearchParams(window.location.search).get('actiune') === 'showLoginForm' || new URLSearchParams(window.location.search).get('actiune') === 'login')) {
                alert('Login successful! Token stored in session storage. Redirecting...');
                window.location.href = 'index.php?controller=feed&actiune=showFeed';
            }

        } catch (e) {
            console.error('Error storing JWT token in session storage:', e);
            alert('Could not store login session. Please ensure your browser supports session storage and try again.');
        }
    }
});