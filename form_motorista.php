<?php include_once 'header.php'; ?>

<div class="card shadow-lg">
  <div class="card-header bg-primary text-white">
    <h4 class="mb-0">Cadastro de Motorista</h4>
  </div>
  <div class="card-body">
    <form method="post" action="cad_motorista.php" class="row g-3">
      <div class="col-md-6">
        <label for="nome" class="form-label">Nome</label>
        <input type="text" name="nome" id="nome" class="form-control" required>
      </div>
      <div class="col-md-6">
        <label for="telefone" class="form-label">Telefone</label>
        <input type="text" name="telefone" id="telefone" class="form-control">
      </div>
      <div class="col-12 text-end">
        <a href="list_motoristas.php" class="btn btn-secondary">Voltar</a>
        <button type="submit" class="btn btn-primary">Cadastrar</button>
      </div>
    </form>
  </div>
</div>

<?php include_once 'footer.php'; ?>
