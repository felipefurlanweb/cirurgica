<?php
include_once 'header.php';
include_once 'conexao.php';

$motoristas = $conn->query("SELECT id, nome FROM motoristas ORDER BY nome")->fetch_all(MYSQLI_ASSOC);
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<style>
  #map { height: 520px; }
</style>

<h4 class="mb-3">Mapa por Motorista</h4>

<div class="row mb-3">
  <div class="col-md-6 d-flex align-items-center gap-2">
    <select id="selectMotorista" class="form-select">
      <option value="">Selecione o motorista...</option>
      <?php foreach ($motoristas as $m): ?>
        <option value="<?= (int)$m['id'] ?>"><?= htmlspecialchars($m['nome']) ?></option>
      <?php endforeach; ?>
    </select>

    <div id="spinner" class="spinner-border text-primary" role="status" style="display:none;">
      <span class="visually-hidden">Carregando...</span>
    </div>
  </div>
</div>

<div id="map"></div>

<div class="text-end mt-4">
  <a href="index.php" class="btn btn-primary">Voltar</a>
</div>

<script>
  // ====== MAP INIT ======
  const mapa = L.map('map').setView([-15, -55], 4);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
  }).addTo(mapa);

  // Zoom confortável
  const DEFAULT_ZOOM_SINGLE = 9;
  const MAX_FIT_ZOOM       = 9;
  const FIT_PADDING        = [30, 30];

  // Ícones por status
  const iconBlue = L.icon({ iconUrl: 'https://maps.google.com/mapfiles/ms/icons/blue-dot.png',   iconSize:[32,32], iconAnchor:[16,32], popupAnchor:[0,-32] });
  const iconRed  = L.icon({ iconUrl: 'https://maps.google.com/mapfiles/ms/icons/red-dot.png',    iconSize:[32,32], iconAnchor:[16,32], popupAnchor:[0,-32] });
  const iconOrg  = L.icon({ iconUrl: 'https://maps.google.com/mapfiles/ms/icons/orange-dot.png', iconSize:[32,32], iconAnchor:[16,32], popupAnchor:[0,-32] });

  function getMarkerIcon(urgente, coleta) {
    if (urgente === 'sim') return iconRed;
    if (coleta  === 'sim') return iconOrg;
    return iconBlue;
  }

  const spinner = document.getElementById('spinner');
  let markers = [];

  document.getElementById('selectMotorista').addEventListener('change', buscarViagens);

  async function buscarViagens() {
    const motoristaId = document.getElementById('selectMotorista').value;
    if (!motoristaId) return;

    spinner.style.display = 'inline-block';

    // Limpa marcadores anteriores
    markers.forEach(m => mapa.removeLayer(m));
    markers = [];

    try {
      const res = await fetch(`ajax_viagens_motorista.php?id=${encodeURIComponent(motoristaId)}`);
      const viagens = await res.json();

      for (const v of viagens) {
        // precisa ter lat/lon persistido na cidade
        if (!v.latitude || !v.longitude) continue;

        const lat = parseFloat(v.latitude);
        const lon = parseFloat(v.longitude);
        const icon = getMarkerIcon(v.urgente, v.coleta);

        const marker = L.marker([lat, lon], { icon }).addTo(mapa)
          .bindPopup(`
            <strong>${escapeHtml(v.nome)} (${escapeHtml(v.uf)})</strong><br>
            ${v.urgente === 'sim' ? '<span class="badge bg-danger">Urgente</span> ' : ''}
            ${v.coleta  === 'sim' ? '<span class="badge bg-warning text-dark">Coleta</span>' : ''}
          `);
        markers.push(marker);
      }

      if (markers.length === 1) {
        mapa.setView(markers[0].getLatLng(), DEFAULT_ZOOM_SINGLE);
      } else if (markers.length > 1) {
        const group = new L.featureGroup(markers);
        mapa.fitBounds(group.getBounds(), { padding: FIT_PADDING, maxZoom: MAX_FIT_ZOOM });
      } else {
        // Sem pontos: centraliza no BR
        mapa.setView([-15, -55], 4);
      }
    } catch (err) {
      alert("Erro ao buscar viagens.");
    } finally {
      spinner.style.display = 'none';
    }
  }

  // helper
  function escapeHtml(str){
    return String(str ?? '').replace(/[&<>"'`=\/]/g, s => ({
      '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;','/':'&#x2F;','`':'&#x60;','=':'&#x3D;'
    }[s]));
  }
</script>

<?php include_once 'footer.php'; ?>
