<?php
include_once 'header.php';
include_once 'conexao.php';

// Motoristas para o popup
$motoristas = $conn->query("SELECT id, nome FROM motoristas ORDER BY nome")->fetch_all(MYSQLI_ASSOC);

// Cidades mapeadas + ÚLTIMA viagem por cidade (motorista_id/nome, urgente, coleta)
$cidadesMapeadas = $conn->query("
  SELECT
    c.id,
    c.nome,
    e.uf,
    c.latitude,
    c.longitude,
    v.motorista_id,
    m.nome AS motorista_nome,
    v.urgente,
    v.coleta
  FROM cidades c
  JOIN estado e ON e.id = c.uf
  LEFT JOIN (
    SELECT vv.*
    FROM viagens vv
    JOIN (SELECT cidade_id, MAX(id) AS max_id FROM viagens GROUP BY cidade_id) ult
      ON ult.max_id = vv.id
  ) v ON v.cidade_id = c.id
  LEFT JOIN motoristas m ON m.id = v.motorista_id
  WHERE c.latitude IS NOT NULL 
    AND c.longitude IS NOT NULL
    AND (v.motorista_id IS NULL OR v.motorista_id = 0)
  ORDER BY c.nome
")->fetch_all(MYSQLI_ASSOC);

$rioClaro = $conn->query("
  SELECT
    c.id,
    c.nome,
    e.uf,
    c.latitude,
    c.longitude,
    NULL AS motorista_id,
    NULL AS motorista_nome,
    'nao' AS urgente,
    'nao' AS coleta
  FROM cidades c
  JOIN estado e ON e.id = c.uf
  WHERE c.nome = 'Rio Claro' AND e.uf = 'SP'
  LIMIT 1
")->fetch_assoc();

if ($rioClaro) {
  // só adiciona se ainda não existir no array
  $jaExiste = false;
  foreach ($cidadesMapeadas as $c) {
    if ($c['nome'] === $rioClaro['nome'] && $c['uf'] === $rioClaro['uf']) {
      $jaExiste = true;
      break;
    }
  }
  if (!$jaExiste) {
    $cidadesMapeadas[] = $rioClaro;
  }
}

?>

<!-- Leaflet -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<!-- Select2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css"/>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.6.2/dist/select2-bootstrap4.min.css"/>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.full.min.js"></script>

<style>
  #map { height: 520px; width: 100%; }
  .map-container { display: flex; gap: 20px; }
  .cidade-lista {
    width: 380px; max-height: 520px; overflow-y: auto;
    border: 1px solid #dee2e6; background: #f8f9fa; padding: 12px; border-radius: .5rem;
  }
  .select2-container--bootstrap4 .select2-selection--single {
    height: 38px; padding: .375rem .75rem;
  }
</style>

<h4 class="mb-3">Mapa de Cidades</h4>

<div class="d-flex align-items-center gap-2 mb-3">
  <select id="selectCidade" class="form-select" style="min-width:420px"></select>

  <button id="btnLimpar" type="button" class="btn btn-outline-secondary">Limpar mapa</button>

  <div id="spinner" class="spinner-border text-primary" role="status" style="display:none;">
    <span class="visually-hidden">Carregando...</span>
  </div>
</div>

<div class="map-container mb-4">
  <div style="flex:1;"><div id="map"></div></div>
  <div class="cidade-lista">
    <h6 class="mb-2">Cidades no mapa:</h6>
    <ul id="listaCidades" class="list-group list-group-flush mb-0"></ul>
  </div>
</div>

<div class="text-end mt-4">
  <a href="index.php" class="btn btn-primary">Voltar</a>
</div>

<script>

  const DEFAULT_ZOOM_SINGLE = 11;   // zoom quando houver 1 marker
  const MAX_FIT_ZOOM       = 11;   // zoom máximo ao encaixar vários
  const FIT_PADDING        = [30, 30]; // padding nas bordas

  // ====== MAP INIT ======
  const mapa = L.map('map').setView([-15, -55], 4);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap contributors' }).addTo(mapa);

  // Ícones por status
  const iconGreen = L.icon({ iconUrl: 'https://maps.google.com/mapfiles/ms/icons/green-dot.png',   iconSize:[32,32], iconAnchor:[16,32], popupAnchor:[0,-32] });
  const iconBlue = L.icon({ iconUrl: 'https://maps.google.com/mapfiles/ms/icons/blue-dot.png',   iconSize:[32,32], iconAnchor:[16,32], popupAnchor:[0,-32] });
  const iconRed  = L.icon({ iconUrl: 'https://maps.google.com/mapfiles/ms/icons/red-dot.png',    iconSize:[32,32], iconAnchor:[16,32], popupAnchor:[0,-32] });
  const iconOrg  = L.icon({ iconUrl: 'https://maps.google.com/mapfiles/ms/icons/orange-dot.png', iconSize:[32,32], iconAnchor:[16,32], popupAnchor:[0,-32] });

  function getMarkerIcon(urgente, coleta) {
    if (urgente === 'sim') return iconRed;
    if (coleta  === 'sim') return iconOrg;
    return iconBlue;
  }

  const lista   = document.getElementById('listaCidades');
  const spinner = document.getElementById('spinner');

  // chave "Nome,UF" => marker
  const markerCidade = {};
  // Memória por cidade para pré-preencher popup e badges
  // chave -> { motorista_id, motorista_nome, urgente, coleta }
  const infoCidade = {};
  // Último motorista usado (para acelerar workflow)
  let ultimoMotoristaId = null;

  const motoristas = <?= json_encode($motoristas, JSON_UNESCAPED_UNICODE) ?>;
  const cidadesPersistidas = <?= json_encode($cidadesMapeadas, JSON_UNESCAPED_UNICODE) ?>;

  // ====== LOAD PERSISTED MARKERS ======
  const markersArray = [];
  cidadesPersistidas.forEach(c => {
    const chave = `${c.nome},${c.uf}`;
    const lat = parseFloat(c.latitude), lon = parseFloat(c.longitude);

    // guarda info da última viagem (se houver)
    infoCidade[chave] = {
      motorista_id: c.motorista_id ? String(c.motorista_id) : null,
      motorista_nome: c.motorista_nome || null,
      urgente: c.urgente || 'nao',
      coleta:  c.coleta  || 'nao',
      nome: c.nome,
    };

    let icone = getMarkerIcon(infoCidade[chave].urgente, infoCidade[chave].coleta);
    if (infoCidade[chave].nome == "Rio Claro"){
      icone = iconGreen; // Rio Claro sempre verde (sem viagem)
    }

    const marker = L.marker([lat, lon], { icon: icone }).addTo(mapa);
    markerCidade[chave] = marker;
    markersArray.push(marker);

    if (infoCidade[chave].nome != "Rio Claro"){
      marker.on('click', () => abrirPopupCadastro(marker, chave));

      const urgBadge = (infoCidade[chave].urgente === 'sim') ? ' <span class="badge bg-danger ms-1">Urgente</span>' : '';
      const colBadge = (infoCidade[chave].coleta  === 'sim') ? ' <span class="badge bg-warning text-dark ms-1">Coleta</span>' : '';
      const motTxt   = infoCidade[chave].motorista_nome ? ` — <strong>${escapeHtml(infoCidade[chave].motorista_nome)}</strong>` : '';

      const li = document.createElement('li');
      li.className = "list-group-item d-flex justify-content-between align-items-center";
      li.dataset.value = chave;
      li.innerHTML = `
        <span>${escapeHtml(chave)}${motTxt}${urgBadge}${colBadge}</span>
        <button class="btn btn-sm btn-outline-danger btn-remove" data-value="${escapeHtml(chave)}">&times;</button>
      `;
      lista.appendChild(li); 
    }

  });

  if (markersArray.length === 1) {
    const only = markersArray[0].getLatLng();
    mapa.setView(only, DEFAULT_ZOOM_SINGLE);
  } else if (markersArray.length > 1) {
    const group = new L.featureGroup(markersArray);
    mapa.fitBounds(group.getBounds(), { padding: FIT_PADDING, maxZoom: MAX_FIT_ZOOM });
  }

  // ====== SELECT2 (lista só cidades SEM lat/lon) ======
  $('#selectCidade').select2({
    theme: 'bootstrap4',
    placeholder: 'Digite para buscar cidade...',
    ajax: {
      url: 'cidades_select2.php',
      dataType: 'json',
      delay: 250,
      data: function (params) {
        return { q: params.term };
      },
      processResults: function (data) {
        return { results: data };
      }
    },
    templateResult: function (data) {
      if (data.loading) return data.text;
      if (data.ja_no_mapa) {
        return $('<span class="text-danger fw-bold">' + data.text + '</span>');
      }
      return data.text;
    },
    templateSelection: function (data) {
      if (data.ja_no_mapa) return '';
      return data.text || '';
    },
    escapeMarkup: function (markup) { return markup; }
  });


  // ====== ADD CITY FROM SELECT2 ======
  $('#selectCidade').on('select2:select', async function (e) {
    const dataSel = e.params.data; // {id:"Nome,UF", nome, uf}
    // const chave = dataSel.id;
    const chave = `${dataSel.nome},${dataSel.uf}`;
    if (!chave || markerCidade[chave]) { $('#selectCidade').val(null).trigger('change'); return; }

    spinner.style.display = 'inline-block';
    try {
      const q = `${dataSel.nome}, ${dataSel.uf}, Brasil`;
      const res = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(q)}`);
      const geo = await res.json();

      if (geo[0]) {
        const lat = parseFloat(geo[0].lat);
        const lon = parseFloat(geo[0].lon);

        // Persiste coordenadas
        const saveRes = await fetch('ajax_salvar_cidade_mapa.php', {
          method: "POST",
          headers: {'Content-Type':'application/json'},
          body: JSON.stringify({ cidade_nome: dataSel.nome, cidade_uf: dataSel.uf, lat, lon })
        });
        const saveJson = await saveRes.json();
        if (!saveJson.success) { alert("Falha ao salvar cidade no mapa."); return; }

        // Adiciona marker (sem viagem ainda => azul)
        const marker = L.marker([lat, lon], { icon: iconBlue }).addTo(mapa);
        markerCidade[chave] = marker;
        marker.on('click', () => abrirPopupCadastro(marker, chave));
        mapa.setView([lat, lon], DEFAULT_ZOOM_SINGLE);

        // Inicia info sem histórico
        // infoCidade[chave] = { motorista_id: null, motorista_nome: null, urgente: 'nao', coleta: 'nao' };
        infoCidade[chave] = {
          motorista_id: null,
          motorista_nome: null,
          urgente: 'nao',
          coleta: 'nao',
          nome: dataSel.nome,
          uf: dataSel.uf
        };

        // Lista lateral
        const li = document.createElement('li');
        li.className = "list-group-item d-flex justify-content-between align-items-center";
        li.dataset.value = chave;
        li.innerHTML = `
          <span>${escapeHtml(dataSel.nome)},${escapeHtml(dataSel.uf)}</span>
          <button class="btn btn-sm btn-outline-danger btn-remove" data-value="${escapeHtml(chave)}">&times;</button>
        `;
        lista.appendChild(li);
      } else {
        alert("Cidade não encontrada na geocodificação.");
      }
    } catch {
      alert("Erro ao buscar coordenadas/salvar cidade.");
    } finally {
      spinner.style.display = 'none';
      $('#selectCidade').val(null).trigger('change');
    }
  });

  // ====== CLEAR ALL (coords + viagens) ======
  document.getElementById('btnLimpar').addEventListener('click', async () => {
    if (!confirm('Tem certeza que deseja limpar o mapa? Isso remove TODOS os markers e TODAS as viagens.')) return;

    const btn = document.getElementById('btnLimpar');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = 'Limpando...';
    spinner.style.display = 'inline-block';

    try {
      const resp = await fetch('ajax_limpar_mapa.php', { method: 'POST' });
      const json = await resp.json();

      if (!json.success) { alert('Não foi possível limpar no servidor.'); return; }

      // Limpa visual e memória
      Object.values(markerCidade).forEach(m => mapa.removeLayer(m));
      for (const k in markerCidade) delete markerCidade[k];
      for (const k in infoCidade) delete infoCidade[k];
      lista.innerHTML = '';
      $('#selectCidade').val(null).trigger('change');

      alert(`Mapa limpo!\nCidades zeradas: ${json.cidades_zeradas ?? 0}\nViagens apagadas: ${json.viagens_apagadas ?? 0}`);
    } catch {
      alert('Erro ao comunicar com o servidor.');
    } finally {
      spinner.style.display = 'none';
      btn.disabled = false;
      btn.innerHTML = originalText;
    }
  });

  // ====== REMOVE ONE CITY (coords + viagens) ======
  lista.addEventListener('click', async function (e) {
    if (e.target.classList.contains('btn-remove')) {
      const chave = e.target.dataset.value;               // "Nome,UF"
      const [nome, uf] = chave.split(',').map(s => s.trim());

      // 1) Visual
      if (markerCidade[chave]) {
        mapa.removeLayer(markerCidade[chave]);
        delete markerCidade[chave];
      }
      const item = lista.querySelector(`li[data-value="${cssSel(chave)}"]`);
      if (item) item.remove();

      // 2) Memória
      delete infoCidade[chave];

      // 3) Persistência
      try {
        const resp = await fetch('ajax_remover_cidade_mapa.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ cidade_nome: nome, cidade_uf: uf })
        });
        const json = await resp.json();
        if (!json.success) alert('Não foi possível remover do mapa no servidor.');
      } catch { alert('Erro ao comunicar com o servidor ao remover a cidade.'); }
    }
  });

  // ====== POPUP & SAVE VIAGEM ======
  function abrirPopupCadastro(marker, chave) {
    // recria popup do zero (evita bug de reabrir)
    marker.unbindPopup();

    const info = infoCidade[chave] || { motorista_id: null, urgente: 'nao', coleta: 'nao' };
    const preselectId = info.motorista_id || (ultimoMotoristaId ? String(ultimoMotoristaId) : '');

    const safeId = String(chave).replace(/[^a-z0-9_-]/gi, '_');
    // const safeId = chave.replace(/[^a-z0-9_-]/gi, '_');
    // const safeId = chave;

    let opts = '<option value="">...</option>';
    motoristas.forEach(m => {
      const selected = (preselectId && String(preselectId) === String(m.id)) ? 'selected' : '';
      opts += `<option value="${m.id}" ${selected}>${escapeHtml(m.nome)}</option>`;
    });

    const urgSelNao = (!info.urgente || info.urgente === 'nao') ? 'selected' : '';
    const urgSelSim = (info.urgente === 'sim') ? 'selected' : '';
    const colSelNao = (!info.coleta  || info.coleta  === 'nao') ? 'selected' : '';
    const colSelSim = (info.coleta  === 'sim') ? 'selected' : '';

    const html = `
      <div style="min-width:230px">
        <strong>${escapeHtml(chave)}</strong>
        <label class="form-label mb-1 mt-2">Motorista:</label>
        <select class="form-select form-select-sm mb-2" id="motorista-${safeId}">${opts}</select>

        <div class="row g-2 mb-2">
          <div class="col-6">
            <label class="form-label mb-1">Urgente?</label>
            <select class="form-select form-select-sm" id="urgente-${safeId}">
              <option value="nao" ${urgSelNao}>Não</option>
              <option value="sim" ${urgSelSim}>Sim</option>
            </select>
          </div>
          <div class="col-6">
            <label class="form-label mb-1">Coleta?</label>
            <select class="form-select form-select-sm" id="coleta-${safeId}">
              <option value="nao" ${colSelNao}>Não</option>
              <option value="sim" ${colSelSim}>Sim</option>
            </select>
          </div>
        </div>

        <button class="btn btn-sm btn-primary w-100"
                onclick='cadastrarViagem(${JSON.stringify(chave)})'>
          Cadastrar Viagem
        </button>
      </div>
    `;
    marker.bindPopup(html, { autoClose: true, closeOnClick: true, keepInView: true }).openPopup();
  }

  async function cadastrarViagem(chave) {
    const [nome, uf] = chave.split(',').map(s => (s || '').trim());
    const safeId = chave.replace(/[^a-z0-9_-]/gi, '_');

    const selectMot = document.getElementById(`motorista-${safeId}`);
    const motorista_id = selectMot?.value || null; // agora pode ser null

    // status vem dos selects (ou do estado salvo da cidade, dependendo do seu popup atual)
    const urgenteEl = document.getElementById(`urgente-${safeId}`) || document.getElementById(`cidade-urgente-${safeId}`);
    const coletaEl  = document.getElementById(`coleta-${safeId}`)  || document.getElementById(`cidade-coleta-${safeId}`);
    const urgente = urgenteEl?.value || 'nao';
    const coleta  = coletaEl?.value  || 'nao';

    try {
      const res = await fetch("ajax_cadastrar_viagem.php", {
        method: "POST",
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({
          motorista_id: motorista_id,  // null OK
          cidade_nome: nome,
          cidade_uf: uf,
          urgente,
          coleta
        })
      });
      const json = await res.json();
      if (json.success) {
        // memoriza motorista somente se escolheu
        if (motorista_id) {
          ultimoMotoristaId = motorista_id;
          infoCidade[chave].motorista_id   = String(motorista_id);
          infoCidade[chave].motorista_nome = selectMot.options[selectMot.selectedIndex].textContent;
        } else {
          infoCidade[chave].motorista_id   = null;
          infoCidade[chave].motorista_nome = null;
        }

        // Atualiza status local
        infoCidade[chave].urgente = urgente;
        infoCidade[chave].coleta  = coleta;

        // ⬇️ Se escolheu motorista ⇒ remove só esta cidade do mapa e da lista
        if (motorista_id) {
          if (markerCidade[chave]) {
            mapa.removeLayer(markerCidade[chave]);
            delete markerCidade[chave];
          }
          const item = lista.querySelector(`li[data-value="${cssSel(chave)}"]`);
          if (item) item.remove();
          delete infoCidade[chave];
        } else {
          // Sem motorista ⇒ só atualiza cor/badges normalmente
          if (markerCidade[chave]) {
            markerCidade[chave].setIcon(getMarkerIcon(urgente, coleta));
          }
          const item = lista.querySelector(`li[data-value="${cssSel(chave)}"]`);
          if (item) {
            const urgBadge = (urgente === 'sim') ? ' <span class="badge bg-danger ms-1">Urgente</span>' : '';
            const colBadge = (coleta  === 'sim') ? ' <span class="badge bg-warning text-dark ms-1">Coleta</span>' : '';
            const motTxt   = infoCidade[chave].motorista_nome
              ? ` — <strong>${escapeHtml(infoCidade[chave].motorista_nome)}</strong>` : '';
            item.innerHTML = `
              <span>${escapeHtml(chave)}${motTxt}${urgBadge}${colBadge}</span>
              <button class="btn btn-sm btn-outline-danger btn-remove" data-value="${escapeHtml(chave)}">&times;</button>
            `;
          }
        }

        mapa.closePopup();

        // const modo = json.mode || 'insert';
        // if (modo === 'updated')      alert('Registro atualizado com sucesso!');
        // else if (modo === 'noop')    alert('Nada para atualizar (mesmos dados).');
        // else                         alert('Registro salvo com sucesso!');
      } else {
        alert("Erro: " + (json.error || "desconhecido"));
      }

    } catch (e) {
      alert("Erro ao salvar: " + (e.message || "desconhecido"));
    }
  }

  // ====== HELPERS ======
  function escapeHtml(str){
    return String(str).replace(/[&<>"'`=\/]/g, s => ({
      '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;','/':'&#x2F;','`':'&#x60;','=':'&#x3D;'
    }[s]));
  }
  function cssSel(str){ return String(str).replace(/(["\\])/g, '\\$1'); }
</script>

<?php include_once 'footer.php'; ?>
