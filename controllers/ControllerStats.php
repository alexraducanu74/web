<?php


class ControllerStats
{
    private $model;
    private $actiune;
    private $params;

    public function __construct($actiune = '', $params = [])
    {
        $this->actiune = $actiune;
        $this->params = $params;

        $db = Dbh::getInstance()->getConnection();
        $this->model = new ModelStats($db);

        switch ($this->actiune) {
            case 'exportCSV':
                $this->exportCSV();
                break;
            case 'exportDocBook':
                $this->exportDocBook();
                break;
            default:
                echo "Actiune invalida.";
        }
    }

    public function exportCSV()
    {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename="stats.csv"');

        $output = fopen('php://output', 'w');

        fputcsv($output, ['Cele mai citite carti']);
        fputcsv($output, ['Titlu', 'Pagini citite']);
        foreach ($this->model->getMostReadBooks() as $row) {
            fputcsv($output, $row);
        }

        fputcsv($output, []);

        fputcsv($output, ['Genuri populare']);
        fputcsv($output, ['Gen', 'Numar carti']);
        foreach ($this->model->getPopularGenres() as $row) {
            fputcsv($output, $row);
        }

        fputcsv($output, []);

        fputcsv($output, ['Carti cu cel mai bun rating mediu']);
        fputcsv($output, ['Titlu', 'Rating Mediu']);
        foreach ($this->model->getTopRatedBooks() as $row) {
            fputcsv($output, $row);
        }

        fputcsv($output, []);
        fputcsv($output, ['Numar total utilizatori care au inceput o carte']);
        fputcsv($output, [$this->model->getUserBookStartCount()]);

        fclose($output);
        exit;
    }

    public function exportDocBook()
    {
        header('Content-Type: application/xml');
        header('Content-Disposition: attachment;filename="stats.xml"');

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        // Radacina DocBook
        $book = $dom->createElementNS('http://docbook.org/ns/docbook', 'book');
        $book->setAttribute('version', '5.0');
        $dom->appendChild($book);

        // Titlu principal
        $title = $dom->createElement('title', 'Statistici Lectura');
        $book->appendChild($title);

        // Cele mai citite carti 
        $chapter1 = $dom->createElement('chapter');
        $chapter1->appendChild($dom->createElement('title', 'Cele mai citite carti'));
        foreach ($this->model->getMostReadBooks() as $bookData) {
            $para = $dom->createElement('para', "{$bookData['title']} - {$bookData['total_pages']} pagini");
            $chapter1->appendChild($para);
        }
        $book->appendChild($chapter1);

        // Genuri populare 
        $chapter2 = $dom->createElement('chapter');
        $chapter2->appendChild($dom->createElement('title', 'Genuri populare'));
        foreach ($this->model->getPopularGenres() as $genre) {
            $para = $dom->createElement('para', "{$genre['genre']} - {$genre['count']} carti");
            $chapter2->appendChild($para);
        }
        $book->appendChild($chapter2);

        // Carti cu cel mai bun rating mediu 
        $chapter3 = $dom->createElement('chapter');
        $chapter3->appendChild($dom->createElement('title', 'Carti cu cel mai bun rating mediu'));
        foreach ($this->model->getTopRatedBooks() as $bookData) {
            $para = $dom->createElement('para', "{$bookData['title']} - Rating: {$bookData['avg_rating']}");
            $chapter3->appendChild($para);
        }
        $book->appendChild($chapter3);

        // 4. Numar total utilizatori care au inceput o carte 
        $chapter4 = $dom->createElement('chapter');
        $chapter4->appendChild($dom->createElement('title', 'Numar total utilizatori care au inceput o carte'));
        $count = $this->model->getUserBookStartCount();
        $chapter4->appendChild($dom->createElement('para', $count));
        $book->appendChild($chapter4);

        echo $dom->saveXML();
        exit;
    }

}
