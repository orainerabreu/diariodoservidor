<?php
$cfg = $GLOBALS['sys_config'];
if ($cfg['permite_cadastro'] != 1) { echo "Cadastro desativado."; exit; }
$cor = $cfg['cor_principal'] ?? 'blue';

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ... (Lógica de Cadastro da Versão 4.0 - Não precisa mudar)
    $nome = $_POST['nome'];
    $user = $_POST['usuario'];
    $email = $_POST['email'];
    $pass = $_POST['senha'];
    $pass_conf = $_POST['confirma_senha'];

    if ($pass !== $pass_conf) { $erro = "As senhas não conferem.";
    } elseif (strlen($pass) < 6) { $erro = "Senha muito curta (mínimo 6).";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = ? OR email = ?");
        $stmt->execute([$user, $email]);
        if ($stmt->rowCount() > 0) { $erro = "Usuário ou E-mail já cadastrados.";
        } else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO usuarios (nome_completo, usuario, email, senha, nivel, ativo, trocar_senha) VALUES (?, ?, ?, ?, 'servidor', 1, 0)");
            if ($stmt->execute([$nome, $user, $email, $hash])) { $sucesso = true;
            } else { $erro = "Erro ao cadastrar."; }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Cadastro - <?php echo $cfg['nome_sistema']; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: colors.<?php echo $cor; ?>
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 bg-white p-8 rounded-xl shadow-lg">
        <?php if ($sucesso): ?>
            <div class="text-center">
                <h2 class="text-2xl font-bold text-green-600 mb-2">Conta Criada!</h2>
                <a href="index.php?p=login" class="inline-block bg-primary-600 text-white font-bold py-2 px-6 rounded hover:bg-primary-700">Ir para Login</a>
            </div>
        <?php else: ?>
            <div class="text-center">
                <h2 class="text-3xl font-extrabold text-gray-900">Crie sua conta</h2>
                <p class="mt-2 text-sm text-gray-600">Ou <a href="index.php?p=login" class="font-medium text-primary-600 hover:text-primary-500">volte para o login</a></p>
            </div>
            
            <?php if ($erro): ?><div class="bg-red-50 text-red-700 p-3 rounded text-sm text-center"><?php echo $erro; ?></div><?php endif; ?>

            <form class="mt-8 space-y-6" method="POST">
                <div class="rounded-md shadow-sm -space-y-px">
                    <div><input name="nome" type="text" required class="appearance-none rounded-none rounded-t-md relative block w-full px-3 py-2 border border-gray-300" placeholder="Nome Completo"></div>
                    <div><input name="usuario" type="text" required class="appearance-none relative block w-full px-3 py-2 border border-gray-300" placeholder="Nome de Usuário (Login)"></div>
                    <div><input name="email" type="email" required class="appearance-none relative block w-full px-3 py-2 border border-gray-300" placeholder="E-mail"></div>
                    <div><input name="senha" type="password" required class="appearance-none relative block w-full px-3 py-2 border border-gray-300" placeholder="Senha"></div>
                    <div><input name="confirma_senha" type="password" required class="appearance-none rounded-none rounded-b-md relative block w-full px-3 py-2 border border-gray-300" placeholder="Confirmar Senha"></div>
                </div>
                <button type="submit" class="group relative w-full flex justify-center py-2 px-4 text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700">Cadastrar</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>