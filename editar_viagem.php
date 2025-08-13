<?php
include_once 'header.php';
include_once 'conexao.php';

$id = $_GET['id'] ?? 0;

// Pega dados da viagem
$stmt = $conn->prepare("SELECT * FROM viagens WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$viagem = $stmt->get_result()->fetch_assoc();

// Pega motoristas e cidades
$motoristas = $conn->query("SELECT id, nome FROM motoristas ORDER BY nome");
$cidades = $conn->query("SELECT id, nome, estado FROM cidades ORDER BY nome");
?>

<div class="card shadow-lg">
  <div class="card-header bg-primary text-white">
    <h4>Editar Viagem</h4>
  </div>
  <div class="card-body">
    <form method="post" action="atualizar_viagem.php" class="row g-3">
      <input type="hidden" name="id" value="<?= $viagem['id'] ?>">

      <div class="col-md-6">
        <label for="motorista_id" class="form-label">Motorista</label>
        <select name="motorista_id" id="motorista_id" class="form-select" required>
          <option value="">Selecione um motorista</option>
          <?php while ($m = $motoristas->fetch_assoc()): ?>
            <option value="<?= $m['id'] ?>" <?= $m['id'] == $viagem['motorista_id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($m['nome']) ?>
            </option>
          <?php endwhile; ?>
        </select>
      </div>

      <div class="col-md-6">
        <label for="cidade_id" class="form-label">Cidade de Destino</label>
        <select name="cidade_id" id="cidade_id" class="form-select" required>
          <option value="">Selecione uma cidade</option>
          <?php while ($c = $cidades->fetch_assoc()): ?>
            <option value="<?= $c['id'] ?>" <?= $c['id'] == $viagem['cidade_id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($c['nome']) ?> (<?= $c['estado'] ?>)
            </option>
          <?php endwhile; ?>
        </select>
      </div>

      <div class="col-12 text-end">
        <a href="list_viagens.php" class="btn btn-secondary">Cancelar</a>
        <button type="submit" class="btn btn-success">Salvar Alterações</button>
      </div>
    </form>
  </div>
</div>

<?php include_once 'footer.php'; ?>
