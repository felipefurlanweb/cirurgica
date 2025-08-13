<?php
include_once 'header.php';
include_once 'conexao.php';

// Contagens para os cards
$total_motoristas = $conn->query("SELECT COUNT(*) AS total FROM motoristas")->fetch_assoc()['total'];
$total_cidades = $conn->query("SELECT COUNT(*) AS total FROM cidades")->fetch_assoc()['total'];
$total_viagens = $conn->query("SELECT COUNT(*) AS total FROM viagens WHERE motorista_id IS NOT NULL")->fetch_assoc()['total'];

// Viagens por cidade
$res2 = $conn->query("SELECT c.nome AS cidade, COUNT(*) AS total
                      FROM viagens v JOIN cidades c ON v.cidade_id = c.id
                      GROUP BY v.cidade_id ORDER BY total DESC");
$via_cidades = $via_cidades_total = [];
while ($row = $res2->fetch_assoc()) {
  $via_cidades[] = $row['cidade'];
  $via_cidades_total[] = $row['total'];
}

// Viagens por motorista
$res3 = $conn->query("SELECT m.nome AS motorista, COUNT(*) AS total
                      FROM viagens v JOIN motoristas m ON v.motorista_id = m.id
                      GROUP BY v.motorista_id ORDER BY total DESC");
$via_motoristas = $via_motoristas_total = [];
while ($row = $res3->fetch_assoc()) {
  $via_motoristas[] = $row['motorista'];
  $via_motoristas_total[] = $row['total'];
}
?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


<div class="d-flex flex-wrap align-items-center my-3 gap-2">
  
  <h2 class="text-primary">Painel de Controle</h2>

  <form class="d-flex align-items-center gap-2" action="exportar_geral.php" method="get">
    <input type="text" name="data" class="form-control" placeholder="Data (dd/mm/aaaa)" style="max-width: 160px;" required id="dataExport">
    <button type="submit" class="btn btn-outline-success">📦 Exportar Geral</button>
  </form>

  <!-- <a href="exporta_viagens.php" class="btn btn-secondary">Exportar Viagens (CSV)</a> -->
  <a href="list_usuarios.php" class="btn btn-outline-primary">Usuários</a>
  <a href="mapa_cidades.php" class="btn btn-info">Mapa Cidades</a>
  <a href="mapa_motoristas.php" class="btn btn-primary">Mapa Motoristas</a>
  <a href="logout.php" class="btn btn-danger">Sair</a>
</div>


<div class="row g-4 mb-4">
  <div class="col-md-4">
    <div class="card border-primary shadow h-100">
      <div class="card-body">
        <h5 class="card-title">Motoristas</h5>
        <p class="card-text fs-4"><?= $total_motoristas ?> cadastrados</p>
        <a href="list_motoristas.php" class="btn btn-outline-primary w-100">Ver todos</a>
        <a href="form_motorista.php" class="btn btn-primary mt-2 w-100">Novo Motorista</a>
      </div>
    </div>
  </div>

  <div class="col-md-4">
    <div class="card border-success shadow h-100">
      <div class="card-body">
        <h5 class="card-title">Cidades</h5>
        <p class="card-text fs-4"><?= $total_cidades ?> cadastradas</p>
        <a href="list_cidades.php" class="btn btn-outline-success w-100">Ver todas</a>
        <a href="form_cidade.php" class="btn btn-success mt-2 w-100">Nova Cidade</a>
      </div>
    </div>
  </div>

  <div class="col-md-4">
    <div class="card border-dark shadow h-100">
      <div class="card-body">
        <h5 class="card-title">Viagens</h5>
        <p class="card-text fs-4"><?= $total_viagens ?> registradas</p>
        <a href="list_viagens.php" class="btn btn-outline-dark w-100">Ver todas</a>
        <a href="form_viagem.php" class="btn btn-dark mt-2 w-100">Nova Viagem</a>
      </div>
    </div>
  </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-6">
        <!-- Gráfico 2 -->
        <div class="card shadow mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">Viagens por Cidade</h5>
        </div>
        <div class="card-body">
            <canvas id="grafico_viagens_cidade"></canvas>
        </div>
        </div>
    </div>
    <div class="col-md-6">
        <!-- Gráfico 3 -->
        <div class="card shadow mb-4">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0">Viagens por Motorista</h5>
        </div>
        <div class="card-body">
            <canvas id="grafico_viagens_motorista"></canvas>
        </div>
        </div>
    </div>
</div>

<!-- JS dos gráficos -->
<script>

  new Chart(document.getElementById('grafico_viagens_cidade'), {
    type: 'bar',
    data: {
      labels: <?= json_encode($via_cidades) ?>,
      datasets: [{
        label: 'Viagens',
        data: <?= json_encode($via_cidades_total) ?>,
        backgroundColor: 'rgba(25, 135, 84, 0.7)'
      }]
    },
    options: { responsive: true, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 }}}}
  });

  new Chart(document.getElementById('grafico_viagens_motorista'), {
    type: 'bar',
    data: {
      labels: <?= json_encode($via_motoristas) ?>,
      datasets: [{
        label: 'Viagens',
        data: <?= json_encode($via_motoristas_total) ?>,
        backgroundColor: 'rgba(108, 117, 125, 0.7)'
      }]
    },
    options: { responsive: true, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 }}}}
  });
</script>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
  $(document).ready(function () {
    flatpickr("#dataExport", {
      dateFormat: "d/m/Y",
      defaultDate: new Date()
    });
  });
</script>



<?php include_once 'footer.php'; ?>
