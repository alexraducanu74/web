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
        } elseif ($actiune == "ajaxFilterBooks") {
            $this->ajaxFilterBooks();
        } else {
            $this->handleFeedDisplay('', [], []);
        }
    }

    private function handleFeedDisplay(string $query, array $authorFilter, array $genreFilter): void
    {
        $books = $this->modelFeed->getBooks($query, $authorFilter, $genreFilter);
        $allAuthors = $this->modelFeed->getDistinctAuthors();
        $allGenres = $this->modelFeed->getDistinctIndividualGenres();

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
        $this->viewFeed->renderBook($book);
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