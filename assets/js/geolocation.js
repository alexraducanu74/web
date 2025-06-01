const urlParams = new URLSearchParams(window.location.search);
if (!urlParams.has('lat') || !urlParams.has('lon')) {
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(position => {
      const lat = position.coords.latitude;
      const lon = position.coords.longitude;
      
      urlParams.set('lat', lat);
      urlParams.set('lon', lon);
      
      if (!urlParams.has('controller')) urlParams.set('controller', 'feed');
      if (!urlParams.has('actiune')) urlParams.set('actiune', 'search');
      
      const newUrl = window.location.pathname + '?' + urlParams.toString();
      window.location.href = newUrl;
    }, error => {
      console.log('Geolocația nu a fost permisă sau eșuată.', error);
    });
  }
}