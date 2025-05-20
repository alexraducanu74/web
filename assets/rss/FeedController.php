<?php
require_once __DIR__ . '/RssFetcher.php';

class FeedController {
    private array $sources = [
        "https://www.nytimes.com/svc/collections/v1/publish/www.nytimes.com/section/books/rss.xml",
        "rss/anunturi.xml",
    ];

    public function getAllFeeds(): array {
        $allItems = [];
        foreach ($this->sources as $url) {
            $items = RssFetcher::fetch($url);
            $allItems = array_merge($allItems, $items);
        }
        return $allItems;
    }
}