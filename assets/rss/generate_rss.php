<?php

$pdo = Dbh::getInstance()->getConnection();

$doc = new DOMDocument("1.0", "UTF-8");
$doc->formatOutput = true;


$rss = $doc->createElement("rss");
$rss->setAttribute("version", "2.0");
$doc->appendChild($rss);

$channel = $doc->createElement("channel");
$channel->appendChild($doc->createElement("title", "Cărți Și Recenzii Adăugate Recent"));
$channel->appendChild($doc->createElement("link", "http://localhost/rss/anunturi.xml")); 
$channel->appendChild($doc->createElement("description", "Cărțile adăugate cel mai recent"));
$channel->appendChild($doc->createElement("language", "ro"));
$channel->appendChild($doc->createElement("pubDate", date(DATE_RSS)));
$rss->appendChild($channel);


$stmt = $pdo->query("SELECT * FROM books ORDER BY created_at DESC");

while ($book = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $item = $doc->createElement("item");

    $titleText = $book['title'];
    $linkText = "http://localhost/web/index.php?controller=feed&actiune=viewBook&parametrii=" . $book['id'];
    $descText = "Autor: " . $book['author'] . " | Gen: " . $book['genre'];

    $item->appendChild($doc->createElement("title", htmlspecialchars($titleText, ENT_XML1, 'UTF-8')));
    $item->appendChild($doc->createElement("link", htmlspecialchars($linkText, ENT_XML1, 'UTF-8')));
    $item->appendChild($doc->createElement("description", htmlspecialchars($descText, ENT_XML1, 'UTF-8')));
    $item->appendChild($doc->createElement("pubDate", date(DATE_RSS, strtotime($book['created_at']))));

    $channel->appendChild($item);
}

$reviewStmt = $pdo->query("
    SELECT p.review, p.pages_read, p.updated_at, b.id as book_id, b.title, u.users_uid 
    FROM user_book_progress p
    JOIN books b ON p.book_id = b.id
    JOIN users u ON p.user_id = u.users_id
    WHERE p.review IS NOT NULL AND TRIM(p.review) <> ''
    ORDER BY p.updated_at DESC
    LIMIT 10
");

while ($review = $reviewStmt->fetch(PDO::FETCH_ASSOC)) {
    $item = $doc->createElement("item");

    $titleText = "Review for: " . $review['title'] . " by " . $review['users_uid'];
    $linkText = "http://localhost/web/index.php?controller=feed&actiune=viewBook&parametrii=" . $review['book_id'];
    $descText = htmlspecialchars($review['review'], ENT_XML1, 'UTF-8') 
            . " | Pages read: " . $review['pages_read'];
    $item->appendChild($doc->createElement("title", htmlspecialchars($titleText, ENT_XML1, 'UTF-8')));
    $item->appendChild($doc->createElement("link", htmlspecialchars($linkText, ENT_XML1, 'UTF-8')));
    $item->appendChild($doc->createElement("description", $descText));
    $item->appendChild($doc->createElement("pubDate", date(DATE_RSS, strtotime($review['updated_at']))));

    $channel->appendChild($item);
}

$xmlPath = __DIR__ . '/../rss/anunturi.xml';
$doc->save($xmlPath);