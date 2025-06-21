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

        $user = $this->getAuthenticatedUser();
        $this->viewFeed->setUser($user); // Pass user data to the view

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

    private function editBookForm(int $id): void
    {
        $book = $this->modelFeed->getBookById($id);
        if (!$book) {
            echo "Book not found.";
            return;
        }

        $user = $this->getAuthenticatedUser();
        $isAdmin = $user && $user['is_admin'];
        if (!$isAdmin) {
            http_response_code(403);
            echo "Forbidden";
            return;
        }

        $this->viewFeed->renderEditBookForm($book);
    }

    private function handleFeedDisplay(string $query, array $authorFilter, array $genreFilter): void
    {

        $books = $this->modelFeed->getBooks($query, $authorFilter, $genreFilter);
        $allAuthors = $this->modelFeed->getDistinctAuthors();
        $allGenres = $this->modelFeed->getDistinctIndividualGenres();

        $user = $this->getAuthenticatedUser();
        $isAdmin = $user && $user['is_admin'];

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
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            header('Location: index.php?controller=auth&actiune=showLoginForm');
            exit;
        }
        $userId = $user['user_id'];
        $username = $user['username'];

        $books = $this->modelFeed->getBooksWithUserProgress($userId);


        $this->viewFeed->renderMyBooks($books, $username);
    }

    public function saveReview($bookId)
    {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            header('Location: index.php?controller=auth&actiune=showLoginForm');
            exit;
        }
        $userId = $user['user_id'];

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