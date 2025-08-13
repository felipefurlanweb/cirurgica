<?php
include_once 'conexao.php';

$stmt = $conn->prepare("UPDATE viagens SET motorista_id = ?, cidade_id = ? WHERE id = ?");
$stmt->bind_param("iii", $_POST['motorista_id'], $_POST['cidade_id'], $_POST['id']);
$stmt->execute();

header("Location: list_viagens.php");
