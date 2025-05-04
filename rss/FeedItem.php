<?php
class FeedItem {
    public string $title;
    public string $link;
    public string $description;
    public string $pubDate;

    public function __construct($title, $link, $description, $pubDate) {
        $this->title = $title;
        $this->link = $link;
        $this->description = strip_tags($description);
        $this->pubDate = date("d.m.Y H:i", strtotime($pubDate));
    }
}
