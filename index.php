<?php
require_once 'rss/FeedController.php';
$controller = new FeedController();
$items = $controller->getAllFeeds();
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Flux de Știri Cărți</title>
    <link rel="stylesheet" href="/web/style.css">
</head>
<body>
<?php include './nav/navbar.php';?>
    <div class="container">
        <h1>Flux de Știri RSS despre Cărți</h1>
        <?php foreach ($items as $item): ?>
            <div class="feed-item">
            <h2><a href="<?= htmlspecialchars($item->link) ?>" target="_blank"><?= htmlspecialchars($item->title) ?></a></h2>
            <p><?= htmlspecialchars($item->description) ?></p>
            <time><?= $item->pubDate ?></time>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>