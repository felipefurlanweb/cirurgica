<?php
include_once 'header.php';
include_once 'conexao.php';

$sql = "SELECT * FROM motoristas ORDER BY nome";
$resultado = $conn->query($sql);
?>

<div class="d-flex align-items-center justify-content-between mb-3">
  <h4 class="mb-0">Motoristas</h4>
  <div class="d-flex gap-2">
    <a href="form_motorista.php" class="btn btn-primary">+ Adicionar</a>
    <a href="index.php" class="btn btn-outline-secondary">Voltar</a>
  </div>
</div>

<div class="card shadow-lg">
  <div class="card-body">
    <?php if ($resultado->num_rows > 0): ?>
      <table class="table table-bordered table-striped">
        <thead class="table-light">
          <tr>
            <th>Ações</th>
            <th>Nome</th>
            <th>Telefone</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($m = $resultado->fetch_assoc()): ?>
            <tr>
                <td style="width: 400px;">
                    <a href="exportar_viagem.php?motorista_id=<?= $m['id'] ?>" class="btn btn-sm btn-outline-success">
                      Exportar Viagem
                    </a>
                    <a href="editar_motorista.php?id=<?= $m['id'] ?>" class="btn btn-sm btn-warning">Editar</a>
                    <a href="excluir_motorista.php?id=<?= $m['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza que deseja excluir este motorista?')">Excluir</a>
                </td>
              <td><?= htmlspecialchars($m['nome']) ?></td>
              <td><?= $m['telefone'] ?></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    <?php else: ?>
      <div class="alert alert-info">Nenhum motorista cadastrado.</div>
    <?php endif; ?>
    
  </div>
</div>

<?php include_once 'footer.php'; ?>
