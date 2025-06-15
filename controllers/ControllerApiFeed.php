<?php
class ControllerApiFeed
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
  public function updateBookApi(int $id): void
    {
        header('Content-Type: application/json');

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

        $model = new ModelFeed();
        $oldBook = $model->getBookById($id);
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
            $count = $model->countBooksUsingCover($oldBook['cover_image'], $id);
            if ($oldBook['cover_image'] && $count === 0) {
                $oldImagePath = __DIR__ . '/../assets/' . $oldBook['cover_image'];
                if (file_exists($oldImagePath)) unlink($oldImagePath);
            }
        }

        $updateSuccess = $model->updateBook($id, [
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


