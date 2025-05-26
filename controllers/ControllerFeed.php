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
            $this->search($_GET['q'] ?? '');
        } elseif ($actiune == "viewBook" && isset($parametri[0])) {
            $this->viewBook((int) $parametri[0]);
        }
    }

    private function showFeed(): void
    {
        $books = $this->modelFeed->getBooks();
        $this->viewFeed->setBooks($books);
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

    public function search(string $query): void
    {
        $results = $this->modelFeed->getBooks($query);

        if (empty($results)) {
            // No books found, try to get lat/lon from GET params
            $lat = isset($_GET['lat']) ? (float) $_GET['lat'] : 0;
            $lon = isset($_GET['lon']) ? (float) $_GET['lon'] : 0;

            // Fallback coords if none provided (e.g., center of Bucharest)
            if ($lat === 0 && $lon === 0) {
                $lat = 44.4268;
                $lon = 26.1025;
            }

            $libraries = $this->modelFeed->findLibrariesNearby($lat, $lon);

            // Pass libraries to view to display
            $this->viewFeed->setLibraries($libraries);
            $this->viewFeed->setQuery($query);
            $this->viewFeed->renderNoBooks();
        } else {
            $this->viewFeed->setBooks($results);
            $this->viewFeed->setQuery($query);
            $this->viewFeed->render();
        }
    }
}