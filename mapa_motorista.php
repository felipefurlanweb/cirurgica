<?php
include_once 'header.php';
include_once 'conexao.php';

// ID do motorista selecionado
$motorista_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Buscar todos os motoristas (para o select)
$todos_motoristas = $conn->query("SELECT id, nome FROM motoristas ORDER BY nome");

// Dados do motorista e suas cidades (com lat/lon se houver)
$cidades = [];
$motorista_nome = "";
if ($motorista_id > 0) {
    // Nome do motorista
    if ($stmt_nome = $conn->prepare("SELECT nome FROM motoristas WHERE id = ?")) {
        $stmt_nome->bind_param("i", $motorista_id);
        $stmt_nome->execute();
        $result_nome = $stmt_nome->get_result();
        if ($row_nome = $result_nome->fetch_assoc()) {
            $motorista_nome = $row_nome['nome'];
        }
        $stmt_nome->close();
    }

    // Cidades atribuídas a esse motorista
    // Traz também lat/lon para evitar geocodificar quando existir
    $sql = "
        SELECT 
          c.id AS cidade_id,
          c.nome,
          e.uf,
          c.latitude,
          c.longitude
        FROM viagens v
        JOIN cidades c ON v.cidade_id = c.id
        JOIN estado  e ON c.uf = e.id
        WHERE v.motorista_id = ?
        GROUP BY c.id, c.nome, e.uf, c.latitude, c.longitude
        ORDER BY v.id ASC
    ";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $motorista_id);
        $stmt->execute();
        $cidades = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }

    $rioClaro = $conn->query("
      SELECT
          c.id AS cidade_id,
          c.nome,
          e.uf,
          c.latitude,
          c.longitude 
      FROM cidades c
      JOIN estado e ON e.id = c.uf
      WHERE c.nome = 'Rio Claro' AND e.uf = 'SP'
      LIMIT 1
    ")->fetch_assoc();

    if ($rioClaro) {
      // só adiciona se ainda não existir no array
      $jaExiste = false;
      foreach ($cidades as $c) {
        if ($c['nome'] === $rioClaro['nome'] && $c['uf'] === $rioClaro['uf']) {
          $jaExiste = true;
          break;
        }
      }
      if (!$jaExiste) {
        $cidades[] = $rioClaro;
      }
    }

}
?>

<!-- Leaflet -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<h4 class="mb-3">Visualizar Trajeto de Motoristas</h4>

