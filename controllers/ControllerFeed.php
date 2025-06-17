<?php

class ControllerFeed extends Controller
{
    private ModelFeed $modelFeed;
    private ViewFeed $viewFeed;

    public function __construct(string $actiune, array $parametri)
    {
        parent::__construct();
        $this->modelFeed = new ModelFeed();
        $this->viewFeed = new ViewFeed();

        $query = $_GET['q'] ?? '';
        $authorParams = $_GET['author'] ?? [];
        $genreParams = $_GET['genre'] ?? [];

        $currentAuthors = is_array($authorParams) ? $authorParams : (!empty($authorParams) ? [$authorParams] : []);
        $currentGenres = is_array($genreParams) ? $genreParams : (!empty($genreParams) ? [$genreParams] : []);

        if ($actiune == "showFeed" || $actiune == "search") {
            $this->handleFeedDisplay($query, $currentAuthors, $currentGenres);
        } elseif ($actiune == "viewBook" && isset($parametri[0])) {
            $this->viewBook((int) $parametri[0]);
        } elseif ($actiune === 'myBooks') {
            $this->myBooks();
        } elseif ($actiune == "saveReview" && isset($parametri[0])) {
            $this->saveReview((int) $parametri[0]);
        } elseif ($actiune == "ajaxFilterBooks") {
            $this->ajaxFilterBooks();
        } elseif ($actiune == "editBook" && isset($parametri[0])) {
            $this->editBookForm((int) $parametri[0]);
        } else {
            $this->handleFeedDisplay('', [], []);
        }
    }


    function getAuthenticatedUser()
    {
        if (
            session_status() === PHP_SESSION_ACTIVE &&
            isset($_SESSION['user_id'], $_SESSION['username'])
        ) {
            return [
                'user_id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'is_admin' => $_SESSION['is_admin'] ?? false,
            ];
        }
        return false;
    }
    private function editBookForm(int $id): void
    {
        $book = $this->modelFeed->getBookById($id);
        if (!$book) {
            echo "Book not found.";
            return;
        }
        $book['title'] = htmlspecialchars($book['title']);
        $book['author'] = htmlspecialchars($book['author']);
        $book['genre'] = htmlspecialchars($book['genre']);
        $book['cover_image'] = htmlspecialchars($book['cover_image']);

        $isAdmin = $this->getAuthenticatedUser()['is_admin'] ?? false;
        if (!$isAdmin) {
            http_response_code(403);
            echo "Forbidden";
            return;
        }

        $formHtml = $this->view->loadEditFormTemplate($book);
        $authLinksForLayout = $this->view->getAuthSpecificLinks();
        $scriptTag = '<script src="/web/assets/js/feed-api.js" defer></script>
        <script src="assets/js/feed_filters.js" defer></script>
        <script src="assets/js/geolocation.js" defer></script>';

        $layout = $this->view->loadTemplate('views/layout.tpl', [
            'title' => "Edit Book - " . htmlspecialchars($book['title']),
            'content' => $formHtml . $scriptTag,
            'authLinks' => $authLinksForLayout
        ]);
        echo $layout;
    }

    private function handleFeedDisplay(string $query, array $authorFilter, array $genreFilter): void
    {

        $books = $this->modelFeed->getBooks($query, $authorFilter, $genreFilter);
        $allAuthors = $this->modelFeed->getDistinctAuthors();
        $allGenres = $this->modelFeed->getDistinctIndividualGenres();

        $user = $this->getAuthenticatedUser();
        $isAdmin = $user !== false && isset($user['is_admin']) && $user['is_admin'] === true;

        $this->viewFeed->setIsAdmin($isAdmin);
        $this->viewFeed->setAllAuthors($allAuthors);
        $this->viewFeed->setAllGenres($allGenres);
        $this->viewFeed->setQuery($query);
        $this->viewFeed->setCurrentFilters($authorFilter, $genreFilter);
        $this->viewFeed->setBooks($books);

        if (empty($books) && (!empty($query) || !empty($authorFilter) || !empty($genreFilter))) {
            $lat = isset($_GET['lat']) ? (float) $_GET['lat'] : 0;
            $lon = isset($_GET['lon']) ? (float) $_GET['lon'] : 0;
            if ($lat === 0 && $lon === 0) {
                $lat = 44.4268;
                $lon = 26.1025;
            }
            $libraries = $this->modelFeed->findLibrariesNearby($lat, $lon);
            $this->viewFeed->setLibraries($libraries);
            $this->viewFeed->renderNoBooks();
        } else {
            $this->viewFeed->render();
        }
    }

    private function viewBook(int $id): void
    {
        $book = $this->modelFeed->getBookById($id);
        if (!$book) {
            echo "Cartea nu a fost gasita.";
            return;
        }

        $user = $this->getAuthenticatedUser();
        $progress = null;
        $allReviews = $this->modelFeed->getAllReviewsForBook($id);
        $averageRating = $this->modelFeed->getAverageRatingForBook($id);

        if ($user) {
            $progress = $this->modelFeed->getUserProgress($user['user_id'], $id);
        }

        $this->viewFeed->renderBook($book, $progress, $allReviews, $averageRating);
    }
    public function myBooks(): void
    {
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            header('Location: index.php?controller=auth&actiune=showLoginForm');
            exit;
        }

        $books = $this->modelFeed->getBooksWithUserProgress($userId);

        $bookHtml = '';
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

        if (empty($bookHtml)) {
            $bookHtml = "<p>You haven't reviewed or made progress on any books yet.</p>";
        }

        $content = $this->view->loadTemplate('views/book.tpl', ['book' => $bookHtml]);

        $layout = $this->view->loadTemplate('views/layout.tpl', [
            'title' => 'My Books',
            'content' => $content,
            'authLinks' => $this->view->getAuthSpecificLinks()
        ]);

        echo $layout;
    }

    public function saveReview($bookId)
    {
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            header('Location: index.php?controller=auth&actiune=showLoginForm');
            exit;
        }

        $review = trim($_POST['review'] ?? '');
        $pagesRead = (int) ($_POST['pages_read'] ?? 0);
        $rating = isset($_POST['rating']) ? (int) $_POST['rating'] : null;

        if ($review === '') {
            die("Review cannot be empty.");
        }

        if ($rating === null || $rating < 1 || $rating > 5) {
            die("Rating must be provided and between 1 and 5.");
        }

        $book = $this->modelFeed->getBookById($bookId);
        if (!$book) {
            die("Book not found.");
        }

        $totalPages = (int) $book['total_pages'];

        if ($pagesRead > $totalPages) {
            die("Pages read cannot exceed total pages.");
        }

        $this->modelFeed->saveUserProgress($userId, $bookId, $pagesRead, $review, $rating);

        header("Location: index.php?controller=feed&actiune=myBooks");
        exit;
    }

    private function ajaxFilterBooks(): void
    {
        header('Content-Type: text/html');

        $authorFilter = $_GET['author_filter'] ?? [];
        $genreFilter = $_GET['genre_filter'] ?? [];
        $generalQuery = $_GET['q'] ?? '';

        if (!is_array($authorFilter))
            $authorFilter = $authorFilter ? [$authorFilter] : [];
        if (!is_array($genreFilter))
            $genreFilter = $genreFilter ? [$genreFilter] : [];

        $books = $this->modelFeed->getBooks($generalQuery, $authorFilter, $genreFilter);
        echo $this->viewFeed->renderBookItems($books);
        exit;
    }

}