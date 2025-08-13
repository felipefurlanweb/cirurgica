<?php
include_once 'conexao.php';
header('Content-Type: application/json; charset=utf-8');

$input = json_decode(file_get_contents("php://input"), true);

$motorista_raw = $input['motorista_id'] ?? null;
$motorista_id  = is_null($motorista_raw) || $motorista_raw === '' ? null : (int)$motorista_raw;

$cidade_nome = trim($input['cidade_nome'] ?? '');
$cidade_uf   = trim($input['cidade_uf'] ?? ''); // sigla UF

$urgente = (isset($input['urgente']) && $input['urgente'] === 'sim') ? 'sim' : 'nao';
$coleta  = (isset($input['coleta'])  && $input['coleta']  === 'sim') ? 'sim' : 'nao';

if ($cidade_nome === '' || $cidade_uf === '') {
  http_response_code(400);
  echo json_encode(['success'=>false,'error'=>'Dados incompletos']);
  exit;
}

// UF -> estado.id
$stmt = $conn->prepare("SELECT id FROM estado WHERE uf = ? LIMIT 1");
if (!$stmt) { http_response_code(500); echo json_encode(['success'=>false,'error'=>$conn->error]); exit; }
$stmt->bind_param("s", $cidade_uf);
$stmt->execute();
$e = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$e) { http_response_code(404); echo json_encode(['success'=>false,'error'=>'UF não encontrada']); exit; }
$estado_id = (int)$e['id'];

// Cidade
$stmt = $conn->prepare("SELECT id FROM cidades WHERE nome = ? AND uf = ? LIMIT 1");
if (!$stmt) { http_response_code(500); echo json_encode(['success'=>false,'error'=>$conn->error]); exit; }
$stmt->bind_param("si", $cidade_nome, $estado_id);
$stmt->execute();
$c = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$c) { http_response_code(404); echo json_encode(['success'=>false,'error'=>'Cidade não encontrada']); exit; }
$cidade_id = (int)$c['id'];

/*
  UPSERT idempotente:
  - usamos NULLIF(?,0) para permitir passar 0 e virar NULL
  - o índice único é (cidade_id, motorista_id_norm) onde motorista_id_norm = IFNULL(motorista_id,0)
*/
$sql = "
  INSERT INTO viagens (motorista_id, cidade_id, data, urgente, coleta)
  VALUES (NULLIF(?,0), ?, NOW(), ?, ?)
  ON DUPLICATE KEY UPDATE
    motorista_id = NULLIF(VALUES(motorista_id),0),
    urgente = VALUES(urgente),
    coleta  = VALUES(coleta),
    data = NOW()
";
$stmt = $conn->prepare($sql);
if (!$stmt) { http_response_code(500); echo json_encode(['success'=>false,'error'=>$conn->error]); exit; }

$motorista_param = is_null($motorista_id) ? 0 : $motorista_id; // 0 -> vira NULL pelo NULLIF
$stmt->bind_param("iiss", $motorista_param, $cidade_id, $urgente, $coleta);
$stmt->execute();

$rows = $stmt->affected_rows; // 1 insert | 2 update | 0 noop
$stmt->close();

$mode = ($rows === 1) ? 'insert' : (($rows === 2) ? 'updated' : 'noop');
echo json_encode(['success'=>true, 'mode'=>$mode]);
