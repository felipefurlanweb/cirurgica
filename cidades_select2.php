<?php
require 'conexao.php';

// Termo de busca do Select2
$q = trim($_GET['q'] ?? '');

if ($q === '') {
    echo json_encode([]);
    exit;
}

// Buscar IDs de cidades que já estão no mapa
$cidadesMapa = [];
$res = $conn->query("SELECT id FROM cidades WHERE latitude IS NOT NULL AND longitude IS NOT NULL");
while ($row = $res->fetch_assoc()) {
    $cidadesMapa[] = (int)$row['id'];
}

// Buscar cidades pelo termo
$stmt = $conn->prepare("
    SELECT c.id, c.nome, e.uf
    FROM cidades c
    JOIN estado e ON e.id = c.uf
    LEFT JOIN (
        SELECT cidade_id, MAX(id) AS max_id
        FROM viagens
        GROUP BY cidade_id
    ) ult ON ult.cidade_id = c.id
    LEFT JOIN viagens v ON v.id = ult.max_id
    WHERE c.nome LIKE CONCAT('%', ?, '%') 
    AND c.nome <> 'Rio Claro' AND e.uf <> 'SP' 
      AND (v.motorista_id IS NULL OR v.motorista_id = 0)
    ORDER BY c.nome ASC
    LIMIT 20
");

$stmt->bind_param("s", $q);
$stmt->execute();
$res = $stmt->get_result();

$resultado = [];
while ($row = $res->fetch_assoc()) {
    $jaNoMapa = in_array((int)$row['id'], $cidadesMapa);

    if ($jaNoMapa) {
        $resultado[] = [
            'id' => null,
            'text' => '⚠ ' . $row['nome'] . ' (' . $row['uf'] . ') — já está cadastrada no mapa',
            'ja_no_mapa' => true
        ];
    } else {
        $resultado[] = [
            'id' => $row['id'],
            'text' => $row['nome'] . ' (' . $row['uf'] . ')',
            'nome' => $row['nome'],
            'uf'   => $row['uf']
        ];
    }
}

echo json_encode($resultado);
