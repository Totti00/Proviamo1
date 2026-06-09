document.addEventListener('DOMContentLoaded', () => {
  const mapContainer = document.getElementById('reb-map');
  if (mapContainer && window.L) {
    const lat = parseFloat(mapContainer.dataset.lat);
    const lng = parseFloat(mapContainer.dataset.lng);
    if (!Number.isNaN(lat) && !Number.isNaN(lng)) {
      const map = L.map('reb-map').setView([lat, lng], 14);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
      }).addTo(map);
      L.marker([lat, lng]).addTo(map);
    }
  }

  const favoriteButton = document.querySelector('.reb-favorite-btn');
  if (favoriteButton) {
    favoriteButton.addEventListener('click', async () => {
      const form = new URLSearchParams();
      form.append('action', 'reb_toggle_favorite');
      form.append('nonce', rebData.nonce);
      form.append('post_id', favoriteButton.dataset.postId);

      const response = await fetch(rebData.ajaxUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: form.toString()
      });

      const data = await response.json();
      if (data.success) {
        const isFavorite = !!data.data.favorite;
        favoriteButton.setAttribute('aria-pressed', String(isFavorite));
        favoriteButton.textContent = isFavorite ? rebData.removeFavoriteText : rebData.saveFavoriteText;
      } else if (data?.data?.message) {
        window.alert(data.data.message);
      }
    });
  }
});
