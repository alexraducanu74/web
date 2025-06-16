<?php
class ModelFeed
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Dbh::getInstance()->getConnection();
    }

    public function getBooks(string $generalQuery = '', array $authorFilters = [], array $genreFilters = []): array
    {
        $sql = "SELECT * FROM books";
        $conditions = [];
        $params = [];

        if (!empty($generalQuery)) {
            $conditions[] = "(title ILIKE :generalQuery OR author ILIKE :generalQueryAuthor)"; // Changed param name for clarity
            $params[':generalQuery'] = '%' . $generalQuery . '%';
            $params[':generalQueryAuthor'] = '%' . $generalQuery . '%';
        }

        if (!empty($authorFilters)) {
            $authorPlaceholders = [];
            foreach ($authorFilters as $index => $author) {
                $paramName = ':authorFilter' . $index;
                $authorPlaceholders[] = "author = " . $paramName; // Exact match for selected authors
                $params[$paramName] = $author;
            }
            if (!empty($authorPlaceholders)) {
                $conditions[] = "(" . implode(" OR ", $authorPlaceholders) . ")";
            }
        }

        if (!empty($genreFilters)) {
            $genrePlaceholders = [];
            foreach ($genreFilters as $index => $genre) {
                $paramName = ':genreFilter' . $index;
                $genrePlaceholders[] = "genre ILIKE " . $paramName;
                $params[$paramName] = '%' . $genre . '%';
            }
            if (!empty($genrePlaceholders)) {
                $conditions[] = "(" . implode(" OR ", $genrePlaceholders) . ")";
            }
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $sql .= " ORDER BY id desc";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countBooksUsingCover(string $coverImage, int $excludeBookId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM books WHERE cover_image = ? AND id != ?");
        $stmt->execute([$coverImage, $excludeBookId]);
        return (int) $stmt->fetchColumn();
    }

    public function getBookById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM books WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $book = $stmt->fetch(PDO::FETCH_ASSOC);
        return $book ?: null;
    }

    public function getDistinctAuthors(): array
    {
        $stmt = $this->db->query("SELECT DISTINCT author FROM books WHERE author IS NOT NULL AND author != '' ORDER BY author ASC");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    public function updateBook(int $id, array $bookData): bool
    {
        $stmt = $this->db->prepare("UPDATE books SET title = :title, author = :author, genre = :genre, cover_image = :cover_image WHERE id = :id");
        return $stmt->execute([
            ':title' => $bookData['title'],
            ':author' => $bookData['author'],
            ':genre' => $bookData['genre'],
            ':cover_image' => $bookData['cover_image'],
            ':id' => $id
        ]);
    }

    public function getDistinctIndividualGenres(): array
    {
        $stmt = $this->db->query("SELECT genre FROM books WHERE genre IS NOT NULL AND genre != ''");
        $allGenreStrings = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $individualGenres = [];
        foreach ($allGenreStrings as $genreString) {
            $genres = explode(',', $genreString);
            foreach ($genres as $genre) {
                $trimmedGenre = trim($genre);
                if (!empty($trimmedGenre) && !in_array($trimmedGenre, $individualGenres, true)) {
                    $individualGenres[] = $trimmedGenre;
                }
            }
        }
        sort($individualGenres);
        return $individualGenres;
    }

    public function findLibrariesNearby(float $lat, float $lon): array
    {
        $url = "https://nominatim.openstreetmap.org/search?format=json&limit=5&q=library&viewbox="
            . ($lon - 0.05) . ","
            . ($lat + 0.05) . ","
            . ($lon + 0.05) . ","
            . ($lat - 0.05) . "&bounded=1";

        $opts = [
            "http" => [
                "header" => "User-Agent: MyBookApp/1.0\r\n"
            ]
        ];
        $context = stream_context_create($opts);
        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            return [];
        }

        $data = json_decode($response, true);

        $libraries = [];
        if (is_array($data)) {
            foreach ($data as $place) {
                $libraries[] = [
                    'name' => $place['display_name'] ?? 'N/A',
                    'url' => 'https://www.openstreetmap.org/' . ($place['osm_type'] ?? '') . '/' . ($place['osm_id'] ?? '')
                ];
            }
        }

        return $libraries;
    }

    public function deleteBook($bookId): bool
    {
        $stmt = $this->db->prepare("SELECT cover_image FROM books WHERE id = ?");
        $stmt->execute([$bookId]);
        $coverImage = $stmt->fetchColumn();

        if ($coverImage) {
            // Check if any other books use this image
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM books WHERE cover_image = ? AND id != ?");
            $stmt->execute([$coverImage, $bookId]);
            $count = (int) $stmt->fetchColumn();


            if ($count === 0) {
                $imagePath = __DIR__ . '/../assets/' . $coverImage;

                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }
        }

        $stmt = $this->db->prepare("DELETE FROM books WHERE id = ?");
        return $stmt->execute([$bookId]);
    }
    public function insertBook(array $bookData): bool
    {
        $stmt = $this->db->prepare("INSERT INTO books (title, author, genre, cover_image) VALUES (:title, :author, :genre, :cover_image)");
        return $stmt->execute([
            ':title' => $bookData['title'],
            ':author' => $bookData['author'],
            ':genre' => $bookData['genre'],
            ':cover_image' => $bookData['cover_image']
        ]);
    }

    public function getUserProgress(int $userId, int $bookId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT ub.pages_read, ub.review, ub.rating, b.total_pages
            FROM user_book_progress ub
            JOIN books b ON ub.book_id = b.id
            WHERE ub.user_id = ? AND ub.book_id = ?
        ");
        $stmt->execute([$userId, $bookId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    public function getBooksWithUserProgress(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT b.id, b.title, b.author, b.total_pages,
                   ub.pages_read, ub.review, ub.rating, ub.updated_at
            FROM books b
            JOIN user_book_progress ub ON b.id = ub.book_id
            WHERE ub.user_id = ?
            ORDER BY ub.updated_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    public function saveUserProgress(int $userId, int $bookId, int $pagesRead, string $review, int $rating): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO user_book_progress (user_id, book_id, pages_read, review, rating, updated_at)
            VALUES (?, ?, ?, ?, ?, NOW())
            ON CONFLICT (user_id, book_id) DO UPDATE
            SET pages_read = EXCLUDED.pages_read,
                review = EXCLUDED.review,
                rating = EXCLUDED.rating,
                updated_at = NOW()
        ");
        return $stmt->execute([$userId, $bookId, $pagesRead, $review, $rating]);
    }


    public function getAllReviewsForBook(int $bookId): array
    {
        $stmt = $this->db->prepare("
            SELECT p.review, p.rating, p.updated_at, u.users_uid
            FROM user_book_progress p
            JOIN users u ON p.user_id = u.users_id
            WHERE p.book_id = ? AND p.review IS NOT NULL AND TRIM(p.review) <> ''
            ORDER BY p.updated_at DESC
        ");
        $stmt->execute([$bookId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getAverageRatingForBook(int $bookId): ?float
    {
        $stmt = $this->db->prepare("SELECT AVG(rating) AS avg_rating FROM user_book_progress WHERE book_id = ? AND rating IS NOT NULL");
        $stmt->execute([$bookId]);
        $avg = $stmt->fetchColumn();

        // Check if we got a numeric value
        return is_numeric($avg) ? round((float) $avg, 2) : null;
    }
}