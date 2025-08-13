<?php
// Endpoint server-side do DataTables: paginação, busca e ordenação
header('Content-Type: application/json; charset=utf-8');

require_once 'conexao.php';
$conn->set_charset('utf8mb4');
$conn->query("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

// Parâmetros do DataTables (POST)
$draw   = intval($_POST['draw']   ?? 0);
$start  = intval($_POST['start']  ?? 0);
$length = intval($_POST['length'] ?? 10);
$searchValue = trim($_POST['search']['value'] ?? '');
$order      = $_POST['order'][0] ?? ['column' => 1, 'dir' => 'asc']; // default: cidade ASC
$orderCol   = intval($order['column']);
$orderDir   = strtolower($order['dir']) === 'desc' ? 'DESC' : 'ASC';

// Mapeamento seguro de índices -> colunas no BD
// OBS: a última coluna (Ações) não entra em ORDER BY
$columnsMap = [
  0 => 'c.id',
  1 => 'c.nome',
  2 => 'e.uf',
  3 => 'c.urgente',
  4 => 'c.coleta'
];
$orderBy = $columnsMap[$orderCol] ?? 'c.nome';

// Base query
$baseFrom = " FROM cidades c JOIN estado e ON e.id = c.uf ";

// Quantidade total (sem filtro)
$sqlTotal = "SELECT COUNT(*) AS total " . $baseFrom;
$resTotal = $conn->query($sqlTotal);
$recordsTotal = (int)($resTotal->fetch_assoc()['total'] ?? 0);

// Filtro (WHERE)
$where = "";
$params = [];
$types  = "";

if ($searchValue !== '') {
  // Busca em nome, uf, urgente, coleta
  $where = " WHERE (c.nome LIKE ? OR e.uf LIKE ? OR c.urgente LIKE ? OR c.coleta LIKE ?)";
  $like = "%{$searchValue}%";
  $params = [$like, $like, $like, $like];
  $types  = "ssss";
}

// Contagem com filtro
if ($where) {
  $stmtCount = $conn->prepare("SELECT COUNT(*) AS total " . $baseFrom . $where);
  $stmtCount->bind_param($types, ...$params);
  $stmtCount->execute();
  $resCount = $stmtCount->get_result();
  $recordsFiltered = (int)($resCount->fetch_assoc()['total'] ?? 0);
  $stmtCount->close();
} else {
  $recordsFiltered = $recordsTotal;
}

// Dados paginados
$sqlData = "SELECT c.id, c.nome, e.uf, c.urgente, c.coleta " . $baseFrom . $where . " ORDER BY $orderBy $orderDir LIMIT ?, ?";
if ($where) {
  $stmtData = $conn->prepare($sqlData);
  // adicionar limites
  $params[] = $start;
  $params[] = $length;
  $types   .= "ii";
  $stmtData->bind_param($types, ...$params);
} else {
  $stmtData = $conn->prepare($sqlData);
  $stmtData->bind_param("ii", $start, $length);
}

$stmtData->execute();
$result = $stmtData->get_result();

// Montagem do array de saída
$data = [];
while ($row = $result->fetch_assoc()) {
  $urgenteHtml = ($row['urgente'] === 'sim')
    ? '<span class="badge bg-danger">Sim</span>'
    : '<span class="badge bg-secondary">Não</span>';

  $coletaHtml = ($row['coleta'] === 'sim')
    ? '<span class="badge bg-warning text-dark">Sim</span>'
    : '<span class="badge bg-secondary">Não</span>';

  $data[] = [
    'id'           => (int)$row['id'],
    'nome'         => htmlspecialchars($row['nome'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
    'uf'           => htmlspecialchars($row['uf'],   ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
    'urgente_html' => $urgenteHtml,
    'coleta_html'  => $coletaHtml
  ];
}
$stmtData->close();

// Resposta no formato esperado pelo DataTables
echo json_encode([
  'draw'            => $draw,
  'recordsTotal'    => $recordsTotal,
  'recordsFiltered' => $recordsFiltered,
  'data'            => $data
], JSON_UNESCAPED_UNICODE);
