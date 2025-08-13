<?php
include_once 'header.php';
include_once 'conexao.php';

// Recuperar motoristas e cidades do banco
$motoristas = $conn->query("SELECT id, nome FROM motoristas ORDER BY nome");
$cidades = $conn->query("SELECT id, nome, estado FROM cidades ORDER BY nome");
?>

<div class="card shadow-lg">
  <div class="card-header bg-primary text-white">
    <h4 class="mb-0">Cadastro de Viagem</h4>
  </div>
  <div class="card-body">
    <form method="post" action="cad_viagem.php" class="row g-3">
      <div class="col-md-6">
        <label for="motorista_id" class="form-label">Motorista</label>
        <select name="motorista_id" id="motorista_id" class="form-select" required>
          <option value="">Selecione um motorista</option>
          <?php while ($m = $motoristas->fetch_assoc()): ?>
            <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nome']) ?></option>
          <?php endwhile; ?>
        </select>
      </div>

      <div class="col-md-6">
        <label for="cidade_id" class="form-label">Cidade de Destino</label>
        <select name="cidade_id" id="cidade_id" class="form-select" required>
          <option value="">Selecione uma cidade</option>
          <?php while ($c = $cidades->fetch_assoc()): ?>
            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nome']) ?> (<?= $c['estado'] ?>)</option>
          <?php endwhile; ?>
        </select>
      </div>

      <div class="col-12 text-end">
        <a href="index.php" class="btn btn-secondary">Voltar</a>
        <button type="submit" class="btn btn-primary">Cadastrar Viagem</button>
      </div>
    </form>
  </div>
</div>

<?php include_once 'footer.php'; ?>
