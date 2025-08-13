<?php
include 'conexao.php';

$stmt = $conn->prepare("INSERT INTO viagens (motorista_id, cidade_id) VALUES (?, ?)");
$stmt->bind_param("ii", $_POST['motorista_id'], $_POST['cidade_id']);
$stmt->execute();

header("Location: index.php");
