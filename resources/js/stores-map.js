import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const mapElement = document.querySelector('#stores-map');
const dataElement = document.querySelector('#stores-data');

if (mapElement && dataElement) {
  const stores = JSON.parse(dataElement.textContent || '[]').filter((store) => Number.isFinite(Number(store.latitude)) && Number.isFinite(Number(store.longitude)));
  const map = L.map(mapElement, { zoomControl: true, scrollWheelZoom: false, attributionControl: true }).setView([-34.61, -58.44], 10);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap' }).addTo(map);
  const icon = L.icon({ iconUrl: mapElement.dataset.pin, iconSize: [37, 48], iconAnchor: [19, 43], popupAnchor: [0, -42] });
  const markers = stores.map((store, index) => {
    const marker = L.marker([Number(store.latitude), Number(store.longitude)], { icon, title: store.name || 'Moldpack' }).addTo(map);
    const popup = document.createElement('div');
    popup.className = 'store-popup';
    [store.name, store.address, store.phone, store.email].filter(Boolean).forEach((value, line) => {
      const node = document.createElement(line === 0 ? 'strong' : 'span');
      node.textContent = value;
      popup.appendChild(node);
    });
    marker.bindPopup(popup);
    marker.on('click', () => setActive(index));
    return marker;
  });
  const locationButton = document.querySelector('#stores-use-location');
  const locationStatus = document.querySelector('#stores-location-status');
  const locationResult = document.querySelector('#stores-location-result');
  let userMarker;

  function setActive(index) {
    document.querySelectorAll('[data-store-index]').forEach((entry) => entry.classList.toggle('is-active', Number(entry.dataset.storeIndex) === index));
  }

  document.querySelectorAll('[data-store-index]').forEach((entry) => entry.addEventListener('click', () => {
    const index = Number(entry.dataset.storeIndex);
    const marker = markers[index];
    if (!marker) return;
    setActive(index);
    map.flyTo(marker.getLatLng(), Math.max(map.getZoom(), 13), { duration: .65 });
    marker.openPopup();
  }));

  document.querySelector('.stores-more')?.addEventListener('click', (event) => {
    const hidden = [...document.querySelectorAll('[data-store-index][hidden]')];
    hidden.slice(0, 4).forEach((entry) => { entry.hidden = false; });
    if (hidden.length <= 4 && hidden.length > 0) event.currentTarget.hidden = true;
  });

  const distanceBetween = (from, to) => {
    const radians = (degrees) => degrees * Math.PI / 180;
    const latitudeDelta = radians(to.latitude - from.latitude);
    const longitudeDelta = radians(to.longitude - from.longitude);
    const value = Math.sin(latitudeDelta / 2) ** 2 + Math.cos(radians(from.latitude)) * Math.cos(radians(to.latitude)) * Math.sin(longitudeDelta / 2) ** 2;
    return 6371 * 2 * Math.atan2(Math.sqrt(value), Math.sqrt(1 - value));
  };

  const locationError = (error) => {
    locationButton?.classList.remove('is-loading');
    if (!locationStatus) return;
    locationStatus.textContent = error?.code === 1 ? 'No pudimos acceder a tu ubicación. Podés habilitarla desde el navegador.' : error?.code === 3 ? 'La ubicación tardó demasiado. Volvé a intentarlo.' : 'No pudimos determinar tu ubicación en este momento.';
    locationStatus.classList.add('is-visible', 'is-error');
  };

  locationButton?.addEventListener('click', () => {
    locationStatus.textContent = 'Buscando tu ubicación…';
    locationStatus.classList.add('is-visible');
    locationStatus.classList.remove('is-error');
    locationResult?.classList.remove('is-visible');
    locationResult?.setAttribute('aria-hidden', 'true');
    locationButton.classList.add('is-loading');
    if (!navigator.geolocation) { locationError(); return; }

    navigator.geolocation.getCurrentPosition(({ coords }) => {
      const origin = { latitude: coords.latitude, longitude: coords.longitude };
      const ranked = stores.map((store, index) => ({ store, index, distance: distanceBetween(origin, { latitude: Number(store.latitude), longitude: Number(store.longitude) }) })).sort((a, b) => a.distance - b.distance);
      const nearest = ranked[0];
      locationButton.classList.remove('is-loading');
      if (!nearest) { locationError(); return; }

      if (userMarker) userMarker.remove();
      userMarker = L.marker([origin.latitude, origin.longitude], {
        title: 'Tu ubicación',
        icon: L.divIcon({ className: 'user-location-marker', html: '<span></span>', iconSize: [24, 24], iconAnchor: [12, 12] }),
      }).addTo(map).bindTooltip('Tu ubicación', { direction: 'top', offset: [0, -12] });

      const storeEntry = document.querySelector(`[data-store-index="${nearest.index}"]`);
      if (storeEntry) { storeEntry.hidden = false; storeEntry.scrollIntoView({ behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'nearest' }); }
      setActive(nearest.index);
      const destination = [Number(nearest.store.latitude), Number(nearest.store.longitude)];
      map.flyToBounds([L.latLng(origin.latitude, origin.longitude), L.latLng(destination)], { padding: [70, 70], maxZoom: 12, duration: matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : .85 });

      document.querySelector('#stores-nearest-name').textContent = nearest.store.name || 'Punto de venta Moldpack';
      document.querySelector('#stores-nearest-detail').textContent = `${nearest.distance.toLocaleString('es-AR', { maximumFractionDigits: 1 })} km · ${nearest.store.address || ''}`;
      const route = document.querySelector('#stores-nearest-route');
      route.href = `https://www.google.com/maps/dir/?api=1&origin=${origin.latitude},${origin.longitude}&destination=${destination.join(',')}`;
      locationStatus.textContent = 'Encontramos la mejor opción para vos.';
      locationResult?.setAttribute('aria-hidden', 'false');
      requestAnimationFrame(() => locationResult?.classList.add('is-visible'));
    }, locationError, { enableHighAccuracy: true, timeout: 10000, maximumAge: 300000 });
  });

  if (markers.length > 1) map.fitBounds(L.featureGroup(markers).getBounds(), { padding: [42, 42], maxZoom: 10 });
  else if (markers.length === 1) map.setView(markers[0].getLatLng(), 13);
}
