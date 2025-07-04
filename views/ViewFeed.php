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
    private bool $isAdmin = false;
    private ?array $user = null;

    public function setUser(?array $user): void
    {
        $this->user = $user;
    }

    public function setIsAdmin(bool $isAdmin): void
    {
        $this->isAdmin = $isAdmin;
    }
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
            $adminButtons = '';
            if ($this->isAdmin) {
                $adminButtons = "
                <div class='admin-buttons'>
                    <a href='index.php?controller=feed&actiune=editBook&parametri={$book['id']}' class='edit-btn'>Edit</a>
                    <a href='#' class='delete-btn' data-id='{$book['id']}'>Delete</a>
                </div>";
            }
            $itemsHtml .= "
                <div class='book'>
                    <a href='index.php?controller=feed&actiune=viewBook&parametri={$book['id']}' class='book-link'>
                        <img src='assets/" . htmlspecialchars($book['cover_image']) . "' alt='Cover of " . htmlspecialchars($book['title']) . "'>
                        <h3>" . htmlspecialchars($book['title']) . "</h3>
                        <p>by " . htmlspecialchars($book['author']) . "</p>
                        " . (!empty($book['genre']) ? "<p class='genre'>Genre: " . htmlspecialchars($book['genre']) . "</p>" : "") . "
                    </a>
                    $adminButtons
                </div>";
        }
        return $itemsHtml;
    }

    public function loadEditFormTemplate(array $book): string
    {
        $templateData = [
            'id' => htmlspecialchars($book['id']),
            'title' => htmlspecialchars($book['title']),
            'author' => htmlspecialchars($book['author']),
            'genre' => htmlspecialchars($book['genre']),
            'cover_image' => htmlspecialchars($book['cover_image']),
        ];
        return $this->loadTemplate('views/edit-book.tpl', $templateData);
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
                $genreId = 'genre_cb_' . md5($genre);
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
    public function renderMyBooks(array $books, string $username): void
    {
        $bookHtml = '';
        if (empty($books)) {
            $bookHtml = "<p>Hello, " . htmlspecialchars($username) . "! You haven't reviewed or made progress on any books yet.</p>";
        } else {
            foreach ($books as $book) {
                $progress = ($book['total_pages'] > 0)
                    ? round(($book['pages_read'] / $book['total_pages']) * 100)
                    : 0;

                $ratingDisplay = isset($book['rating']) ? "Your Rating: " . htmlspecialchars($book['rating']) . " / 5" : "No rating yet";
                $bookHtml .= "
                    <div class='book-entry'>
                        <h3>" . htmlspecialchars($book['title']) . "</h3>
                        <p><strong>Author:</strong> " . htmlspecialchars($book['author']) . "</p>
                        <p><strong>Progress:</strong> {$book['pages_read']} / {$book['total_pages']} pages ({$progress}%)</p>
                        <p><strong>Your Review:</strong> " . nl2br(htmlspecialchars($book['review'])) . "</p>
                        <p><strong>{$ratingDisplay}</strong></p>
                        <a href='index.php?controller=feed&actiune=viewBook&parametri={$book['id']}'>View Book</a>
                        <hr>
                    </div>
                ";
            }
        }

        $content = $this->loadTemplate('views/book.tpl', ['book' => $bookHtml]);
        $scriptTag = '<script src="assets/js/feed-api.js" defer></script>
        <script src="assets/js/feed_filters.js" defer></script>
        <script src="assets/js/nav.js" defer></script>
        <script src="assets/js/geolocation.js" defer></script>';
        $layout = $this->loadTemplate('views/layout.tpl', [
            'title' => 'My Books - ' . htmlspecialchars($username), // Dynamic title
            'content' => $content . $scriptTag,
            'authLinks' => $this->getAuthSpecificLinks()
        ]);

        echo $layout;
    }

    public function renderEditBookForm(array $book): void
    {
        $book['title'] = htmlspecialchars($book['title']);
        $book['author'] = htmlspecialchars($book['author']);
        $book['genre'] = htmlspecialchars($book['genre']);
        $book['cover_image'] = htmlspecialchars($book['cover_image']);

        $formHtml = $this->loadEditFormTemplate($book);
        $authLinksForLayout = $this->getAuthSpecificLinks();
        $scriptTag = '<script src="assets/js/feed-api.js" defer></script>
        <script src="assets/js/feed_filters.js" defer></script>
        <script src="assets/js/nav.js" defer></script>
        <script src="assets/js/geolocation.js" defer></script>';

        $layout = $this->loadTemplate('views/layout.tpl', [
            'title' => "Edit Book - " . htmlspecialchars($book['title']),
            'content' => $formHtml . $scriptTag,
            'authLinks' => $authLinksForLayout
        ]);
        echo $layout;
    }

    public function getAuthSpecificLinks(): string
    {
        if ($this->user) {
            $username = htmlspecialchars($this->user['username']);
            return '<a href="index.php?controller=feed&actiune=myBooks">My Books</a>
                    <a href="index.php?controller=group&actiune=myGroups">My Groups</a>
                    <a href="index.php?controller=group&actiune=showCreateForm">Create Group</a>
                    <div class="separator"></div>
                    <a href="index.php?controller=auth&actiune=logout">Logout (' . $username . ')</a>';
        } else {
            return '
                <a href="index.php?controller=auth&actiune=showLoginForm">Login</a>
                <a href="index.php?controller=auth&actiune=showRegisterForm">Register</a>';
        }
    }

    public function render(): void
    {
        $headerText = "Browse Books";
        if (!empty($this->query)) {
            $headerText = "Results for \"" . htmlspecialchars($this->query) . "\"";
        } elseif (!empty($this->currentAuthorFilters) || !empty($this->currentGenreFilters)) {
            $headerText = "Filtered Books";
        }

        $adminInsertFormHtml = $this->renderAdminInsertForm();

        $rawFilterBarHtml = $this->renderFilterBar();
        $toggleAndWrappedFiltersHtml = "<button id='toggle-filters-button' class='toggle-filters-btn' style='margin-bottom: 10px; padding: 8px 15px; background-color: #555; color: white; border: none; border-radius: 4px; cursor: pointer;'>Show Filters</button>" .
            "<div id='filters-wrapper' style='display: none;'>" . $rawFilterBarHtml . "</div>";

        $bookItemsHtml = $this->renderBookItems($this->books);

        $pageContent = "<h2>{$headerText}</h2>";
        $pageContent .= $toggleAndWrappedFiltersHtml;
        $pageContent .= "<div class='book-list'>{$bookItemsHtml}</div>";

        $contentForLayout = $adminInsertFormHtml . $pageContent;

        $scriptTag = '<script src="assets/js/feed-api.js" defer></script>
                      <script src="assets/js/feed_filters.js" defer></script>
                      <script src="assets/js/nav.js" defer></script>
                      <script src="assets/js/geolocation.js" defer></script>';

        $authLinksForLayout = $this->getAuthSpecificLinks();

        $layout = $this->loadTemplate('views/layout.tpl', [
            'title' => 'Browse Books',
            'content' => $contentForLayout . $scriptTag,
            'authLinks' => $authLinksForLayout
        ]);

        echo $layout;
    }

    function generateStarRatingHtml(int $rating): string
    {
        $starsHtml = [];
        for ($star = 5; $star >= 1; $star--) {
            $required = ($star === 5) ? 'required' : '';
            $checked = ($star === $rating) ? 'checked' : '';
            $input = "<input type='radio' id='star$star' name='rating' value='$star' $checked $required>";
            $label = "<label for='star$star' title='$star star" . ($star > 1 ? 's' : '') . "'>★</label>";
            $starsHtml[] = $input . $label;
        }
        return "
        <fieldset class='star-rating-group'>
            <legend>Please select a rating:</legend>
            <div class='star-rating'>
                " . implode('', $starsHtml) . "
            </div>
        </fieldset>";
    }

    public function renderBook(array $book, ?array $userBookData = null, array $allReviews = [], ?float $averageRating = null): void
    {
        $isLoggedIn = !is_null($this->user);
        $review = $userBookData['review'] ?? '';
        $pagesRead = $userBookData['pages_read'] ?? 0;
        $totalPages = $book['total_pages'] ?? 0;
        $rating = $userBookData['rating'] ?? 0;
        $progressPercentage = ($totalPages > 0) ? round(($pagesRead / $totalPages) * 100) : 0;
        $starRatingHtml = $this->generateStarRatingHtml($rating);
        $reviewForm = '';
        if ($isLoggedIn) {
            $reviewForm = "
            <form method='post' action='index.php?controller=feed&actiune=saveReview&parametri={$book['id']}'>
                <div>
                    <label for='review'>Your Review:</label><br>
                    <textarea name='review' id='review' rows='5' cols='100' required>" . htmlspecialchars($review) . "</textarea>
                </div>
                <div>
                    <label for='pages_read'>Pages Read:</label>
                    <input type='number' name='pages_read' id='pages_read' value='" . htmlspecialchars($pagesRead) . "' min='0' max='" . (int) $totalPages . "'>
                    <p><strong>Total Pages:</strong> " . htmlspecialchars($totalPages) . "</p>
                </div>
                <div>
                    <label>Your Rating:</label><br>
                    $starRatingHtml
                </div>
                <div>
                    <button type='submit'>Save Progress & Review</button>
                </div>
            </form>";
        } else {
            $reviewForm = "<p><em><a href='index.php?controller=auth&actiune=showLoginForm'>Login</a> to leave a review or track progress.</em></p>";
        }
        $progressBar = ($isLoggedIn && $totalPages > 0) ? "
            <div class='progress-bar' style='border: 1px solid #ccc; border-radius: 4px; overflow: hidden; width: 100%; margin-top: 10px;'>
                <div class='progress' style='width: {$progressPercentage}%; background-color: #4CAF50; color: white; padding: 2px;'>
                    {$progressPercentage}% read
                </div>
            </div>" : "";
        $bookHtml = "
            <div class='book-detail'>
                <img src='assets/" . htmlspecialchars($book['cover_image']) . "' alt='Cover of " . htmlspecialchars($book['title']) . "'>
                <div class='book-info'>
                    <h2>" . htmlspecialchars($book['title']) . "</h2>
                    <p><strong>Author:</strong> " . htmlspecialchars($book['author']) . "</p>
                    " . (!empty($book['genre']) ? "<p><strong>Genre:</strong> " . htmlspecialchars($book['genre']) . "</p>" : "") . "
                    $progressBar
                </div>
                <div class='user-interaction'>
                    $reviewForm
                </div>
            ";
        $reviewsHtml = '';
        foreach ($allReviews as $review) {
            $rating = (int) $review['rating'];
            $reviewsHtml .= "
                <div class='review-block'>
                    <p><strong>" . htmlspecialchars($review['users_uid']) . "</strong> rated: $rating / 5 ★</p>
                    <p>" . nl2br(htmlspecialchars($review['review'])) . "</p>
                    <small><em>Reviewed on " . date('F j, Y', strtotime($review['updated_at'])) . "</em></small>
                </div><hr>";
        }
        $avgRatingHtml = ($averageRating !== null)
            ? "<p><strong>Average Rating:</strong> $averageRating / 5</p>"
            : "<p><strong>Average Rating:</strong> Not rated yet.</p>";
        $bookHtml .= $avgRatingHtml;
        $bookHtml .= $reviewsHtml;
        $bookHtml .= '</div>';
        $content = $this->loadTemplate('views/book.tpl', ['book' => $bookHtml]);
        $authLinksForLayout = $this->getAuthSpecificLinks();
        $layout = $this->loadTemplate('views/layout.tpl', [
            'title' => htmlspecialchars($book['title']),
            'content' => $content . '<script src="assets/js/nav.js" defer></script>',
            'authLinks' => $authLinksForLayout
        ]);
        echo $layout;
    }

    public function loadTemplate(string $filePath, array $data): string
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

    private function renderAdminInsertForm(): string
    {
        if (!$this->isAdmin) {
            return '';
        }
        return '
            <section id="admin-insert-book" style="margin: 20px 0; padding: 15px; border: 1px solid #ccc; border-radius: 6px;">
                <h3>Add New Book</h3>
                <form id="insertBookForm" enctype="multipart/form-data">
                <input type="text" name="title" placeholder="Book Title" required>
                <input type="text" name="author" placeholder="Author" required>
                <input type="text" name="genre" placeholder="Genre">
                <input type="file" name="cover_image" accept="image/*" required>
                <button type="submit">Add Book</button>
            </form>
            </section>';
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
        $scriptTag = '<script src="assets/js/feed-api.js" defer></script>
                      <script src="assets/js/feed_filters.js" defer></script>
                      <script src="assets/js/nav.js" defer></script>
                      <script src="assets/js/geolocation.js" defer></script>';
        $authLinksForLayout = $this->getAuthSpecificLinks();
        $layout = $this->loadTemplate('views/layout.tpl', [
            'title' => 'No Books Found',
            'content' => $toggleAndWrappedFiltersHtml . $contentForNoBooksTpl . $scriptTag,
            'authLinks' => $authLinksForLayout
        ]);
        echo $layout;
    }
}
?>