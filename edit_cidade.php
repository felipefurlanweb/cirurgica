<?php
include_once 'header.php';
include_once 'conexao.php';

// valida ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
  echo '<div class="alert alert-danger">ID inválido.</div>';
  include_once 'footer.php';
  exit;
}

// carrega cidade
$stmt = $conn->prepare("SELECT id, nome, uf, urgente, coleta FROM cidades WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$cidade = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$cidade) {
  echo '<div class="alert alert-warning">Cidade não encontrada.</div>';
  include_once 'footer.php';
  exit;
}

// carrega estados (id + sigla + nome)
$estados = $conn->query("SELECT id, uf, nome FROM estado ORDER BY nome")->fetch_all(MYSQLI_ASSOC);
?>

<div class="card shadow-lg">
  <div class="card-header bg-primary text-white">
    <h4 class="mb-0">Editar Cidade</h4>
  </div>
  <div class="card-body">
    <form method="post" action="atualizar_cidade.php" class="row g-3">
      <input type="hidden" name="id" value="<?= (int)$cidade['id'] ?>">
      <div class="col-md-4">
        <label class="form-label">Nome da Cidade</label>
        <input type="text" name="nome" class="form-control" value="<?= htmlspecialchars($cidade['nome'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">UF</label>
        <select name="uf" class="form-select" required>
          <option value="">Selecione...</option>
          <?php foreach ($estados as $e): ?>
            <option value="<?= (int)$e['id'] ?>"
              <?= ((int)$cidade['uf'] === (int)$e['id']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($e['nome'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> (<?= htmlspecialchars($e['uf']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-2">
        <label class="form-label">Urgente?</label>
        <select name="urgente" class="form-select">
          <option value="nao" <?= $cidade['urgente']==='nao'?'selected':''; ?>>Não</option>
          <option value="sim"  <?= $cidade['urgente']==='sim'?'selected':''; ?>>Sim</option>
        </select>
      </div>

      <div class="col-md-2">
        <label class="form-label">Coleta?</label>
        <select name="coleta" class="form-select">
          <option value="nao" <?= $cidade['coleta']==='nao'?'selected':''; ?>>Não</option>
          <option value="sim"  <?= $cidade['coleta']==='sim'?'selected':''; ?>>Sim</option>
        </select>
      </div>

      <div class="col-12 text-end">
        <a href="list_cidades.php" class="btn btn-secondary">Cancelar</a>
        <button type="submit" class="btn btn-success">Salvar Alterações</button>
      </div>
    </form>
  </div>
</div>

<?php include_once 'footer.php'; ?>
