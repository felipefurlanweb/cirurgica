<?php
include_once 'conexao.php';
session_start();
if (($_SESSION['tipo'] ?? '') !== 'admin') { header("Location: index.php"); exit; }

$id       = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$nome     = trim($_POST['nome'] ?? '');
$email    = trim($_POST['email'] ?? '');
$username = trim($_POST['username'] ?? '');
$senha    = $_POST['senha'] ?? '';
$tipo     = $_POST['tipo'] === 'admin' ? 'admin' : 'operador';
$ativo    = isset($_POST['ativo']) && $_POST['ativo']=='0' ? 0 : 1;

if ($id<=0 || $nome==='' || $email==='' || $username==='') {
  header("Location: edit_usuario.php?id={$id}&erro=1"); exit;
}

if ($senha !== '') {
  if (strlen($senha) < 6) { header("Location: edit_usuario.php?id={$id}&erro=2"); exit; }
  $hash = password_hash($senha, PASSWORD_DEFAULT);
  $stmt = $conn->prepare("UPDATE usuarios SET nome=?, email=?, username=?, senha=?, tipo=?, ativo=? WHERE id=?");
  $stmt->bind_param("ssssssi", $nome, $email, $username, $hash, $tipo, $ativo, $id);
} else {
  $stmt = $conn->prepare("UPDATE usuarios SET nome=?, email=?, username=?, tipo=?, ativo=? WHERE id=?");
  $stmt->bind_param("ssssii", $nome, $email, $username, $tipo, $ativo, $id);
}
if (!$stmt->execute()) { die("Erro ao atualizar: " . $stmt->error); }
header("Location: list_usuarios.php?ok=1");
