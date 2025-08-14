<?php
include_once 'conexao.php';

$motorista_id = $_GET['motorista_id'] ?? 0;

$stmt = $conn->prepare("SELECT id, cidade_id FROM viagens WHERE motorista_id = ?");
$stmt->bind_param("i", $motorista_id);
$stmt->execute();
$stmt->bind_result($viagem_id, $cidade_id);

$viagens = [];
while ($stmt->fetch()) {
    $viagens[] = ['id' => $viagem_id, 'cidade_id' => $cidade_id];
}
$stmt->close();

foreach ($viagens as $viagem) {
    if ($viagem['cidade_id']) {
        $stmt = $conn->prepare("UPDATE cidades SET latitude = NULL, longitude = NULL WHERE id = ?");
        $stmt->bind_param("i", $viagem['cidade_id']);
        $stmt->execute();
        $stmt->close();
    }
    $stmt = $conn->prepare("DELETE FROM viagens WHERE id = ?");
    $stmt->bind_param("i", $viagem['id']);
    $stmt->execute();
    $stmt->close();
}

header("Location: list_viagens.php");
