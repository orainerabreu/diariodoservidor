<?php
$erro = '';
$sucesso = '';
$link_simulado = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    
    // Verifica se e-mail existe
    $stmt = $pdo->prepare("SELECT id, nome_completo FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $token = bin2hex(random_bytes(32));
        // Salva token no banco (expira em 1 hora)
        $pdo->prepare("INSERT INTO recuperacao_senha (email, token, expira_em) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))")
            ->execute([$email, $token]);
        
        // SIMULAÇÃO DE ENVIO DE E-MAIL (Pois InfinityFree bloqueia SMTP padrão)
        $sucesso = "Um link de recuperação foi gerado!";
        $link_simulado = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/index.php?p=recuperar&token=" . $token;
    } else {
        $erro = "E-mail não encontrado.";
    }
}

// LÓGICA DE REDEFINIÇÃO (Se clicar no link)
$token_url = $_GET['token'] ?? '';
$etapa_nova_senha = false;

if ($token_url) {
    // Verifica validade do token
    $stmt = $pdo->prepare("SELECT email FROM recuperacao_senha WHERE token = ? AND expira_em > NOW() ORDER BY id DESC LIMIT 1");
    $stmt->execute([$token_url]);
    $dados_rec = $stmt->fetch();

    if ($dados_rec) {
        $etapa_nova_senha = true;
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nova_senha_reset'])) {
            $ns = $_POST['nova_senha_reset'];
            $hash = password_hash($ns, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE usuarios SET senha = ?, trocar_senha = 0 WHERE email = ?")->execute([$hash, $dados_rec['email']]);
            $pdo->prepare("DELETE FROM recuperacao_senha WHERE email = ?")->execute([$dados_rec['email']]);
            $sucesso = "Senha redefinida com sucesso! <a href='index.php?p=login' class='underline font-bold'>Faça login.</a>";
            $etapa_nova_senha = false;
        }
    } else {
        $erro = "Link inválido ou expirado.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Recuperar Senha</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-lg shadow-lg w-full max-w-md">
        <h2 class="text-2xl font-bold text-gray-800 mb-4 text-center">Recuperação de Senha</h2>
        
        <?php if ($sucesso): ?>
            <div class="bg-green-100 text-green-700 p-4 rounded mb-4 text-sm text-center">
                <?php echo $sucesso; ?>
                <?php if ($link_simulado): ?>
                    <div class="mt-4 p-2 bg-white border text-xs break-all text-gray-500">
                        <strong>(Simulação de E-mail)</strong> Link:<br>
                        <a href="<?php echo $link_simulado; ?>"><?php echo $link_simulado; ?></a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <?php if ($erro): ?><div class="bg-red-100 text-red-700 p-3 rounded mb-4 text-center"><?php echo $erro; ?></div><?php endif; ?>

        <?php if ($etapa_nova_senha): ?>
            <form method="POST">
                <label class="block mb-2 text-sm text-gray-600">Nova Senha</label>
                <input type="password" name="nova_senha_reset" required class="w-full border p-2 rounded mb-4">
                <button class="w-full bg-blue-600 text-white font-bold py-2 rounded">Salvar Senha</button>
            </form>
        <?php elseif (!$sucesso): ?>
            <form method="POST">
                <label class="block mb-2 text-sm text-gray-600">Digite seu e-mail cadastrado</label>
                <input type="email" name="email" required class="w-full border p-2 rounded mb-6" placeholder="seu@email.com">
                <button class="w-full bg-blue-600 text-white font-bold py-2 rounded hover:bg-blue-700">Enviar Link</button>
            </form>
            <div class="text-center mt-4"><a href="index.php?p=login" class="text-sm text-gray-500 hover:underline">Voltar</a></div>
        <?php endif; ?>
    </div>
</body>
</html>