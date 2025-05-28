<?php
class ModelFeed
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Dbh::getInstance()->getConnection();
    }

    public function getBooks(string $generalQuery = '', ?string $authorFilter = null, ?string $genreFilter = null): array
    {
        $sql = "SELECT * FROM books";
        $conditions = [];
        $params = [];

        if (!empty($generalQuery)) {
            $conditions[] = "(title LIKE :generalQuery OR author LIKE :generalQueryGeneral)";
            $params[':generalQuery'] = '%' . $generalQuery . '%';
            $params[':generalQueryGeneral'] = '%' . $generalQuery . '%';
        }

        if (!empty($authorFilter)) {
            $conditions[] = "author LIKE :authorFilter";
            $params[':authorFilter'] = '%' . $authorFilter . '%';
        }

        if (!empty($genreFilter)) {
            $conditions[] = "genre LIKE :genreFilter";
            $params[':genreFilter'] = '%' . $genreFilter . '%';
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $sql .= " ORDER BY title ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getBookById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM books WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $book = $stmt->fetch(PDO::FETCH_ASSOC);
        return $book ?: null;
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
        $response = @file_get_contents($url, false, $context); // Added @ to suppress warnings on failure

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
}