<?php
// exportar_geral.php
declare(strict_types=1);

require_once 'conexao.php';
require_once __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// ---------- Entrada (opcional, só para título) ----------
$dataTitulo = trim($_GET['data'] ?? '');
if ($dataTitulo !== '' && !preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $dataTitulo)) {
  // Se vier em outro formato, apenas ignora e não quebra
  $dataTitulo = '';
}

// ---------- Busca motoristas (todos) ----------
$motoristas = [];
$res = $conn->query("SELECT id, nome FROM motoristas ORDER BY nome");
while ($row = $res->fetch_assoc()) {
  $motoristas[] = ['id' => (int)$row['id'], 'nome' => $row['nome']];
}

// Se não houver motoristas, ainda assim gera uma planilha indicando vazio
if (count($motoristas) === 0) {
  $spreadsheet = new Spreadsheet();
  $sheet = $spreadsheet->getActiveSheet();
  $titulo = 'LOGÍSTICA - CONTROLE DIÁRIO INTERNO' . ($dataTitulo ? ' - ' . $dataTitulo : '');
  $sheet->setCellValue('A1', $titulo);
  $sheet->mergeCells('A1:D1');
  $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
  $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

  $sheet->setCellValue('A3', 'Nenhum motorista cadastrado.');
  $sheet->getColumnDimension('A')->setWidth(40);

  header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
  header('Content-Disposition: attachment; filename="export_geral.xlsx"');
  header('Cache-Control: max-age=0');
  (new Xlsx($spreadsheet))->save('php://output');
  exit;
}

// ---------- Para cada motorista, pega ÚLTIMA viagem por cidade ----------
/*
  A ideia é, para cada motorista M:
  SELECT c.nome, e.uf
  FROM viagens v
  JOIN cidades c ON c.id = v.cidade_id
  JOIN estado e  ON e.id = c.uf
  JOIN (
    SELECT cidade_id, MAX(id) AS max_id
    FROM viagens WHERE motorista_id = M
    GROUP BY cidade_id
  ) ult ON ult.max_id = v.id
  WHERE v.motorista_id = M
  ORDER BY c.nome
*/
$viagensPorMotorista = []; // [motorista_id] => [ 'nome' => , 'cidades' => [ "Cidade - UF", ... ] ]

$stmt = $conn->prepare("
  SELECT c.nome AS cidade, e.uf AS uf
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
  ORDER BY v.id ASC
");

foreach ($motoristas as $m) {
  $lista = [];
  if ($stmt) {
    $stmt->bind_param('ii', $m['id'], $m['id']);
    $stmt->execute();
    $rs = $stmt->get_result();
    while ($row = $rs->fetch_assoc()) {
      $lista[] = $row['cidade'] . ' - ' . $row['uf'];
    }
  }
  $viagensPorMotorista[$m['id']] = [
    'nome'    => $m['nome'],
    'cidades' => $lista, // pode estar vazio — e tudo bem
  ];
}
if ($stmt) $stmt->close();

// ---------- Monta planilha ----------
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Título
$titulo = 'LOGÍSTICA - CONTROLE DIÁRIO INTERNO' . ($dataTitulo ? ' - ' . $dataTitulo : '');
$sheet->setCellValue('A1', $titulo);
// Mescla título sobre todas as colunas que vamos usar
$colCount = count($motoristas);
$lastColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colCount);
$sheet->mergeCells("A1:{$lastColLetter}1");
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Cabeçalho com nomes dos motoristas (linha 2)
$colIndex = 1; // 1 = Coluna A
foreach ($motoristas as $m) {
  $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
  $sheet->setCellValue($colLetter . '2', $m['nome']);
  $sheet->getStyle($colLetter . '2')->getFont()->setBold(true);
  $sheet->getColumnDimension($colLetter)->setWidth(28);
  $colIndex++;
}

// Preenche cidades (cada motorista em sua coluna, alfabeticamente já vem do SQL)
$maxLinhas = 0;
$colIndex  = 1;
foreach ($motoristas as $m) {
  $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
  $lin = 3; // começa na linha 3
  foreach ($viagensPorMotorista[$m['id']]['cidades'] as $cidadeUF) {
    $sheet->setCellValue($colLetter . $lin, $cidadeUF);
    $lin++;
  }
  $maxLinhas = max($maxLinhas, $lin - 3); // quantidade preenchida nessa coluna
  $colIndex++;
}

// Borda fina no cabeçalho e nas células que foram preenchidas
$bordasAteLinha = $maxLinhas + 2; // +2 porque começamos a preencher a partir da linha 3
$rangeBordas = "A2:{$lastColLetter}{$bordasAteLinha}";
$sheet->getStyle($rangeBordas)->getBorders()->getAllBorders()
      ->setBorderStyle(Border::BORDER_THIN)
      ->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFCCCCCC'));

// Alinhamento topo nas cidades
$sheet->getStyle("A3:{$lastColLetter}{$bordasAteLinha}")
      ->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

// Congela painel para fixar título e cabeçalho
$sheet->freezePane('A3');

// ---------- Saída ----------
$filename = 'export_geral.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"{$filename}\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
