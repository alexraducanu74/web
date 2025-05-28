<?php
class ViewFeed
{
    private array $books = [];
    private string $query = '';
    private ?string $currentAuthorFilter = null;
    private ?string $currentGenreFilter = null;
    private array $libraries = [];
    public function setBooks(array $books): void
    {
        $this->books = $books;
    }
    public function setQuery(string $query): void
    {
        $this->query = $query;
    }
    public function setCurrentFilters(?string $author, ?string $genre): void
    {
        $this->currentAuthorFilter = $author;
        $this->currentGenreFilter = $genre;
    }
    public function setLibraries(array $libraries): void
    {
        $this->libraries = $libraries;
    }
    public function renderBookItems(array $booksToRender): string
    {
        if (empty($booksToRender)) {
            return "<p class='no-books-message-ajax'>No books found matching your filters.</p>";
        }
        $itemsHtml = '';
        foreach ($booksToRender as $book) {
            $itemsHtml .= "
            <a href='index.php?controller=feed&actiune=viewBook&parametrii={$book['id']}'>
                <div class='book'>
                    <img src='assets/{$book['cover_image']}' alt='Cover of " . htmlspecialchars($book['title']) . "'>
                    <h3>" . htmlspecialchars($book['title']) . "</h3>
                    <p>by " . htmlspecialchars($book['author']) . "</p>
                    " . (!empty($book['genre']) ? "<p class='genre'>Genre: " . htmlspecialchars($book['genre']) . "</p>" : "") . "
                </div>
            </a>";
        }
        return $itemsHtml;
    }
    private function renderFilterBar(): string
    {
        $authorValue = $this->currentAuthorFilter ? htmlspecialchars($this->currentAuthorFilter) : '';
        $genreValue = $this->currentGenreFilter ? htmlspecialchars($this->currentGenreFilter) : '';
        return "
            <div id='filter-bar-container' class='filter-form'>
                <input type='text' id='author-filter' name='author' value='{$authorValue}' placeholder='Filter by Author...'>
                <input type='text' id='genre-filter' name='genre' value='{$genreValue}' placeholder='Filter by Genre...'>
                <button id='apply-filters-button'>Apply Filters</button>
                <button id='reset-filters-button' type='button'>Reset Filters</button>
            </div>
        ";
    }
    public function render(): void
    {
        $headerText = "Browse Books";
        if (!empty($this->query)) {
            $headerText = "Results for \"" . htmlspecialchars($this->query) . "\"";
        } elseif (!empty($this->currentAuthorFilter) || !empty($this->currentGenreFilter)) {
            $headerText = "Filtered Books";
        }
        $header = "<h2>{$headerText}</h2>";
        $filterBarHtml = $this->renderFilterBar();
        $bookItemsHtml = $this->renderBookItems($this->books);
        $content = $this->loadTemplate('views/feed.tpl', [
            'header' => $header,
            'filterBar' => $filterBarHtml,
            'books' => $bookItemsHtml
        ]);
        $ajaxScript = $this->getAjaxScript();
        $layout = $this->loadTemplate('views/layout.tpl', [
            'title' => 'Browse Books',
            'content' => $content . $ajaxScript
        ]);
        echo $layout;
    }
    private function getAjaxScript(): string
    {
        return <<<SCRIPT
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const authorInput = document.getElementById('author-filter');
            const genreInput = document.getElementById('genre-filter');
            const bookListDiv = document.querySelector('.book-list');
            const applyFiltersButton = document.getElementById('apply-filters-button');
            const resetFiltersButton = document.getElementById('reset-filters-button');
            const mainSearchInput = document.querySelector('.search-form input[type="text"]'); 
            function fetchFilteredBooks() {
                const authorValue = authorInput.value.trim();
                const genreValue = genreInput.value.trim();
                const params = new URLSearchParams(window.location.search);
                if (authorValue) params.set('author', authorValue); else params.delete('author');
                if (genreValue) params.set('genre', genreValue); else params.delete('genre');
                let ajaxUrl = `index.php?controller=feed&actiune=ajaxFilterBooks`;
                if (authorValue) ajaxUrl += `&author=\${encodeURIComponent(authorValue)}`;
                if (genreValue) ajaxUrl += `&genre=\${encodeURIComponent(genreValue)}`;
                fetch(ajaxUrl)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
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
                resetFiltersButton.addEventListener('click', function() {
                    authorInput.value = '';
                    genreInput.value = '';
                    const params = new URLSearchParams(window.location.search);
                    params.delete('author');
                    params.delete('genre');
                    fetchFilteredBooks(); 
                });
            }
        });
        </script>
SCRIPT;
    }
    public function renderBook(array $book): void
    {
        $bookHtml = "
            <div class='book-detail'>
                 <img src='assets/" . htmlspecialchars($book['cover_image']) . "' alt='Cover of " . htmlspecialchars($book['title']) . "'>
                <div class='book-info'>
                    <h2>" . htmlspecialchars($book['title']) . "</h2>
                    <p><strong>Author:</strong> " . htmlspecialchars($book['author']) . "</p>
                    " . (!empty($book['genre']) ? "<p><strong>Genre:</strong> " . htmlspecialchars($book['genre']) . "</p>" : "") . "
                </div>
            </div>";
        $content = $this->loadTemplate('views/book.tpl', ['book' => $bookHtml]);
        $layout = $this->loadTemplate('views/layout.tpl', [
            'title' => htmlspecialchars($book['title']),
            'content' => $content
        ]);
        echo $layout;
    }
    private function loadTemplate(string $filePath, array $data): string
    {
        if (!file_exists($filePath)) {
            return "Error: Template file not found at {$filePath}";
        }
        $template = file_get_contents($filePath);
        foreach ($data as $key => $value) {
            $template = str_replace('{$' . $key . '}', (string) $value, $template);
        }
        return $template;
    }
    public function renderNoBooks(): void
    {
        $libsHtml = '<h3>No books matched your search/filters.</h3>';
        $libsHtml .= '<p>Perhaps you can find something at these nearby public libraries:</p>';
        if (empty($this->libraries)) {
            $libsHtml .= "<p>We couldn't find any libraries nearby at the moment.</p>";
        } else {
            $libsHtml .= "<ul>";
            foreach ($this->libraries as $lib) {
                $name = htmlspecialchars($lib['name']);
                $url = htmlspecialchars($lib['url']);
                $libsHtml .= "<li><a href='{$url}' target='_blank' rel='noopener noreferrer'>{$name}</a></li>";
            }
            $libsHtml .= "</ul>";
        }
        $content = $this->loadTemplate('views/no-books.tpl', [
            'libraries' => $libsHtml,
            'query' => htmlspecialchars($this->query)
        ]);
        $filterBarHtml = $this->renderFilterBar();
        $layout = $this->loadTemplate('views/layout.tpl', [
            'title' => 'No Books Found',
            'content' => $filterBarHtml . $content . $this->getAjaxScript()
        ]);
        echo $layout;
    }
}