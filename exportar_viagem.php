<?php
require 'conexao.php';
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Segurança/charset
$conn->set_charset('utf8mb4');

$id = isset($_GET['motorista_id']) ? (int)$_GET['motorista_id'] : 0;
if ($id <= 0) {
  die("Motorista não informado.");
}

/* Buscar nome do motorista */
$stmt = $conn->prepare("SELECT nome FROM motoristas WHERE id = ?");
if (!$stmt) die("Erro prepare motorista: " . $conn->error);
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
if (!$res || !$res->num_rows) die("Motorista não encontrado.");
$motorista = $res->fetch_assoc();
$stmt->close();

/* Buscar cidades dessa última viagem
   OBS: agora c.uf referencia estado.id; precisamos da sigla (e.uf) */
$sqlCidades = "
  SELECT c.nome AS cidade, e.uf AS uf
  FROM viagens v
  JOIN cidades c ON c.id = v.cidade_id
  JOIN estado  e ON e.id = c.uf
  WHERE v.motorista_id = ? 
  ORDER BY v.id ASC
";
$stmt = $conn->prepare($sqlCidades);
if (!$stmt) die("Erro prepare cidades: " . $conn->error);
$stmt->bind_param("i", $id);
$stmt->execute();
$cidadesRes = $stmt->get_result();
$cidades = $cidadesRes ? $cidadesRes->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();

/* Criar planilha */
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

/* Cabeçalho principal */
$sheet->setCellValue('A1', 'MOTORISTA');
$sheet->setCellValue('B1', $motorista['nome']);
$sheet->setCellValue('C1', 'DATA:');
$sheet->setCellValue('D1', '');

/* Cabeçalho das colunas */
$sheet->setCellValue('A2', 'CIDADE');
$sheet->setCellValue('B2', 'Nº PEDIDO');
$sheet->setCellValue('C2', 'Nº NOTA FISCAL');
$sheet->setCellValue('D2', 'QTDE VOLUME');

/* Preencher cidades */
$row = 3;
if (!empty($cidades)) {
  foreach ($cidades as $c) {
    // Se quiser exibir "Cidade (UF)":
    $sheet->setCellValue("A{$row}", $c['cidade'] . ' (' . $c['uf'] . ')');
    $row++;
  }
} else {
  $sheet->setCellValue("A{$row}", 'Sem cidades nesta viagem.');
}

/* Ajustar largura */
foreach (['A' => 30, 'B' => 18, 'C' => 22, 'D' => 18] as $col => $width) {
  $sheet->getColumnDimension($col)->setWidth($width);
}

/* Download */
$nomeSan = preg_replace('/[^a-z0-9_\-]+/i', '_', $motorista['nome']);
$filename = "viagem_" . strtolower($nomeSan) . ".xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