<form method="get" class="row g-3 mb-4">
  <div class="col-md-6">
    <select name="id" class="form-select" onchange="this.form.submit()">
      <option value="">Selecione um motorista</option>
      <?php if ($todos_motoristas): ?>
        <?php while ($m = $todos_motoristas->fetch_assoc()): ?>
          <option value="<?= (int)$m['id'] ?>" <?= $motorista_id == $m['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($m['nome']) ?>
          </option>
        <?php endwhile; ?>
      <?php endif; ?>
    </select>
  </div>
  <div class="col-md-6 text-end">
      <a href="index.php" class="btn btn-primary">Voltar</a>
  </div>
</form>

<?php if ($motorista_id): ?>
  <div class="card shadow-lg mb-4">
    <div class="card-body">
      <div class="row">
        <div class="col-md-9">
          <div id="map" style="height: 520px;"></div>
        </div>
        <div class="col-md-3">
          <h6>Cidades atribuídas a <strong><?= htmlspecialchars($motorista_nome) ?></strong>:</h6>
          <ul id="listaCidades" class="list-group">
            <?php if (empty($cidades)): ?>
              <li class="list-group-item">Nenhuma cidade atribuída para este motorista.</li>
            <?php else: ?>
              <?php foreach ($cidades as $cidade): ?>
                <?php if ($cidade["nome"] == "Rio Claro" && $cidade["uf"] == "SP") continue; // Ignora Rio Claro SP ?>
                <li class="list-group-item d-flex justify-content-between align-items-center"
                    data-cidade-id="<?= (int)$cidade['cidade_id'] ?>">
                  <span><?= htmlspecialchars($cidade['nome']) ?> (<?= htmlspecialchars($cidade['uf']) ?>)</span>
                  <button class="btn btn-sm btn-outline-danger"
                          onclick="removerCidade(<?= (int)$cidade['cidade_id'] ?>, '<?= htmlspecialchars($cidade['nome']) ?> (<?= htmlspecialchars($cidade['uf']) ?>)')">
                    Remover
                  </button>
                </li>
              <?php endforeach; ?>
            <?php endif; ?>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Config de zoom confortável
    const DEFAULT_ZOOM_SINGLE = 9;
    const MAX_FIT_ZOOM       = 9;
    const FIT_PADDING        = [30, 30];

    const motoristaId = <?= (int)$motorista_id ?>;
    const cidades = <?= json_encode($cidades, JSON_UNESCAPED_UNICODE) ?>;

    const iconGreen = L.icon({ iconUrl: 'https://maps.google.com/mapfiles/ms/icons/green-dot.png',   iconSize:[32,32], iconAnchor:[16,32], popupAnchor:[0,-32] });
    const iconBlue = L.icon({ iconUrl: 'https://maps.google.com/mapfiles/ms/icons/blue-dot.png',   iconSize:[32,32], iconAnchor:[16,32], popupAnchor:[0,-32] });
    const iconRed  = L.icon({ iconUrl: 'https://maps.google.com/mapfiles/ms/icons/red-dot.png',    iconSize:[32,32], iconAnchor:[16,32], popupAnchor:[0,-32] });
    const iconOrg  = L.icon({ iconUrl: 'https://maps.google.com/mapfiles/ms/icons/orange-dot.png', iconSize:[32,32], iconAnchor:[16,32], popupAnchor:[0,-32] });

    const mapa = L.map('map').setView([-15, -55], 4);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(mapa);

    let coords = [];
    const markersByCidade = {}; // cidade_id -> marker

    async function geocodeCidade(nome, uf) {
      const res = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(nome + ', ' + uf + ', Brasil')}`);
      const data = await res.json();
      if (data[0]) {
        return [parseFloat(data[0].lat), parseFloat(data[0].lon)];
      }
      return null;
    }

    (async () => {
      for (const c of cidades) {
        let lat = c.latitude ? parseFloat(c.latitude) : null;
        let lon = c.longitude ? parseFloat(c.longitude) : null;

        if ((lat === null || isNaN(lat)) || (lon === null || isNaN(lon))) {
          const gl = await geocodeCidade(c.nome, c.uf);
          if (gl) { lat = gl[0]; lon = gl[1]; }
        }

        if (lat != null && lon != null) {
          coords.push([lat, lon]);

          if(c.nome == "Rio Claro" && c.uf == "SP") {
            const mk = L.marker([lat, lon], { icon: iconGreen }).addTo(mapa).bindPopup(`${c.nome} (${c.uf})`);
            markersByCidade[c.cidade_id] = mk;
          } else {
            // Use iconBlue for others
            const mk = L.marker([lat, lon], { icon: iconBlue }).addTo(mapa).bindPopup(`${c.nome} (${c.uf})`);
            markersByCidade[c.cidade_id] = mk;
          }
        }
      }

      if (coords.length === 1) {
        mapa.setView(coords[0], DEFAULT_ZOOM_SINGLE);
      } else if (coords.length > 1) {
        const group = new L.featureGroup(Object.values(markersByCidade));
        mapa.fitBounds(group.getBounds(), { padding: FIT_PADDING, maxZoom: MAX_FIT_ZOOM });
      }
    })();

    async function removerCidade(cidadeId, nomeExibicao) {
      if (!confirm(`Remover "${nomeExibicao}" deste motorista?`)) return;

      try {
        const res = await fetch('ajax_remover_cidade_motorista.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
          body: new URLSearchParams({ motorista_id: motoristaId, cidade_id: cidadeId })
        });
        const json = await res.json();

        if (!json.success) {
          alert('Não foi possível remover. ' + (json.error || ''));
          return;
        }

        // Remover item da lista
        const li = document.querySelector(`li[data-cidade-id="${cidadeId}"]`);
        if (li) li.remove();

        // Remover marker e refazer bounds
        if (markersByCidade[cidadeId]) {
          mapa.removeLayer(markersByCidade[cidadeId]);
          delete markersByCidade[cidadeId];
        }
        const restantes = Object.values(markersByCidade);
        if (restantes.length === 1) {
          mapa.setView(restantes[0].getLatLng(), DEFAULT_ZOOM_SINGLE);
        } else if (restantes.length > 1) {
          const group = new L.featureGroup(restantes);
          mapa.fitBounds(group.getBounds(), { padding: FIT_PADDING, maxZoom: MAX_FIT_ZOOM });
        } else {
          mapa.setView([-15, -55], 4);
        }

        // alert('Cidade removida deste motorista com sucesso!');
      } catch (e) {
        alert('Erro ao comunicar com o servidor.');
      }
    }
  </script>
<?php endif; ?>

<?php include_once 'footer.php'; ?>
