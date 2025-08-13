<?php
// Zera latitude/longitude de UMA cidade e apaga TODAS as viagens vinculadas a ela
header('Content-Type: application/json; charset=utf-8');
require_once 'conexao.php';

$conn->set_charset('utf8mb4');
$conn->query("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

$input = json_decode(file_get_contents('php://input'), true);

$cidade_id   = isset($input['id']) ? (int)$input['id'] : 0;
$cidade_nome = trim($input['cidade_nome'] ?? '');
$cidade_uf   = trim($input['cidade_uf'] ?? ''); // sigla UF

if ($cidade_id <= 0 && ($cidade_nome === '' || $cidade_uf === '')) {
  http_response_code(400);
  echo json_encode(['error' => 'Informe id OU (cidade_nome e cidade_uf).'], JSON_UNESCAPED_UNICODE);
  exit;
}

// Resolve cidade_id por nome+UF, se necessário
if ($cidade_id <= 0) {
  // UF -> estado.id
  $stmt = $conn->prepare("SELECT id FROM estado WHERE uf = ? LIMIT 1");
  if (!$stmt) { http_response_code(500); echo json_encode(['error'=>$conn->error]); exit; }
  $stmt->bind_param("s", $cidade_uf);
  $stmt->execute();
  $ufRow = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if (!$ufRow) { http_response_code(404); echo json_encode(['error' => 'UF não encontrada.'], JSON_UNESCAPED_UNICODE); exit; }
  $estado_id = (int)$ufRow['id'];

  $stmt = $conn->prepare("SELECT id FROM cidades WHERE nome = ? AND uf = ? LIMIT 1");
  if (!$stmt) { http_response_code(500); echo json_encode(['error'=>$conn->error]); exit; }
  $stmt->bind_param("si", $cidade_nome, $estado_id);
  $stmt->execute();
  $cRow = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if (!$cRow) { http_response_code(404); echo json_encode(['error' => 'Cidade não encontrada.'], JSON_UNESCAPED_UNICODE); exit; }
  $cidade_id = (int)$cRow['id'];
}

$conn->begin_transaction();

try {
  // apaga viagens da cidade
  $stmt = $conn->prepare("DELETE FROM viagens WHERE cidade_id = ?");
  if (!$stmt) throw new Exception($conn->error);
  $stmt->bind_param("i", $cidade_id);
  if (!$stmt->execute()) throw new Exception($stmt->error);
  $apagadas = $stmt->affected_rows;
  $stmt->close();

  // zera coordenadas da cidade
  $stmt = $conn->prepare("UPDATE cidades SET latitude = NULL, longitude = NULL WHERE id = ?");
  if (!$stmt) throw new Exception($conn->error);
  $stmt->bind_param("i", $cidade_id);
  if (!$stmt->execute()) throw new Exception($stmt->error);
  $zeradas = $stmt->affected_rows;
  $stmt->close();

  $conn->commit();

  echo json_encode([
    'success' => true,
    'cidade_id' => $cidade_id,
    'viagens_apagadas' => $apagadas,
    'cidade_zerada'    => $zeradas
  ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
  $conn->rollback();
  http_response_code(500);
  echo json_encode(['success' => false, 'error' => 'Falha ao remover cidade: '.$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
