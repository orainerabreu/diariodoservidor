<?php
// app/trocar_senha.php - Versão 4.0

// 1. SEGURANÇA: Se não estiver logado, manda para o login
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?p=login");
    exit;
}

$erro = '';

// 2. PROCESSAMENTO
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nova = $_POST['nova_senha'] ?? '';
    $confirma = $_POST['confirma_senha'] ?? '';

    if (strlen($nova) < 6) {
        $erro = "A senha deve ter no mínimo 6 caracteres.";
    } elseif ($nova !== $confirma) {
        $erro = "As senhas não conferem.";
    } else {
        // Atualiza no Banco
        $hash = password_hash($nova, PASSWORD_DEFAULT);
        $id = $_SESSION['user_id'];
        
        try {
            // Atualiza senha e remove a obrigação de troca
            $stmt = $pdo->prepare("UPDATE usuarios SET senha = :senha, trocar_senha = 0 WHERE id = :id");
            $stmt->execute([':senha' => $hash, ':id' => $id]);

            // Atualiza a sessão para liberar o acesso ao dashboard
            $_SESSION['trocar_senha'] = 0;

            // Redireciona para o Dashboard (Via Roteador)
            header("Location: index.php?p=dashboard");
            exit;

        } catch (PDOException $e) {
            $erro = "Erro ao atualizar: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trocar Senha - Obrigatório</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-yellow-50 h-screen flex items-center justify-center font-sans">
    <div class="w-full max-w-md bg-white rounded-lg shadow-lg p-8 border-t-4 border-yellow-500">
        
        <div class="text-center mb-6">
            <h2 class="text-xl font-bold text-gray-800">Segurança</h2>
            <p class="text-sm text-gray-500">Defina sua nova senha de acesso.</p>
        </div>
        
        <?php if ($erro): ?>
            <div class="bg-red-100 text-red-700 p-3 rounded mb-4 text-sm border border-red-200">
                <?php echo $erro; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-4">
                <label class="block text-gray-700 text-xs font-bold mb-2 uppercase">Nova Senha</label>
                <input type="password" name="nova_senha" placeholder="Mínimo 6 caracteres" required 
                       class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-yellow-500">
            </div>

            <div class="mb-6">
                <label class="block text-gray-700 text-xs font-bold mb-2 uppercase">Confirme a Senha</label>
                <input type="password" name="confirma_senha" placeholder="Repita a senha" required 
                       class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-yellow-500">
            </div>

            <button type="submit" class="w-full bg-yellow-600 text-white font-bold py-3 rounded hover:bg-yellow-700 transition shadow">
                Salvar Nova Senha
            </button>
        </form>

        <div class="text-center mt-6 pt-4 border-t border-gray-100">
            <a href="index.php?p=logout" class="text-xs text-gray-400 hover:text-red-500 transition">
                Cancelar e Sair
            </a>
        </div>
    </div>
</body>
</html>