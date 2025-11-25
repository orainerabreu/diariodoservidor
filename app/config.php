<?php
// Configuração do Banco de Dados
define('DB_HOST', 'sql100.infinityfree.com');
define('DB_NAME', 'if0_40500874_iserv');
define('DB_USER', 'if0_40500874');
define('DB_PASS', 'Kiane140592');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro de Conexão: " . $e->getMessage());
}
?>