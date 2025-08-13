<?php
include_once 'conexao.php';
header('Content-Type: application/json; charset=utf-8');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { echo json_encode([]); exit; }

/*
  Retorna, para o motorista escolhido, a ÚLTIMA viagem de cada cidade,
  junto com UF e coordenadas da cidade (para evitar geocodificação).
*/
$sql = "
  SELECT
    c.nome,
    e.uf,
    c.latitude,
    c.longitude,
    v.urgente,
    v.coleta
  FROM viagens v
  JOIN cidades c ON c.id = v.cidade_id
  JOIN estado  e ON e.id = c.uf
  JOIN (
    SELECT cidade_id, MAX(id) AS max_id
    FROM viagens
    WHERE motorista_id = ?
    GROUP BY cidade_id
  ) ult ON ult.max_id = v.id
  WHERE v.motorista_id = ?
  ORDER BY c.nome
";

$stmt = $conn->prepare($sql);
if (!$stmt) { echo json_encode([]); exit; }
$stmt->bind_param("ii", $id, $id);
$stmt->execute();
$res = $stmt->get_result();

$out = [];
while ($row = $res->fetch_assoc()) {
  $out[] = [
    'nome'      => $row['nome'],
    'uf'        => $row['uf'],
    'latitude'  => $row['latitude'],
    'longitude' => $row['longitude'],
    'urgente'   => $row['urgente'] ?? 'nao',
    'coleta'    => $row['coleta']  ?? 'nao',
  ];
}

echo json_encode($out, JSON_UNESCAPED_UNICODE);
