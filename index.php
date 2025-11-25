<?php
// index.php - Versão 4.1 (Com Branding e Rotas Públicas)
session_start();
define('BASE_PATH', __DIR__);
require_once BASE_PATH . '/app/config.php';

// --- CARREGAR CONFIGURAÇÕES VISUAIS DO BANCO ---
$config = [];
try {
    $stmt = $pdo->query("SELECT * FROM configuracoes");
    while ($row = $stmt->fetch()) {
        $config[$row['chave']] = $row['valor'];
    }
} catch (Exception $e) {
    // Fallback se der erro
    $config = ['nome_sistema' => 'Diário', 'cor_principal' => 'blue'];
}
// Disponibiliza globalmente
$GLOBALS['sys_config'] = $config;

// --- ROTEAMENTO ---
$pagina = $_GET['p'] ?? 'login';
$modulo = $_GET['m'] ?? null;

// Páginas permitidas (Lista Branca)
// Adicionamos 'cadastro' e 'recuperar' aqui
$paginas_core = ['login', 'dashboard', 'admin', 'logout', 'trocar_senha', 'cadastro', 'recuperar'];

// 1. Módulos
if ($modulo) {
    $modulo = basename($modulo);
    $caminho = BASE_PATH . "/modules/{$modulo}";
    if (file_exists($caminho)) include $caminho;
    else echo "Módulo não encontrado.";
}
// 2. Core
elseif (in_array($pagina, $paginas_core)) {
    $arquivo = BASE_PATH . "/app/{$pagina}.php";
    if (file_exists($arquivo)) include $arquivo;
    else echo "Página não encontrada.";
}
// 3. Bloqueio
else {
    header("Location: index.php?p=login");
    exit;
}
?>