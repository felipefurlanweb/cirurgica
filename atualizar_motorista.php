<?php
include_once 'conexao.php';

$stmt = $conn->prepare("UPDATE motoristas SET nome = ?, telefone = ? WHERE id = ?");
$stmt->bind_param("ssi",
    $_POST['nome'],
    $_POST['telefone'],
    $_POST['id']
);
$stmt->execute();

header("Location: list_motoristas.php");
