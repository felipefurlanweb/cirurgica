<?php
include_once 'header.php';
include_once 'conexao.php';

$id = $_GET['id'] ?? 0;
$res = $conn->prepare("SELECT * FROM motoristas WHERE id = ?");
$res->bind_param("i", $id);
$res->execute();
$motorista = $res->get_result()->fetch_assoc();
?>

<div class="card shadow-lg">
  <div class="card-header bg-primary text-white">
    <h4>Editar Motorista</h4>
  </div>
  <div class="card-body">
    <form method="post" action="atualizar_motorista.php" class="row g-3">
      <input type="hidden" name="id" value="<?= $motorista['id'] ?>">
      <div class="col-md-4">
        <label class="form-label">Nome</label>
        <input type="text" name="nome" class="form-control" value="<?= htmlspecialchars($motorista['nome']) ?>" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">Telefone</label>
        <input type="text" name="telefone" class="form-control" value="<?= $motorista['telefone'] ?>">
      </div>
      <div class="col-12 text-end">
        <a href="list_motoristas.php" class="btn btn-secondary">Cancelar</a>
        <button type="submit" class="btn btn-success">Salvar Alterações</button>
      </div>
    </form>
  </div>
</div>

<?php include_once 'footer.php'; ?>
