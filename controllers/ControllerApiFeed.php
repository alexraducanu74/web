<?php

class ControllerApiFeed extends Controller
{
    private ModelFeed $modelFeed;

    public function __construct(string $actiune, array $parametri)
    {
        parent::__construct();
        $this->modelFeed = new ModelFeed();

        // Set common API headers
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
        header('Content-Type: application/json');

        $bookId = (int) ($parametri[0] ?? 0); // Assuming bookId is always the first parameter for these actions

        switch ($actiune) {
            case 'insertBookApi':
                $this->insertBookApi();
                break;

            case 'deleteBookApi':
                $this->deleteBookApi($bookId);
                break;

            case 'updateBookApi':
                $this->updateBookApi($bookId);
                break;

            case 'genereazaRssApi':
                $this->genereazaRssApi();
                break;

            default:
                http_response_code(404);
                echo json_encode(['error' => 'Unknown API action.']);
                break;
        }
    }

    public function deleteBookApi(int $bookId): void
    {
        $user = $this->getAuthenticatedUser();
        if (!$user || !$user['is_admin']) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            return;
        }

        $success = $this->modelFeed->deleteBook($bookId);
        if ($success) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete book.']);
        }
    }

    public function updateBookApi(int $id): void
    {
        $user = $this->getAuthenticatedUser();
        if (!$user || !$user['is_admin']) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            return;
        }

        $title = trim($_POST['title'] ?? '');
        $author = trim($_POST['author'] ?? '');
        $genre = trim($_POST['genre'] ?? '');

        if ($title === '' || $author === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Title and Author are required.']);
            return;
        }

        $oldBook = $this->modelFeed->getBookById($id);
        if (!$oldBook) {
            http_response_code(404);
            echo json_encode(['error' => 'Book not found.']);
            return;
        }

        $coverImage = $oldBook['cover_image']; // default to old image

        // Handle new cover image upload if any
        if (!empty($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
            $tmpName = $_FILES['cover_image']['tmp_name'];
            $ext = pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION);
            $newFileName = time() . '.' . $ext;
            $destination = __DIR__ . '/../assets/covers/' . $newFileName;

            if (!move_uploaded_file($tmpName, $destination)) {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to upload new cover image.']);
                return;
            }

            $coverImage = 'covers/' . $newFileName;

            // Delete old image if no longer used
            $count = $this->modelFeed->countBooksUsingCover($oldBook['cover_image'], $id);
            if ($oldBook['cover_image'] && $count === 0) {
                $oldImagePath = __DIR__ . '/../assets/' . $oldBook['cover_image'];
                if (file_exists($oldImagePath))
                    unlink($oldImagePath);
            }
        }

        $updateSuccess = $this->modelFeed->updateBook($id, [
            'title' => $title,
            'author' => $author,
            'genre' => $genre,
            'cover_image' => $coverImage,
        ]);

        if ($updateSuccess) {
            echo json_encode(['success' => true, 'bookId' => $id]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update book.']);
        }
    }

    public function genereazaRssApi()
    {
        $pdo = Dbh::getInstance()->getConnection();

        $doc = new DOMDocument("1.0", "UTF-8");
        $doc->formatOutput = true;

        $rss = $doc->createElement("rss");
        $rss->setAttribute("version", "2.0");
        $doc->appendChild($rss);

        $channel = $doc->createElement("channel");
        $channel->appendChild($doc->createElement("title", "Carti si Recenzii Adaugate Recent"));
        $channel->appendChild($doc->createElement("link", "http://localhost/web/index.php"));
        $channel->appendChild($doc->createElement("description", "Cartile si recenziile adaugate cel mai recent"));
        $channel->appendChild($doc->createElement("language", "ro"));
        $channel->appendChild($doc->createElement("pubDate", date(DATE_RSS)));
        $rss->appendChild($channel);

        $stmt = $pdo->query("SELECT * FROM books ORDER BY created_at DESC");

        while ($book = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $item = $doc->createElement("item");

            $titleText = $book['title'];
            $linkText = "http://localhost/web/index.php?controller=feed&actiune=viewBook&parametri=" . $book['id'];
            $descText = "Autor: " . $book['author'] . " | Gen: " . $book['genre'];

            $item->appendChild($doc->createElement("title", htmlspecialchars($titleText, ENT_XML1, 'UTF-8')));
            $item->appendChild($doc->createElement("link", htmlspecialchars($linkText, ENT_XML1, 'UTF-8')));
            $item->appendChild($doc->createElement("description", htmlspecialchars($descText, ENT_XML1, 'UTF-8')));
            $item->appendChild($doc->createElement("pubDate", date(DATE_RSS, strtotime($book['created_at']))));

            $channel->appendChild($item);
        }

        $reviewStmt = $pdo->query("
            SELECT p.review, p.pages_read, p.updated_at, p.rating, b.id as book_id, b.title, u.users_uid 
            FROM user_book_progress p
            JOIN books b ON p.book_id = b.id
            JOIN users u ON p.user_id = u.users_id
            WHERE p.review IS NOT NULL AND TRIM(p.review) <> ''
            ORDER BY p.updated_at DESC
            LIMIT 10
        ");

        while ($review = $reviewStmt->fetch(PDO::FETCH_ASSOC)) {
            $item = $doc->createElement("item");

            $titleText = "Review for: " . $review['title'] . " by " . $review['users_uid'];
            $linkText = "http://localhost/web/index.php?controller=feed&actiune=viewBook&parametri=" . $review['book_id'];
            $ratingValue = (int) $review['rating'];
            $descText = htmlspecialchars($review['review'], ENT_XML1, 'UTF-8')
                . " | Pages read: " . $review['pages_read'] . " | Rating: " . $ratingValue;

            $item->appendChild($doc->createElement("title", htmlspecialchars($titleText, ENT_XML1, 'UTF-8')));
            $item->appendChild($doc->createElement("link", htmlspecialchars($linkText, ENT_XML1, 'UTF-8')));
            $item->appendChild($doc->createElement("description", $descText));
            $item->appendChild($doc->createElement("pubDate", date(DATE_RSS, strtotime($review['updated_at']))));

            $channel->appendChild($item);
        }

        header("Content-Type: application/rss+xml; charset=UTF-8");
        echo $doc->saveXML();
        exit;
    }

    public function insertBookApi(): void
    {
        $user = $this->getAuthenticatedUser();
        if (!$user || !$user['is_admin']) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            return;
        }

        $title = trim($_POST['title'] ?? '');
        $author = trim($_POST['author'] ?? '');
        $genre = trim($_POST['genre'] ?? '');

        if ($title === '' || $author === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Title and author are required.']);
            return;
        }

        if (!isset($_FILES['cover_image']) || $_FILES['cover_image']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['error' => 'Image upload failed.']);
            return;
        }

        $uploadDir = __DIR__ . '/../assets/covers/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $ext = pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION);
        $newFileName = time() . '.' . $ext;
        $destPath = $uploadDir . $newFileName;
        $tmpPath = $_FILES['cover_image']['tmp_name'];

        if (!move_uploaded_file($tmpPath, $destPath)) {
            http_response_code(500);
            echo json_encode(['error' => 'Could not save uploaded image.']);
            return;
        }

        $success = $this->modelFeed->insertBook([
            'title' => $title,
            'author' => $author,
            'genre' => $genre,
            'cover_image' => 'covers/' . $newFileName,
        ]);

        if ($success) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to save book to database.']);
        }
    }
}