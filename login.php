<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Login - Sistema de Viagens</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: #f8f9fa;
    }
    .login-container {
      max-width: 400px;
      margin: 80px auto;
    }
    .card {
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .form-control:focus {
      border-color: #0d6efd;
      box-shadow: 0 0 0 0.2rem rgba(13,110,253,.25);
    }
  </style>
</head>
<body>

<div class="login-container">
  <div class="card">
    <div class="card-header text-center bg-primary text-white">
      <h4 class="mb-0">Acesso ao Sistema</h4>
    </div>
    <div class="card-body">
      <?php if (isset($_GET['erro'])): ?>
        <div class="alert alert-danger">
          E-mail ou senha inválidos.
        </div>
      <?php endif; ?>

      <form action="_login.php" method="post">
        <div class="mb-3">
          <label for="email" class="form-label">E-mail</label>
          <input type="text" id="email" name="email" class="form-control" required autofocus>
        </div>
        <div class="mb-3">
          <label for="senha" class="form-label">Senha</label>
          <input type="password" id="senha" name="senha" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Entrar</button>
      </form>
    </div>
  </div>
</div>

</body>
</html>
