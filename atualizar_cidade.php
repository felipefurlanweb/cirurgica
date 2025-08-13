<?php
include_once 'conexao.php';

$id    = $_POST['id'];
$nome    = $_POST['nome'];
$uf_id   = $_POST['uf'] ?? 0;       // <- id da tabela estado
$urgente = $_POST['urgente'] ?? 'nao';
$coleta = $_POST['coleta'] ?? 'nao';

$stmt = $conn->prepare("UPDATE cidades SET nome = ?, uf = ?, urgente = ?, coleta = ? WHERE id = ?");
$stmt->bind_param("sissi", $nome, $uf_id, $urgente, $coleta, $id);
$stmt->execute();

header("Location: list_cidades.php");
