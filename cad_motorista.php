<?php
include 'conexao.php';

$stmt = $conn->prepare("INSERT INTO motoristas (nome, telefone) VALUES (?, ?)");
$stmt->bind_param("ss", $_POST['nome'], $_POST['telefone']);
$stmt->execute();

header("Location: list_motoristas.php");
