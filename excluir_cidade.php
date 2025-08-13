<?php
include_once 'conexao.php';

$id = $_GET['id'] ?? 0;
$stmt = $conn->prepare("DELETE FROM cidades WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

header("Location: list_cidades.php");
