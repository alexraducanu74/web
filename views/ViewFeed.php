<?php
class ViewFeed
{
    private array $books = [];
    private string $query = '';

    public function setBooks(array $books): void
    {
        $this->books = $books;
    }

    public function setQuery(string $query): void
    {
        $this->query = $query;
    }

        public function render(): void
        {
            $items = '';
            foreach ($this->books as $book) {
                $items .= "
                <a href='index.php?controller=feed&actiune=viewBook&parametrii={$book['id']}'>
                    <div class='book'>
                        <img src='assets/{$book['cover_image']}' alt='Cover of {$book['title']}'>
                        <h3>{$book['title']}</h3>
                        <p>by {$book['author']}</p>
                    </div>
                </a>
            ";
            }

            $header = $this->query
                ? "<h2>Rezultate pentru „" . htmlspecialchars($this->query) . "”</h2>"
                : "<h2>Browse Books</h2>";

            $content = $this->loadTemplate('views/feed.tpl', [
                'header' => $header,
                'books' => $items
            ]);

            $layout = $this->loadTemplate('views/layout.tpl', [
                'title' => 'Browse Books',
                'content' => $content
            ]);

            echo $layout;
        }

    public function renderBook(array $book): void
    {
        $bookHtml = "
            <div class='book-detail'>
                <img src='assets/{$book['cover_image']}' alt='Cover of {$book['title']}'>
                <h2>{$book['title']}</h2>
                <p><strong>Author:</strong> {$book['author']}</p>
            </div>
        ";

        $content = $this->loadTemplate('views/book.tpl', ['book' => $bookHtml]);
        $layout = $this->loadTemplate('views/layout.tpl', [
            'title' => $book['title'],
            'content' => $content
        ]);

        echo $layout;
    }

    private function loadTemplate(string $filePath, array $data): string
    {
        $template = file_get_contents($filePath);
        foreach ($data as $key => $value) {
            $template = str_replace('{$' . $key . '}', $value, $template);
        }
        return $template;
    }

    private array $libraries = [];

    public function setLibraries(array $libraries): void
    {
        $this->libraries = $libraries;
    }
    public function renderNoBooks(): void
    {
        $libsHtml = '<h3>Nu am găsit cărți, dar poți vizita aceste biblioteci publice din proximitate:</h3>';

        if (empty($this->libraries)) {
            $libsHtml .= "<p>Ne pare rău, nu am putut găsi biblioteci în proximitate.</p>";
        } else {
            $libsHtml .= "<ul>";
            foreach ($this->libraries as $lib) {
                $name = htmlspecialchars($lib['name']);
                $url = htmlspecialchars($lib['url']);
                $libsHtml .= "<li><a href='$url' target='_blank' rel='noopener noreferrer'>$name</a></li>";
            }
            $libsHtml .= "</ul>";
        }

        $content = $this->loadTemplate('views/no-books.tpl', [
            'libraries' => $libsHtml,
            'query' => htmlspecialchars($this->query)
        ]);

        $layout = $this->loadTemplate('views/layout.tpl', [
            'title' => 'Nici o carte găsită',
            'content' => $content
        ]);

        echo $layout;
    }


}
