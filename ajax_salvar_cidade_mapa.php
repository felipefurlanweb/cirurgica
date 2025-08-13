<?php
// Salva lat/lon da cidade (para que o marker apareça sempre no mapa)
header('Content-Type: application/json; charset=utf-8');
require_once 'conexao.php';

$data = json_decode(file_get_contents('php://input'), true);
$cidade_nome = trim($data['cidade_nome'] ?? '');
$cidade_uf   = trim($data['cidade_uf'] ?? ''); // sigla
$lat         = isset($data['lat']) ? (float)$data['lat'] : null;
$lon         = isset($data['lon']) ? (float)$data['lon'] : null;

if ($cidade_nome === '' || $cidade_uf === '' || $lat === null || $lon === null) {
  http_response_code(400);
  echo json_encode(['error' => 'Dados incompletos'], JSON_UNESCAPED_UNICODE);
  exit;
}

// Descobre estado.id pela sigla
$stmt = $conn->prepare("SELECT id FROM estado WHERE uf = ? LIMIT 1");
$stmt->bind_param("s", $cidade_uf);
$stmt->execute();
$estado = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$estado) {
  http_response_code(404);
  echo json_encode(['error' => 'UF não encontrada'], JSON_UNESCAPED_UNICODE);
  exit;
}
$estado_id = (int)$estado['id'];

// Cidade
$stmt = $conn->prepare("SELECT id FROM cidades WHERE nome = ? AND uf = ? LIMIT 1");
$stmt->bind_param("si", $cidade_nome, $estado_id);
$stmt->execute();
$c = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$c) {
  http_response_code(404);
  echo json_encode(['error' => 'Cidade não encontrada'], JSON_UNESCAPED_UNICODE);
  exit;
}
$cidade_id = (int)$c['id'];

// Atualiza coordenadas
$stmt = $conn->prepare("UPDATE cidades SET latitude = ?, longitude = ? WHERE id = ?");
$stmt->bind_param("ddi", $lat, $lon, $cidade_id);
$ok = $stmt->execute();
$stmt->close();

echo json_encode(['success' => (bool)$ok], JSON_UNESCAPED_UNICODE);
