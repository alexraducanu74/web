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
        } elseif ($actiune == "updateProgress" && isset($parametri[0])) {
            $this->updateProgress((int)$parametri[0]);   
        } elseif ($actiune === 'myBooks') {
            $this->myBooks();
        } elseif ($actiune == "saveReview" && isset($parametri[0])) {
            $this->saveReview((int)$parametri[0]);
        } elseif ($actiune == "ajaxFilterBooks") {
            $this->ajaxFilterBooks();
        } elseif ($actiune == "genereazaRss") {
            $this->genereazaRss();
        } elseif ($actiune == "deleteBook" && isset($parametri[0])) {
            $this->deleteBook((int) $parametri[0]);
        } elseif ($actiune == "insertBook" && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->insertBook();
        } elseif ($actiune == "editBook" && isset($parametri[0])) {
            $this->editBookForm((int) $parametri[0]);
        } elseif ($actiune == "updateBook" && isset($parametri[0])) {
            $this->updateBook((int) $parametri[0]);
        } else {
            $this->handleFeedDisplay('', [], []);
        }
    }
    private function genereazaRss() {
        include __DIR__ . '/../assets/rss/generate_rss.php';


        header("Location: /web/assets/rss/anunturi.xml");
        exit;
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

        $isAdmin = $this->getAuthenticatedUser()['is_admin'] ?? false;
        if (!$isAdmin) {
            http_response_code(403);
            echo "Forbidden";
            return;
        }

        $formHtml = $this->view->loadEditFormTemplate($book);
        $authLinksForLayout = $this->view->getAuthSpecificLinks();

        $layout = $this->view->loadTemplate('views/layout.tpl', [
            'title' => "Edit Book - " . htmlspecialchars($book['title']),
            'content' => $formHtml,
            'authLinks' => $authLinksForLayout
        ]);
        echo $layout;
    }
    private function updateBook(int $id): void
    {
        $user = $this->getAuthenticatedUser();
        if (!$user || !$user['is_admin']) {
            http_response_code(403);
            echo "Forbidden";
            return;
        }

        $title = trim($_POST['title'] ?? '');
        $author = trim($_POST['author'] ?? '');
        $genre = trim($_POST['genre'] ?? '');

        if ($title === '' || $author === '') {
            echo "Title and Author are required.";
            return;
        }

        $model = $this->modelFeed;
        $oldBook = $model->getBookById($id);
        if (!$oldBook) {
            echo "Book not found.";
            return;
        }

        $coverImage = $oldBook['cover_image']; // default to old image

        // Handle new cover image upload if any
        if (!empty($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
            $tmpName = $_FILES['cover_image']['tmp_name'];
            $ext = pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION);

            $newFileName = time() . '.' . $ext;
            $destination = __DIR__ . '/../assets/covers/' . $newFileName;

            if (move_uploaded_file($tmpName, $destination)) {
                $coverImage = 'covers/' . $newFileName;

                // Use model method to check if old image is used elsewhere
                $count = $model->countBooksUsingCover($oldBook['cover_image'], $id);
                if ($oldBook['cover_image'] && $count === 0) {
                    $oldImagePath = __DIR__ . '/../assets/' . $oldBook['cover_image'];
                    if (file_exists($oldImagePath)) unlink($oldImagePath);
                }
            } else {
                echo "Failed to upload new cover image.";
                return;
            }
        }

        $updateSuccess = $model->updateBook($id, [
            'title' => $title,
            'author' => $author,
            'genre' => $genre,
            'cover_image' => $coverImage,
        ]);

        if ($updateSuccess) {
            header("Location: index.php?controller=feed&actiune=viewBook&parametrii={$id}");
            exit;
        } else {
            echo "Failed to update book.";
        }
    }


    private function insertBook(): void
    {
        if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
            http_response_code(403);
            echo "Forbidden";
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = trim($_POST['title'] ?? '');
            $author = trim($_POST['author'] ?? '');
            $genre = trim($_POST['genre'] ?? '');

            if (empty($title) || empty($author)) {
                echo "Title and author are required.";
                exit;
            }

            if (!isset($_FILES['cover_image']) || $_FILES['cover_image']['error'] !== UPLOAD_ERR_OK) {
                echo "Error uploading image.";
                exit;
            }

            $uploadDir = __DIR__ . '/../assets/covers/'; 
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $extension = pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION);
            $newFileName = time() . '.' . $extension;

            $destPath = $uploadDir . $newFileName;
            $fileTmpPath = $_FILES['cover_image']['tmp_name'];
            if (!move_uploaded_file($fileTmpPath, $destPath)) {
                echo "Failed to save uploaded image.";
                exit;
            }

            $bookData = [
                'title' => $title,
                'author' => $author,
                'genre' => $genre,
                'cover_image' => 'covers/' . $newFileName,  
            ];

            $success = $this->modelFeed->insertBook($bookData);
            if ($success) {
                header('Location: index.php?controller=feed&actiune=showFeed');
                exit;
            } else {
                echo "Failed to save book to database.";
            }
        }
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
            echo "Cartea nu a fost găsită.";
            return;
        }

        $user = $this->getAuthenticatedUser();
        $progress = null;
        $allReviews = $this->modelFeed->getAllReviewsForBook($id);

        if ($user) {
            $progress = $this->modelFeed->getUserProgress($user['user_id'], $id);
        }

        $this->viewFeed->renderBook($book, $progress, $allReviews);
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

            $bookHtml .= "
                <div class='book-entry'>
                    <h3>" . htmlspecialchars($book['title']) . "</h3>
                    <p><strong>Author:</strong> " . htmlspecialchars($book['author']) . "</p>
                    <p><strong>Progress:</strong> {$book['pages_read']} / {$book['total_pages']} pages ({$progress}%)</p>
                    <p><strong>Your Review:</strong> " . nl2br(htmlspecialchars($book['review'])) . "</p>
                    <a href='index.php?controller=feed&actiune=viewBook&parametrii={$book['id']}'>View Book</a>
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

        $pagesRead = (int)($_POST['pages_read'] ?? 0);
        $review = trim($_POST['review'] ?? '');

        $book = $this->modelFeed->getBookById($bookId);
        if (!$book) {
            die("Book not found.");
        }

        $totalPages = (int)$book['total_pages'];

        if ($pagesRead > $totalPages) {
            die("Pages read cannot exceed total pages.");
        }

        $this->modelFeed->saveUserProgress($userId, $bookId, $pagesRead, $review);

        header("Location: index.php?controller=feed&actiune=myBooks");
        exit;
    }

    public function updateProgress(int $bookId): void
    {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            http_response_code(401);
            echo "You must be logged in.";
            return;
        }

        $pagesRead = (int) ($_POST['pages_read'] ?? 0);
        $review = trim($_POST['review'] ?? '');

        $book = $this->modelFeed->getBookById($bookId);
        if (!$book) {
            echo "Book not found.";
            return;
        }

        $totalPages = (int)$book['total_pages'];

        if ($pagesRead < 0 || $pagesRead > $totalPages) {
            echo "Invalid progress input.";
            return;
        }

        $success = $this->modelFeed->saveUserProgress($user['user_id'], $bookId, $pagesRead, $review);
        if ($success) {
            header("Location: index.php?controller=feed&actiune=viewBook&parametrii={$bookId}");
            exit;
        } else {
            echo "Failed to update progress.";
        }
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

    public function deleteBook($bookId)
    {
        if ($_SESSION['is_admin']) {
            $model = new ModelFeed();
            $model->deleteBook($bookId);
    
            // Check if AJAX
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                exit;
            } else {
                header("Location: /web/index.php");
                exit;
            }
        } else {
            http_response_code(403);
            echo "Forbidden";
            exit;
        }
    }
    

}