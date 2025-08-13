<?php
include_once 'header.php';
include_once 'conexao.php';

if (($_SESSION['tipo'] ?? '') !== 'admin') {
  echo '<div class="alert alert-danger m-3">Acesso negado.</div>';
  include_once 'footer.php'; exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { echo '<div class="alert alert-danger m-3">ID inválido.</div>'; include_once 'footer.php'; exit; }

$stmt = $conn->prepare("SELECT id, nome, email, username, tipo, ativo FROM usuarios WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$u = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$u) { echo '<div class="alert alert-warning m-3">Usuário não encontrado.</div>'; include_once 'footer.php'; exit; }
?>

<div class="card shadow-lg">
  <div class="card-header bg-primary text-white">
    <h4 class="mb-0">Editar Usuário</h4>
  </div>
  <div class="card-body">
    <form action="atualizar_usuario.php" method="post" class="row g-3">
      <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">

      <div class="col-md-6">
        <label class="form-label">Nome</label>
        <input type="text" name="nome" class="form-control" value="<?= htmlspecialchars($u['nome']) ?>" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($u['email']) ?>" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">Usuário</label>
        <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($u['username']) ?>" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">Nova Senha (opcional)</label>
        <input type="password" name="senha" class="form-control" minlength="6" placeholder="Deixe em branco para manter">
      </div>
      <div class="col-md-2">
        <label class="form-label">Tipo</label>
        <select name="tipo" class="form-select" required>
          <option value="operador" <?= $u['tipo']==='operador'?'selected':''; ?>>Operador</option>
          <option value="admin"    <?= $u['tipo']==='admin'?'selected':'';    ?>>Admin</option>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">Ativo</label>
        <select name="ativo" class="form-select">
          <option value="1" <?= $u['ativo'] ? 'selected':''; ?>>Sim</option>
          <option value="0" <?= !$u['ativo'] ? 'selected':''; ?>>Não</option>
        </select>
      </div>

      <div class="col-12 text-end">
        <a href="list_usuarios.php" class="btn btn-secondary">Cancelar</a>
        <button class="btn btn-success">Salvar</button>
      </div>
    </form>
  </div>
</div>

<?php include_once 'footer.php'; ?>
