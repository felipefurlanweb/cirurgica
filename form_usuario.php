<?php
include_once 'header.php';
include_once 'conexao.php';

if (($_SESSION['tipo'] ?? '') !== 'admin') {
  echo '<div class="alert alert-danger m-3">Acesso negado.</div>';
  include_once 'footer.php';
  exit;
}
?>

<div class="card shadow-lg">
  <div class="card-header bg-primary text-white">
    <h4 class="mb-0">Novo Usuário</h4>
  </div>
  <div class="card-body">
    <form action="cad_usuario.php" method="post" class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Nome</label>
        <input type="text" name="nome" class="form-control" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">Usuário</label>
        <input type="text" name="username" class="form-control" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">Senha</label>
        <input type="password" name="senha" class="form-control" required minlength="6">
      </div>
      <div class="col-md-4">
        <label class="form-label">Tipo</label>
        <select name="tipo" class="form-select" required>
          <option value="operador">Operador</option>
          <option value="admin">Admin</option>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Ativo</label>
        <select name="ativo" class="form-select">
          <option value="1" selected>Sim</option>
          <option value="0">Não</option>
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
