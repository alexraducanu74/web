<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{$title}</title>
    <link rel="stylesheet" href="assets/style.css">
    </head>
<body>

<div class="navbar">
  <div class="nav-left">
    <a href="/web/index.php" class="logo">BoW</a>
  </div>

  <div class="search-container">
    <form action="/web/index.php" method="get" class="search-form">
      <input type="hidden" name="controller" value="feed">
      <input type="hidden" name="actiune" value="search">
      <input type="text" name="q" placeholder="Caută o carte..." />
      <button type="submit">🔍</button>
    </form>
  </div>

  <div class="nav-right">
    <div class="secondary-links">
      <a href="/web/assets/rss/anunturi.xml" aria-label="RSS Feed" class="rss-icon">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" >
        <path d="M4 11a9 9 0 0 1 9 9"/>
        <path d="M4 4a16 16 0 0 1 16 16"/>
        <circle cx="5" cy="19" r="1"/>
      </svg>
      </a>
      <a href="progress.php">Progres</a>
      <a href="stats.php">Statistici</a>
      <div class="separator"></div>
    </div>
    <div class="auth-links" id="auth-links">
        <?php 
        if (session_status() === PHP_SESSION_NONE) {
            session_start(); 
        }
        ?>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="index.php?controller=group&actiune=myGroups">My Groups</a>
            <a href="index.php?controller=group&actiune=showCreateForm">Create Group</a>
            <div class="separator"></div>
        <?php endif; ?>
        {$authLinks}
    </div>
  </div>
</div>

<main>
    {$content}
</main>
<script src="assets/js/geolocation.js"></script>
</body>
</html>