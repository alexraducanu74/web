<?php
class ApiFeedController
{
  private ModelFeed $modelFeed;

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
    
  public function deleteBookApi(int $bookId): void
  {
    $this->modelFeed = new ModelFeed();
    header('Content-Type: application/json');

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

    $this->modelFeed = new ModelFeed();
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


