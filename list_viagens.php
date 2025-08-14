<?php
// inicie a sessão antes de qualquer saída
session_start();

include_once 'conexao.php';
include_once 'header.php';

// consulta
$sql = "
  SELECT
    v.id,
    v.motorista_id,
    m.nome AS motorista,
    c.nome AS cidade,
    e.uf   AS uf,
    v.data
  FROM viagens v
  JOIN motoristas m ON v.motorista_id = m.id
  JOIN cidades c    ON v.cidade_id    = c.id
  JOIN estado  e    ON e.id           = c.uf
  ORDER BY m.nome ASC, STR_TO_DATE(v.data, '%d/%m/%Y') DESC, v.id DESC
";
$resultado = $conn->query($sql);
?>

<div class="d-flex align-items-center justify-content-between mb-3">
  <h4 class="mb-0">Viagens</h4>
  <div class="d-flex gap-2">
    <a href="index.php" class="btn btn-outline-secondary">Voltar</a>
  </div>
</div>

<?php
if ($resultado && $resultado->num_rows > 0) {
  $currentMotoristaId = null;

  while ($v = $resultado->fetch_assoc()) {
    $motoristaIdLinha = (int)$v['motorista_id'];

    // mudou o motorista -> fecha card anterior (se houver) e abre um novo
    if ($currentMotoristaId !== $motoristaIdLinha) {
      if ($currentMotoristaId !== null) {
        // fecha o card anterior
        echo '</tbody></table></div></div></div>';
      }

      $currentMotoristaId = $motoristaIdLinha;
      $motoristaNome = htmlspecialchars($v['motorista'], ENT_QUOTES, 'UTF-8');
      ?>
      <div class="card shadow-lg mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <div class="fw-semibold">Motorista: <?= $motoristaNome ?></div>
          <form action="excluir_viagens_motorista_mapa.php" method="GET"
                onsubmit="return confirm('Concluir TODAS as viagens deste motorista?')">
            <input type="hidden" name="motorista_id" value="<?= $currentMotoristaId ?>">
            <button type="submit" class="btn btn-sm btn-warning">Concluir todas as viagens</button>
          </form>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
              <thead class="table-light">
                <tr>
                  <th style="width:160px">Ações</th>
                  <th>Data</th>
                  <th>Cidade</th>
                  <th>UF</th>
                </tr>
              </thead>
              <tbody>
      <?php
    } // fim troca de motorista
    ?>
      <tr>
        <td class="text-nowrap">
          <a href="excluir_viagem_mapa.php?id=<?= (int)$v['id'] ?>"
             class="btn btn-sm btn-success"
             onclick="return confirm('Marcar viagem como concluída?')">Viagem Concluída</a>
          <a href="excluir_viagem.php?id=<?= (int)$v['id'] ?>"
             class="btn btn-sm btn-danger"
             onclick="return confirm('Excluir esta cidade da viagem?')">Excluir Viagem</a>
        </td>
        <td><?= htmlspecialchars($v['data'], ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars($v['cidade'], ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars($v['uf'], ENT_QUOTES, 'UTF-8') ?></td>
      </tr>
    <?php
  } // endwhile

  // fecha o último card
  if ($currentMotoristaId !== null) {
    echo '</tbody></table></div></div></div>';
  }
} else {
  echo '<div class="alert alert-info">Nenhuma viagem cadastrada.</div>';
}
?>

<?php include_once 'footer.php'; ?>
