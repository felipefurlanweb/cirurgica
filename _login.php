<?php
// login.php
declare(strict_types=1);

include_once 'conexao.php';
session_start();

// Só aceita POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: login.php');
  exit;
}

$email = trim($_POST['email'] ?? '');
$senha    = $_POST['senha'] ?? '';

// echo $username;die();
// echo $senha;die();

if ($email === '' || $senha === '') {
  header('Location: login.php?erro=1');
  exit;
}

// Busca usuário
$sql = "SELECT id, nome, email, username, senha, tipo, ativo
        FROM usuarios
        WHERE email = ?
        LIMIT 1";
$stmt = $conn->prepare($sql);
if (!$stmt) { header('Location: login.php?erro=1'); exit; }
$stmt->bind_param('s', $email);
$stmt->execute();
$res = $stmt->get_result();

if (!$res || $res->num_rows === 0) {
  // echo "nothing found"; die();
  header('Location: login.php?erro=1'); exit;
}

$user = $res->fetch_assoc();

// Ativo?
if ((int)$user['ativo'] !== 1) {
  // echo "n ativo"; die();
  header('Location: login.php?erro=1'); exit;
}

// echo password_hash("admin123", PASSWORD_DEFAULT);

$hash = $user['senha'];

if (!password_verify($senha, $hash)) {
  echo "senha nao confere"; die();
  header('Location: login.php?erro=1'); exit;
} 


// Login OK
session_regenerate_id(true);
$_SESSION['usuario_id']   = (int)$user['id'];
$_SESSION['usuario_nome'] = $user['nome'];
$_SESSION['tipo']         = $user['tipo'];
$_SESSION['logado_em']    = date('Y-m-d H:i:s');

header('Location: index.php');
exit;
