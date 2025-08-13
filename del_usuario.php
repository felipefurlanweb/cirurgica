<?php
include_once 'conexao.php';
session_start();
if (($_SESSION['tipo'] ?? '') !== 'admin') { header("Location: index.php"); exit; }

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { header("Location: list_usuarios.php?erro=1"); exit; }

if ($id === (int)($_SESSION['usuario_id'] ?? 0)) {
  header("Location: list_usuarios.php?erro=nao_pode_excluir_se"); exit;
}

$stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $id);
if (!$stmt->execute()) { die("Erro ao excluir: " . $stmt->error); }

header("Location: list_usuarios.php?ok=1");
