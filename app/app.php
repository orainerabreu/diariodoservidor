<?php
if (isset($_SESSION['user_id'])) { header("Location: index.php?p=dashboard"); exit; }

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $_POST['usuario'] ?? '';
    $pass = $_POST['senha'] ?? '';
    
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = :u AND ativo = 1");
    $stmt->execute([':u' => $user]);
    $dados = $stmt->fetch();

    if ($dados && password_verify($pass, $dados['senha'])) {
        $_SESSION['user_id'] = $dados['id'];
        $_SESSION['user_nome'] = $dados['nome_completo'];
        $_SESSION['user_nivel'] = $dados['nivel'];
        $_SESSION['trocar_senha'] = $dados['trocar_senha'];
        
        if ($dados['trocar_senha'] == 1) header("Location: index.php?p=trocar_senha");
        else header("Location: index.php?p=dashboard");
        exit;
    } else {
        $erro = "Credenciais inválidas.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Login - Diário v4.0</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-lg shadow-lg w-96">
        <h1 class="text-2xl font-bold text-center text-blue-900 mb-6">Diário do Servidor</h1>
        <?php if ($erro): ?><div class="bg-red-100 text-red-700 p-2 rounded mb-4 text-sm text-center"><?php echo $erro; ?></div><?php endif; ?>
        <form method="POST">
            <input type="text" name="usuario" placeholder="Usuário" required class="w-full mb-4 px-3 py-2 border rounded">
            <input type="password" name="senha" placeholder="Senha" required class="w-full mb-6 px-3 py-2 border rounded">
            <button class="w-full bg-blue-900 text-white font-bold py-2 rounded hover:bg-blue-800">Entrar</button>
        </form>
    </div>
</body>
</html>