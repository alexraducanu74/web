document.addEventListener('DOMContentLoaded', function () {
    const bookListDiv = document.querySelector('.book-list');
    const applyFiltersButton = document.getElementById('apply-filters-button');
    const resetFiltersButton = document.getElementById('reset-filters-button');
    const mainSearchInput = document.querySelector('.search-form input[type="text"]');
    const toggleFiltersButton = document.getElementById('toggle-filters-button');
    const filtersWrapper = document.getElementById('filters-wrapper');
    if (toggleFiltersButton && filtersWrapper) {
        toggleFiltersButton.addEventListener('click', function () {
            if (filtersWrapper.style.display === 'none' || filtersWrapper.style.display === '') {
                filtersWrapper.style.display = 'block';
                toggleFiltersButton.textContent = 'Hide Filters';
            } else {
                filtersWrapper.style.display = 'none';
                toggleFiltersButton.textContent = 'Show Filters';
            }
        });
        if (filtersWrapper.style.display === 'none' || filtersWrapper.style.display === '') {
            toggleFiltersButton.textContent = 'Show Filters';
        } else {
            toggleFiltersButton.textContent = 'Hide Filters';
        }
    }
    function fetchFilteredBooks() {
        if (!bookListDiv) {
            console.error("Element with class 'book-list' not found.");
            return;
        }
        const selectedAuthors = Array.from(document.querySelectorAll('input[name="author_filter[]"]:checked')).map(cb => cb.value);
        const selectedGenres = Array.from(document.querySelectorAll('input[name="genre_filter[]"]:checked')).map(cb => cb.value);
        const searchQuery = mainSearchInput ? mainSearchInput.value.trim() : '';
        let ajaxUrl = `index.php?controller=feed&actiune=ajaxFilterBooks`;
        const queryParams = new URLSearchParams();
        if (searchQuery) {
            queryParams.append('q', searchQuery);
        }
        selectedAuthors.forEach(author => queryParams.append('author_filter[]', author));
        selectedGenres.forEach(genre => queryParams.append('genre_filter[]', genre));
        const queryString = queryParams.toString();
        if (queryString) {
            ajaxUrl += `&${queryString}`;
        }
        fetch(ajaxUrl)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.statusText);
                }
                return response.text();
            })
            .then(html => {
                bookListDiv.innerHTML = html;
            })
            .catch(error => {
                console.error('Error fetching filtered books:', error);
                bookListDiv.innerHTML = '<p class="no-books-message-ajax">Error loading books. Please try again.</p>';
            });
    }
    if (applyFiltersButton) {
        applyFiltersButton.addEventListener('click', fetchFilteredBooks);
    }
    if (resetFiltersButton) {
        resetFiltersButton.addEventListener('click', function () {
            document.querySelectorAll('input[name="author_filter[]"]').forEach(cb => cb.checked = false);
            document.querySelectorAll('input[name="genre_filter[]"]').forEach(cb => cb.checked = false);
            fetchFilteredBooks();
        });
    }
});