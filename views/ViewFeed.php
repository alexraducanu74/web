<?php
class ViewFeed
{
    private array $books = [];
    private string $query = '';
    private array $currentAuthorFilters = [];
    private array $currentGenreFilters = [];
    private array $libraries = [];
    private array $allAuthors = [];
    private array $allGenres = [];

    public function setBooks(array $books): void
    {
        $this->books = $books;
    }

    public function setQuery(string $query): void
    {
        $this->query = $query;
    }

    public function setAllAuthors(array $authors): void
    {
        $this->allAuthors = $authors;
    }

    public function setAllGenres(array $genres): void
    {
        $this->allGenres = $genres;
    }

    public function setCurrentFilters(array $authors, array $genres): void
    {
        $this->currentAuthorFilters = $authors;
        $this->currentGenreFilters = $genres;
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
        $filterHtml = "<div id='filter-bar-container' class='filter-form-checkbox'>";

        $filterHtml .= "<fieldset><legend>Authors</legend><div class='filter-options-group'>";
        if (empty($this->allAuthors)) {
            $filterHtml .= "<p>No authors available for filtering.</p>";
        } else {
            foreach ($this->allAuthors as $author) {
                $authorHtml = htmlspecialchars($author);
                $authorId = 'author_cb_' . md5($author);
                $checked = in_array($author, $this->currentAuthorFilters) ? 'checked' : '';
                $filterHtml .= "<div><input type='checkbox' name='author_filter[]' id='{$authorId}' value='{$authorHtml}' {$checked}> <label for='{$authorId}'>{$authorHtml}</label></div>";
            }
        }
        $filterHtml .= "</div></fieldset>";

        $filterHtml .= "<fieldset><legend>Genres</legend><div class='filter-options-group'>";
        if (empty($this->allGenres)) {
            $filterHtml .= "<p>No genres available for filtering.</p>";
        } else {
            foreach ($this->allGenres as $genre) {
                $genreHtml = htmlspecialchars($genre);
                $genreId = 'genre_cb_' . md5($genre); // ID unic
                $checked = in_array($genre, $this->currentGenreFilters) ? 'checked' : '';
                $filterHtml .= "<div><input type='checkbox' name='genre_filter[]' id='{$genreId}' value='{$genreHtml}' {$checked}> <label for='{$genreId}'>{$genreHtml}</label></div>";
            }
        }
        $filterHtml .= "</div></fieldset>";

        $filterHtml .= "<div class='filter-actions'>";
        $filterHtml .= "<button id='apply-filters-button'>Apply Filters</button>";
        $filterHtml .= "<button id='reset-filters-button' type='button'>Reset Filters</button>";
        $filterHtml .= "</div>";

        $filterHtml .= "</div>";
        return $filterHtml;
    }

    public function render(): void
    {
        $headerText = "Browse Books";
        if (!empty($this->query)) {
            $headerText = "Results for \"" . htmlspecialchars($this->query) . "\"";
        } elseif (!empty($this->currentAuthorFilters) || !empty($this->currentGenreFilters)) {
            $headerText = "Filtered Books";
        }
        $header = "<h2>{$headerText}</h2>";

        $rawFilterBarHtml = $this->renderFilterBar();
        $toggleAndWrappedFiltersHtml = "<button id='toggle-filters-button' class='toggle-filters-btn' style='margin-bottom: 10px; padding: 8px 15px; background-color: #555; color: white; border: none; border-radius: 4px; cursor: pointer;'>Show Filters</button>" .
            "<div id='filters-wrapper' style='display: none;'>" . $rawFilterBarHtml . "</div>";

        $bookItemsHtml = $this->renderBookItems($this->books);

        $content = $this->loadTemplate('views/feed.tpl', [
            'header' => $header,
            'filterBar' => $toggleAndWrappedFiltersHtml,
            'books' => $bookItemsHtml
        ]);

        $scriptTag = '<script src="assets/js/feed_filters.js" defer></script>';

        $layout = $this->loadTemplate('views/layout.tpl', [
            'title' => 'Browse Books',
            'content' => $content . $scriptTag
        ]);
        echo $layout;
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
            $altFilePath = __DIR__ . '/' . basename($filePath);
            if (strpos($filePath, 'views/') === 0) {
                $altFilePath = __DIR__ . '/../' . $filePath;
            }


            if (!file_exists($altFilePath) && file_exists(__DIR__ . '/../' . $filePath)) {
                $actualFilePath = __DIR__ . '/../' . $filePath;
            } else if (file_exists($altFilePath)) {
                $actualFilePath = $altFilePath;
            } else {
                return "Error: Template file not found at {$filePath} or {$altFilePath}";
            }
        } else {
            $actualFilePath = $filePath;
        }

        $template = file_get_contents($actualFilePath);
        foreach ($data as $key => $value) {
            $template = str_replace('{$' . $key . '}', (string) $value, $template);
        }
        return $template;
    }

    public function renderNoBooks(): void
    {
        $libsHtml = '<h3>No books matched your search/filters.</h3>';
        if (!empty($this->libraries)) {
            $libsHtml .= '<p>Perhaps you can find something at these nearby public libraries:</p>';
            $libsHtml .= "<ul>";
            foreach ($this->libraries as $lib) {
                $name = htmlspecialchars($lib['name']);
                $url = htmlspecialchars($lib['url']);
                $libsHtml .= "<li><a href='{$url}' target='_blank' rel='noopener noreferrer'>{$name}</a></li>";
            }
            $libsHtml .= "</ul>";
        } else {
            $libsHtml .= "<p>We couldn't find any libraries nearby at the moment, or there was an issue fetching them.</p>";
        }

        $contentForNoBooksTpl = $this->loadTemplate('views/no-books.tpl', [
            'libraries' => $libsHtml,
            'query' => htmlspecialchars($this->query)
        ]);

        $rawFilterBarHtml = $this->renderFilterBar();
        $toggleAndWrappedFiltersHtml = "<button id='toggle-filters-button' class='toggle-filters-btn' style='margin-bottom: 10px; padding: 8px 15px; background-color: #555; color: white; border: none; border-radius: 4px; cursor: pointer;'>Show Filters</button>" .
            "<div id='filters-wrapper' style='display: none;'>" . $rawFilterBarHtml . "</div>";

        $scriptTag = '<script src="assets/js/feed_filters.js" defer></script>';

        $layout = $this->loadTemplate('views/layout.tpl', [
            'title' => 'No Books Found',
            'content' => $toggleAndWrappedFiltersHtml . $contentForNoBooksTpl . $scriptTag
        ]);
        echo $layout;
    }
}