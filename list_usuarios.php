<?php
include_once 'header.php';
include_once 'conexao.php';

if (($_SESSION['tipo'] ?? '') !== 'admin') {
  echo '<div class="alert alert-danger m-3">Acesso negado.</div>';
  include_once 'footer.php';
  exit;
}

$res = $conn->query("SELECT id, nome, email, username, tipo, ativo FROM usuarios ORDER BY nome");
?>

<div class="d-flex align-items-center justify-content-between mb-3">
  <h4 class="mb-0">Usuários</h4>
  <div class="d-flex gap-2">
    <a href="form_usuario.php" class="btn btn-primary">+ Novo Usuário</a>
    <a href="index.php" class="btn btn-outline-secondary">Voltar</a>
  </div>
</div>

<div class="card shadow-sm">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-striped align-middle">
        <thead class="table-light">
          <tr>
            <th>ID</th>
            <th>Nome</th>
            <th>Email</th>
            <th>Usuário</th>
            <th>Tipo</th>
            <th>Ativo</th>
            <th style="width:180px">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($u = $res->fetch_assoc()): ?>
            <tr>
              <td><?= (int)$u['id'] ?></td>
              <td><?= htmlspecialchars($u['nome']) ?></td>
              <td><?= htmlspecialchars($u['email']) ?></td>
              <td><?= htmlspecialchars($u['username']) ?></td>
              <td>
                <?= $u['tipo']==='admin' ? '<span class="badge bg-dark">Admin</span>' : '<span class="badge bg-secondary">Operador</span>' ?>
              </td>
              <td>
                <?= $u['ativo'] ? '<span class="badge bg-success">Sim</span>' : '<span class="badge bg-danger">Não</span>' ?>
              </td>
              <td class="text-nowrap">
                <a class="btn btn-sm btn-warning" href="edit_usuario.php?id=<?= (int)$u['id'] ?>">Editar</a>
                <?php if ((int)$u['id'] !== (int)($_SESSION['usuario_id'] ?? 0)): // evita excluir a si mesmo ?>
                  <a class="btn btn-sm btn-danger" href="del_usuario.php?id=<?= (int)$u['id'] ?>"
                     onclick="return confirm('Excluir este usuário?')">Excluir</a>
                <?php endif; ?>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include_once 'footer.php'; ?>
