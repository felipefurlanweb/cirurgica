<?php
include_once 'conexao.php';

$nome    = $_POST['nome'] ?? '';
$uf_id   = $_POST['uf'] ?? 0;       // <- id da tabela estado
$urgente = $_POST['urgente'] ?? 'nao';
$coleta  = $_POST['coleta'] ?? 'nao';

if (!$nome || !$uf_id) { header("Location: form_cidade.php?erro=1"); exit; }

$stmt = $conn->prepare("INSERT INTO cidades (nome, uf, urgente, coleta) VALUES (?, ?, ?, ?)");
$stmt->bind_param("siss", $nome, $uf_id, $urgente, $coleta);
$stmt->execute();

header("Location: list_cidades.php");
