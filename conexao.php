<?php
$conn = new mysqli("localhost", "usuario", "senha", "guerra");
if ($conn->connect_error) die("Erro: " . $conn->connect_error);
// Garanta UTF-8 em TUDO na conexão
$conn->set_charset('utf8mb4');
$conn->query("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
$conn->query("SET collation_connection = utf8mb4_unicode_ci");