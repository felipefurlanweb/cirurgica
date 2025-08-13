<?php
include_once 'conexao.php';

// Define os headers para forçar o download como CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment;filename=viagens.csv');

// Abre o "arquivo" de saída
$output = fopen('php://output', 'w');

// Escreve o cabeçalho das colunas
fputcsv($output, ['ID', 'Motorista', 'Cidade', 'Estado']);

// Consulta as viagens com os nomes
$sql = "SELECT v.id, m.nome AS motorista, c.nome AS cidade, c.estado
        FROM viagens v
        JOIN motoristas m ON v.motorista_id = m.id
        JOIN cidades c ON v.cidade_id = c.id
        ORDER BY v.id ASC";

$resultado = $conn->query($sql);

// Escreve os dados linha por linha no CSV
while ($linha = $resultado->fetch_assoc()) {
    fputcsv($output, [
        $linha['id'],
        $linha['motorista'],
        $linha['cidade'],
        $linha['estado']
    ]);
}

// Fecha o stream de saída
fclose($output);
exit;
