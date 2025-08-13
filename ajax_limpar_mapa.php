<?php
// Zera latitude/longitude de TODAS as cidades e apaga TODAS as viagens
header('Content-Type: application/json; charset=utf-8');
require_once 'conexao.php';

$conn->set_charset('utf8mb4');
$conn->query("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

// (Opcional) restringir a admin:
// session_start();
// if (($_SESSION['tipo'] ?? '') !== 'admin') {
//   http_response_code(403);
//   echo json_encode(['error' => 'Acesso negado']);
//   exit;
// }

$conn->begin_transaction();

try {
  // apaga todas as viagens
  if ($conn->query("DELETE FROM viagens") === false) {
    throw new Exception($conn->error);
  }
  $apagadas = $conn->affected_rows;

  // zera todas as coordenadas
  if ($conn->query("UPDATE cidades SET latitude = NULL, longitude = NULL WHERE latitude IS NOT NULL OR longitude IS NOT NULL") === false) {
    throw new Exception($conn->error);
  }
  $zeradas = $conn->affected_rows;

  $conn->commit();

  echo json_encode([
    'success' => true,
    'viagens_apagadas' => $apagadas,
    'cidades_zeradas'  => $zeradas
  ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
  $conn->rollback();
  http_response_code(500);
  echo json_encode([
    'success' => false,
    'error'   => 'Falha ao limpar mapa: ' . $e->getMessage()
  ], JSON_UNESCAPED_UNICODE);
}
