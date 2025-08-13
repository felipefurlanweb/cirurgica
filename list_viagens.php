<?php
include_once 'header.php';
include_once 'conexao.php';

// lista detalhada (uma linha por cidade da viagem)
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
  ORDER BY STR_TO_DATE(v.data, '%d/%m/%Y') DESC, v.id DESC
";

$resultado = $conn->query($sql);
?>

<div class="d-flex align-items-center justify-content-between mb-3">
  <h4 class="mb-0">Viagens</h4>
  <div class="d-flex gap-2">
    <a href="index.php" class="btn btn-outline-secondary">Voltar</a>
  </div>
</div>

<div class="card shadow-lg">
  <div class="card-body">
    <?php if ($resultado && $resultado->num_rows > 0): ?>
      <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle">
          <thead class="table-light">
            <tr>
              <th style="width:100px">Ações</th>
              <th>Motorista</th>
              <th>Cidade</th>
              <th>UF</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($v = $resultado->fetch_assoc()): ?>
              <tr>
                <td class="text-nowrap">
                  <!-- editar/excluir somente este registro -->
                  <!-- <a href="editar_viagem.php?id=<?= (int)$v['id'] ?>" class="btn btn-sm btn-warning">Editar</a> -->
                  <a href="excluir_viagem.php?id=<?= (int)$v['id'] ?>"
                     class="btn btn-sm btn-danger"
                     onclick="return confirm('Deseja excluir esta cidade da viagem?')">Excluir</a>
                </td>
                <td><?= htmlspecialchars($v['motorista'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($v['cidade'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($v['uf'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="alert alert-info">Nenhuma viagem cadastrada.</div>
    <?php endif; ?>
    
  </div>
</div>

<?php include_once 'footer.php'; ?>
