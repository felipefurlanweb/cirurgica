<?php include_once 'header.php'; ?>

<div class="card shadow-lg">
  <div class="card-header bg-primary text-white">
    <h4 class="mb-0">Cadastro de Cidade</h4>
  </div>
  <div class="card-body">
    <form method="post" action="cad_cidade.php" class="row g-3">
      <div class="col-md-4">
        <label for="nome" class="form-label">Nome da Cidade</label>
        <input type="text" name="nome" id="nome" class="form-control" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">UF</label>
        <select name="uf" class="form-select" required>
          <option value="">Selecione...</option>
          <?php foreach ($estados as $e): ?>
            <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['nome']) ?> (<?= $e['uf'] ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">Urgente?</label>
        <select name="urgente" class="form-select" required>
          <option value="nao" selected>Não</option>
          <option value="sim">Sim</option>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">Coleta?</label>
        <select name="coleta" class="form-select" required>
          <option value="nao" selected>Não</option>
          <option value="sim">Sim</option>
        </select>
      </div>
      <div class="col-md-12 text-end">
        <a href="index.php" class="btn btn-secondary">Voltar</a>
        <button type="submit" class="btn btn-primary">Cadastrar</button>
      </div>
    </form>
  </div>
</div>

<?php include_once 'footer.php'; ?>
