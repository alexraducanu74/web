document.addEventListener('DOMContentLoaded', function () {
    if (typeof jwtTokenToStore !== 'undefined' && jwtTokenToStore) {
        try {
            sessionStorage.setItem('jwtToken', jwtTokenToStore);
            if (window.location.pathname.includes('index.php') && new URLSearchParams(window.location.search).get('controller') === 'auth' && (new URLSearchParams(window.location.search).get('actiune') === 'showLoginForm' || new URLSearchParams(window.location.search).get('actiune') === 'login')) {
                window.location.href = 'index.php?controller=feed&actiune=showFeed';
            }

        } catch (e) {
            console.error('Error storing JWT token in session storage:', e);
        }
    }
});