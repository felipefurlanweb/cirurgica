<?php
include_once 'conexao.php';

$id = $_GET['id'] ?? 0;

// Select cidade_id from viagens
$stmt = $conn->prepare("SELECT cidade_id FROM viagens WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($cidade_id);
$stmt->fetch();
$stmt->close();

// Set latitude and longitude to NULL in cidades table
if ($cidade_id) {
    $stmt = $conn->prepare("UPDATE cidades SET latitude = NULL, longitude = NULL WHERE id = ?");
    $stmt->bind_param("i", $cidade_id);
    $stmt->execute();
    $stmt->close();
}

$stmt = $conn->prepare("DELETE FROM viagens WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

header("Location: list_viagens.php");
