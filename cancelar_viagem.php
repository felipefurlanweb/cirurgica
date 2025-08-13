<?php
include_once 'conexao.php';

// parâmetros
$motorista_id = isset($_GET['motorista_id']) ? (int)$_GET['motorista_id'] : 0;
$data_param   = $_GET['data'] ?? '';

if ($motorista_id <= 0 || !preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $data_param)) {
  header("Location: list_viagens.php?erro=parametros");
  exit;
}

// apaga todas as cidades dessa data para esse motorista
$stmt = $conn->prepare("DELETE FROM viagens WHERE motorista_id = ? AND data = ?");
$stmt->bind_param("is", $motorista_id, $data_param);
$stmt->execute();
$apagadas = $stmt->affected_rows;
$stmt->close();

// volta para a lista com feedback
header("Location: list_viagens.php?canceladas={$apagadas}");
exit;