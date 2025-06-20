<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title}</title>
    <link rel="stylesheet" href="assets/style.css">
    </head>
<body>

<div class="navbar">
  <div class="nav-left">
    <a href="/web/index.php" class="logo">BoW</a>
    <button class="menu-toggle" id="menu-toggle" aria-label="Toggle navigation" >
      <span class="hamburger"></span>
      <span class="hamburger"></span>
      <span class="hamburger"></span>
    </button>
  </div>

  <div class="search-container">
    <form action="/web/index.php" method="get" class="search-form">
      <input type="hidden" name="controller" value="feed">
      <input type="hidden" name="actiune" value="search">
      <input type="text" name="q" placeholder="Cauta o carte...">
      <button type="submit">🔍</button>
    </form>
  </div>

  <div class="nav-right" id="nav-links">
    <div class="secondary-links">
      <a href="index.php?controller=stats&actiune=exportCSV">Export CSV</a>
      <div class="separator"></div>
      <a href="index.php/web/views/doc.html">doc</a>
      <div class="separator"></div>
      <a href="index.php?controller=stats&actiune=exportDocBook">Export DocBook</a>
      <div class="separator"></div>
      <a href="/web/index.php?controller=feed&actiune=genereazaRssApi&api=1" aria-label="RSS Feed" class="rss-icon">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" >
        <path d="M4 11a9 9 0 0 1 9 9"/>
        <path d="M4 4a16 16 0 0 1 16 16"/>
        <circle cx="5" cy="19" r="1"/>
      </svg>
      </a>
      <div class="separator"></div>
    </div>
    <div class="auth-links" id="auth-links">
        {$authLinks}
    </div>
  </div>
</div>

<main>
    {$content}
</main>
</body>
</html>