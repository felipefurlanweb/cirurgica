<?php
include_once 'conexao.php';

$id = $_GET['id'] ?? 0;

$stmt = $conn->prepare("DELETE FROM viagens WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

header("Location: list_viagens.php");
