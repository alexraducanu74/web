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
    <div class="auth-links">
      <a href="/web/RegisterAndLogin/login.php">Login</a>
      <a href="/web/RegisterAndLogin/register.php">Register</a>
    </div>
  </div>
</div>



<main>
    {$content}
</main>

</body>
</html>
<script>
  document.addEventListener('DOMContentLoaded', () => {
    // Dacă URL nu are lat/lon
    const urlParams = new URLSearchParams(window.location.search);
    if (!urlParams.has('lat') || !urlParams.has('lon')) {
      if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(position => {
          const lat = position.coords.latitude;
          const lon = position.coords.longitude;

          // Construim URL nou cu parametrii
          urlParams.set('lat', lat);
          urlParams.set('lon', lon);

          // Adăugăm controller și actiune dacă nu sunt prezente (exemplu)
          if (!urlParams.has('controller')) urlParams.set('controller', 'feed');
          if (!urlParams.has('actiune')) urlParams.set('actiune', 'search');

          const newUrl = window.location.pathname + '?' + urlParams.toString();
          window.location.href = newUrl;
        }, error => {
          console.log('Geolocația nu a fost permisă sau eșuată.', error);
          // Dacă vrei, poți afișa mesaj aici sau continua fără locație
        });
      }
    }
  });
</script>
