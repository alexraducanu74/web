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
}


