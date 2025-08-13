<?php
// Remove a atribuição do motorista para uma cidade específica
// (na prática, seta motorista_id = NULL para esse par)
header('Content-Type: application/json; charset=utf-8');
require_once 'conexao.php';

$motorista_id = isset($_POST['motorista_id']) ? (int)$_POST['motorista_id'] : 0;
$cidade_id    = isset($_POST['cidade_id'])    ? (int)$_POST['cidade_id']    : 0;

if ($motorista_id <= 0 || $cidade_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Parâmetros inválidos.']);
    exit;
}

$stmt = $conn->prepare("UPDATE viagens SET motorista_id = NULL WHERE motorista_id = ? AND cidade_id = ?");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $conn->error]);
    exit;
}
$stmt->bind_param("ii", $motorista_id, $cidade_id);
$stmt->execute();
$rows = $stmt->affected_rows;
$stmt->close();

// $rows pode ser 0 se já estava NULL ou se não havia linha correspondente
echo json_encode(['success' => true, 'rows' => $rows]);
