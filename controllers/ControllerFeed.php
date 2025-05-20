<?php
class ControllerFeed extends Controller
{
    public function __construct($actiune, $parametri)
    {
        parent::__construct();
        if ($actiune == "showFeed") $this->showFeed();
        if ($actiune == "search") $this->search($_GET['q'] ?? '');
        if ($actiune == "viewBook" && isset($parametri[0])) $this->viewBook((int)$parametri[0]);
    }

    private function showFeed(): void
    {
        $model = new ModelFeed();
        $view = new ViewFeed();
    
        $books = $model->getBooks();
        $view->setBooks($books);
        $view->render();
    }
    
    
    private function viewBook(int $id): void
    {
        $model = new ModelFeed();
        $book = $model->getBookById($id);

        if (!$book) {
            echo "Cartea nu a fost găsită.";
            return;
        }

        $view = new ViewFeed();
        $view->renderBook($book);
    }
    
    public function search(string $query): void
    {
        $model = new ModelFeed();
        $view = new ViewFeed();

        $results = $model->getBooks($query);

        if (empty($results)) {
            // No books found, try to get lat/lon from GET params
            $lat = isset($_GET['lat']) ? (float)$_GET['lat'] : 0;
            $lon = isset($_GET['lon']) ? (float)$_GET['lon'] : 0;

            // Fallback coords if none provided (e.g., center of Bucharest)
            if ($lat === 0 && $lon === 0) {
                $lat = 44.4268;
                $lon = 26.1025;
            }

            $libraries = $model->findLibrariesNearby($lat, $lon);

            // Pass libraries to view to display
            $view->setLibraries($libraries);
            $view->setQuery($query);
            $view->renderNoBooks();
        } else {
            $view->setBooks($results);
            $view->setQuery($query);
            $view->render();
        }
    }

}
