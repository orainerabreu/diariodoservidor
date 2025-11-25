<?php
if (isset($_SESSION['user_id'])) { header("Location: index.php?p=dashboard"); exit; }

$cfg = $GLOBALS['sys_config'];
$cor = $cfg['cor_principal'] ?? 'blue';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ... (Lógica de Login da Versão 4.0 - Não precisa mudar)
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
        $erro = "Usuário ou senha incorretos.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $cfg['nome_sistema']; ?> - Entrar</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: ({opacityValue}) => {
                            if (opacityValue === undefined) {
                                return `rgb(var(--color-<?php echo $cor; ?>) / 1)`
                            }
                            return `rgb(var(--color-<?php echo $cor; ?>) / ${opacityValue})`
                        },
                    }
                }
            }
        }
    </script>
    <style>
        :root {
            --color-blue: 37 99 235; /* 600 */
            --color-green: 21 128 61; /* 700 */
            --color-red: 220 38 38; /* 600 */
            --color-purple: 126 34 206; /* 700 */
            --color-orange: 234 88 12; /* 600 */
        }
        .bg-primary-900 { background-color: rgb(var(--color-<?php echo $cor; ?>) / 1); }
        .text-primary-900 { color: rgb(var(--color-<?php echo $cor; ?>) / 1); }
        .text-primary-600 { color: rgb(var(--color-<?php echo $cor; ?>) / 1); }
        .bg-primary-600 { background-color: rgb(var(--color-<?php echo $cor; ?>) / 1); }
        /* Adicionamos o Tailwind na classe primary para forçar a cor */
    </style>
</head>
<body class="bg-gray-50 h-screen font-sans flex overflow-hidden">

    <div class="hidden lg:flex w-1/2 bg-primary-900 text-white flex-col justify-between p-12 relative overflow-hidden">
        <div class="relative z-10">
            <h1 class="text-3xl font-bold tracking-wide flex items-center gap-2">
                <i class="ph ph-notebook text-4xl"></i> <?php echo strtoupper($cfg['nome_sistema']); ?>
            </h1>
        </div>
        <div class="relative z-10 max-w-lg">
            <h2 class="text-5xl font-bold mb-6 leading-tight">Gestão Inteligente.</h2>
            <p class="text-xl text-white opacity-80 mb-8"><?php echo $cfg['slogan']; ?></p>
        </div>
    </div>

    <div class="w-full lg:w-1/2 flex items-center justify-center p-8 bg-white overflow-y-auto">
        <div class="w-full max-w-md">
            <div class="text-center mb-8">
                <h2 class="text-2xl font-bold text-gray-800">Acesse sua conta!</h2>
            </div>

            <?php if ($erro): ?>
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 text-sm flex items-center gap-2">
                    <i class="ph ph-warning-circle text-xl"></i> <?php echo $erro; ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Usuário / Login</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400"><i class="ph ph-user"></i></div>
                        <input type="text" name="usuario" required class="pl-10 block w-full border-gray-300 rounded-lg border focus:ring-primary-500 focus:border-primary-500 py-3 transition" placeholder="Seu usuário">
                    </div>
                </div>

                <div>
                    <div class="flex justify-between items-center mb-1">
                        <label class="block text-sm font-medium text-gray-700">Senha</label>
                        <a href="index.php?p=recuperar" class="text-sm text-primary-600 hover:text-primary-800 font-semibold">Esqueceu?</a>
                    </div>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400"><i class="ph ph-lock"></i></div>
                        <input type="password" name="senha" id="senhaInput" required class="pl-10 pr-10 block w-full border-gray-300 rounded-lg border focus:ring-primary-500 focus:border-primary-500 py-3 transition" placeholder="Sua senha">
                        <button type="button" onclick="toggleSenha()" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                            <i class="ph ph-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="w-full flex justify-center py-3 px-4 rounded-lg shadow-sm text-sm font-bold text-white bg-primary-600 hover:bg-primary-700 transition">
                    Entrar no Sistema
                </button>
            </form>

            <?php if ($cfg['permite_cadastro'] == 1): ?>
                <div class="mt-8 text-center border-t pt-6">
                    <p class="text-gray-600">Ainda não tem conta?</p>
                    <a href="index.php?p=cadastro" class="mt-2 inline-block text-primary-700 font-bold hover:underline">
                        Criar conta gratuitamente
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleSenha() {
            const input = document.getElementById('senhaInput');
            const icon = document.getElementById('eyeIcon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('ph-eye', 'ph-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('ph-eye-slash', 'ph-eye');
            }
        }
    </script>
</body>
</html>