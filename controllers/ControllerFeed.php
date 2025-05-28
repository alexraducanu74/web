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
        if ($actiune == "showFeed") {
            $this->showFeed();
        } elseif ($actiune == "search") {
            $author = $_GET['author'] ?? null;
            $genre = $_GET['genre'] ?? null;
            $this->search($_GET['q'] ?? '', $author, $genre);
        } elseif ($actiune == "viewBook" && isset($parametri[0])) {
            $this->viewBook((int) $parametri[0]);
        } elseif ($actiune == "ajaxFilterBooks") {
            $this->ajaxFilterBooks();
        }
    }
    private function showFeed(): void
    {
        $authorFilter = $_GET['author'] ?? null;
        $genreFilter = $_GET['genre'] ?? null;
        $books = $this->modelFeed->getBooks('', $authorFilter, $genreFilter);
        $this->viewFeed->setBooks($books);
        $this->viewFeed->setCurrentFilters($authorFilter, $genreFilter);
        $this->viewFeed->render();
    }
    private function viewBook(int $id): void
    {
        $book = $this->modelFeed->getBookById($id);
        if (!$book) {
            echo "Cartea nu a fost găsită.";
            return;
        }
        $this->viewFeed->renderBook($book);
    }
    public function search(string $query, ?string $authorFilter = null, ?string $genreFilter = null): void
    {
        $results = $this->modelFeed->getBooks($query, $authorFilter, $genreFilter);
        if (empty($results)) {
            $lat = isset($_GET['lat']) ? (float) $_GET['lat'] : 0;
            $lon = isset($_GET['lon']) ? (float) $_GET['lon'] : 0;
            if ($lat === 0 && $lon === 0) {
                $lat = 44.4268;
                $lon = 26.1025;
            }
            $libraries = $this->modelFeed->findLibrariesNearby($lat, $lon);
            $this->viewFeed->setLibraries($libraries);
            $this->viewFeed->setQuery($query);
            $this->viewFeed->setCurrentFilters($authorFilter, $genreFilter);
            $this->viewFeed->renderNoBooks();
        } else {
            $this->viewFeed->setBooks($results);
            $this->viewFeed->setQuery($query);
            $this->viewFeed->setCurrentFilters($authorFilter, $genreFilter);
            $this->viewFeed->render();
        }
    }
    private function ajaxFilterBooks(): void
    {
        header('Content-Type: text/html');
        $authorFilter = $_GET['author'] ?? null;
        $genreFilter = $_GET['genre'] ?? null;
        $generalQuery = $_GET['q'] ?? '';
        $books = $this->modelFeed->getBooks($generalQuery, $authorFilter, $genreFilter);
        echo $this->viewFeed->renderBookItems($books);
        exit;
    }
}