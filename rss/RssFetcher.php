<?php
require_once 'FeedItem.php';

class RssFetcher {
    public static function fetch(string $url): array {
        $rss = @simplexml_load_file($url);
        if ($rss === false) return [];

        $items = [];
        foreach ($rss->channel->item as $item) {
            $items[] = new FeedItem(
                (string) $item->title,
                (string) $item->link,
                (string) $item->description,
                (string) $item->pubDate
            );
        }
        return $items;
    }
}
