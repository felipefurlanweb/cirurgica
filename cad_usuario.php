<?php
include_once 'conexao.php';
session_start();
if (($_SESSION['tipo'] ?? '') !== 'admin') { header("Location: index.php"); exit; }

$nome     = trim($_POST['nome'] ?? '');
$email    = trim($_POST['email'] ?? '');
$username = trim($_POST['username'] ?? '');
$senha    = $_POST['senha'] ?? '';
$tipo     = $_POST['tipo'] === 'admin' ? 'admin' : 'operador';
$ativo    = isset($_POST['ativo']) && $_POST['ativo']=='0' ? 0 : 1;

if ($nome==='' || $email==='' || $username==='' || strlen($senha) < 6) {
  header("Location: form_usuario.php?erro=1"); exit;
}

$hash = password_hash($senha, PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO usuarios (nome, email, username, senha, tipo, ativo) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssssi", $nome, $email, $username, $hash, $tipo, $ativo);
if (!$stmt->execute()) {
  die("Erro ao salvar: " . $stmt->error);
}

header("Location: list_usuarios.php?ok=1");
