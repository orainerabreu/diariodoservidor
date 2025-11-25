<?php
// app/admin.php - Versão 4.1.1 (CORRIGIDA)
if (!isset($_SESSION['user_id']) || $_SESSION['user_nivel'] !== 'administrador') { 
    header("Location: index.php?p=dashboard"); 
    exit;
}

$msg = '';
$tipo_msg = '';
$tab = $_GET['tab'] ?? 'usuarios';
$configs = $pdo->query("SELECT * FROM configuracoes")->fetchAll(PDO::FETCH_KEY_PAIR);

// --- LÓGICA 1: GERENCIAMENTO DE USUÁRIOS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_user'])) {
    $action = $_POST['action_user'];
    $id = $_POST['id'] ?? null;
    $nivel = $_POST['nivel'] ?? null;
    $nome = $_POST['nome'] ?? null;
    $email = $_POST['email'] ?? null;
    $user = $_POST['usuario'] ?? null;
    $senha = $_POST['senha'] ?? null;
    $ativo = isset($_POST['ativo']) ? 1 : 0;

    if ($action === 'create') {
        try {
            $pass_hash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO usuarios (nome_completo, usuario, email, senha, nivel, trocar_senha) VALUES (?, ?, ?, ?, ?, 1)");
            $stmt->execute([$nome, $user, $email, $pass_hash, $nivel]);
            $msg = "Usuário criado com sucesso! Será forçada a troca de senha no primeiro login.";
            $tipo_msg = 'success';
        } catch (PDOException $e) { 
            $msg = "Erro: Usuário ou E-mail já existe."; 
            $tipo_msg = 'error';
        }
        
    } elseif ($action === 'edit' && $id) {
        $sql = "UPDATE usuarios SET nome_completo=?, nivel=?, ativo=?, email=? WHERE id=?";
        $params = [$nome, $nivel, $ativo, $email, $id];
        
        if (!empty($senha)) {
            $sql = "UPDATE usuarios SET nome_completo=?, nivel=?, ativo=?, email=?, senha=?, trocar_senha=1 WHERE id=?";
            $params = [$nome, $nivel, $ativo, $email, password_hash($senha, PASSWORD_DEFAULT), $id];
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $msg = "Usuário atualizado!";
        $tipo_msg = 'success';

    } elseif ($action === 'delete' && $id) {
        if ($id != $_SESSION['user_id']) { 
            $pdo->prepare("DELETE FROM usuarios WHERE id=?")->execute([$id]);
            $msg = "Usuário removido.";
            $tipo_msg = 'warning';
        } else {
            $msg = "Você não pode deletar sua própria conta.";
            $tipo_msg = 'error';
        }
    }
}

// --- LÓGICA 2: ATUALIZADOR DO SISTEMA (CORE/MODULES) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['update_file'])) {
    $target = $_POST['target_folder'];
    $file = $_FILES['update_file'];
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    
    if ($ext === 'php') {
        $path = ($target === 'app') ? 'app/' : 'modules/';
        if (!is_dir($path)) mkdir($path, 0777, true);
        
        // Bloqueia update de arquivos vitais na raiz (index.php, .htaccess) por segurança
        if ($target === 'app' && in_array($file['name'], ['index.php', '.htaccess', 'config.php'])) {
            $msg = "Atualização de {$file['name']} bloqueada por esta interface por razões de segurança crítica.";
            $tipo_msg = 'error';
        } 
        // Permite atualização normal
        elseif (move_uploaded_file($file['tmp_name'], $path . $file['name'])) {
            $msg = "Arquivo <b>{$file['name']}</b> atualizado na pasta <b>{$target}/</b> com sucesso.";
            $tipo_msg = 'success';
        } else {
            $msg = "Erro de permissão de escrita.";
            $tipo_msg = 'error';
        }
    } else {
        $msg = "Apenas arquivos .php permitidos.";
        $tipo_msg = 'error';
    }
}

// --- LÓGICA 3: APARÊNCIA ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_config'])) {
    $pdo->beginTransaction();
    try {
        foreach ($_POST['cfg'] as $key => $val) {
            $stmt = $pdo->prepare("INSERT INTO configuracoes (chave, valor) VALUES (:k, :v) ON DUPLICATE KEY UPDATE valor=:v");
            $stmt->execute([':k' => $key, ':v' => $val]);
        }
        $pdo->commit();
        $msg = "Configurações visuais atualizadas!";
        $tipo_msg = 'success';
        // Redireciona para recarregar as configurações globais
        header("Location: index.php?p=admin&tab=aparencia"); exit; 
    } catch (Exception $e) {
        $pdo->rollBack();
        $msg = "Erro ao salvar: " . $e->getMessage();
        $tipo_msg = 'error';
    }
}

// Buscar Usuários para a lista
$usuarios = $pdo->query("SELECT * FROM usuarios ORDER BY nome_completo")->fetchAll();

// Mapeamento de cores para o Tailwind CSS
$cor_principal = $configs['cor_principal'] ?? 'blue';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Administração</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: colors.<?php echo $cor_principal; ?>
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-100 font-sans min-h-screen p-6">

    <div class="max-w-6xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-800 mb-6">Painel Administrativo</h1>
        
        <?php if ($msg): ?>
            <div class="p-4 rounded-lg shadow border-l-4 mb-6 
                <?php echo $tipo_msg === 'success' ? 'bg-green-50 border-green-500 text-green-700' : 
                          ($tipo_msg === 'error' ? 'bg-red-50 border-red-500 text-red-700' : 'bg-blue-50 border-blue-500 text-blue-700'); ?>">
                <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <div class="flex space-x-4 mb-6 border-b">
            <a href="index.php?p=admin&tab=usuarios" class="pb-2 <?php echo $tab==='usuarios'?'border-b-2 border-primary-600 text-primary-600 font-bold':'text-gray-500'; ?>">Gerenciar Usuários</a>
            <a href="index.php?p=admin&tab=sistema" class="pb-2 <?php echo $tab==='sistema'?'border-b-2 border-primary-600 text-primary-600 font-bold':'text-gray-500'; ?>">Atualizar Sistema</a>
            <a href="index.php?p=admin&tab=aparencia" class="pb-2 <?php echo $tab==='aparencia'?'border-b-2 border-primary-600 text-primary-600 font-bold':'text-gray-500'; ?>">Aparência</a>
        </div>

        <?php if ($tab === 'usuarios'): ?>
            <div class="bg-white p-6 rounded shadow mb-8 border border-gray-200">
                <h3 class="font-bold text-xl mb-4 text-gray-800">Adicionar Novo Usuário</h3>
                <form method="POST" class="grid grid-cols-1 md:grid-cols-6 gap-4 items-end">
                    <input type="hidden" name="action_user" value="create">
                    <input type="text" name="nome" placeholder="Nome Completo" required class="border p-2 rounded col-span-2">
                    <input type="text" name="usuario" placeholder="Login" required class="border p-2 rounded">
                    <input type="email" name="email" placeholder="E-mail" required class="border p-2 rounded">
                    <input type="password" name="senha" placeholder="Senha Provisória" required class="border p-2 rounded">
                    <select name="nivel" class="border p-2 rounded">
                        <option value="servidor">Servidor</option>
                        <option value="administrador">Administrador</option>
                    </select>
                    <button class="bg-green-600 text-white p-2 rounded font-bold hover:bg-green-700 col-span-full md:col-span-1">Criar</button>
                </form>
            </div>

            <div class="bg-white rounded shadow overflow-hidden border border-gray-200">
                <table class="w-full text-left">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="p-4">Nome (Login)</th>
                            <th class="p-4">E-mail</th>
                            <th class="p-4">Nível</th>
                            <th class="p-4">Status</th>
                            <th class="p-4">Nova Senha?</th>
                            <th class="p-4">Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $u): ?>
                        <tr class="border-b hover:bg-gray-50">
                            <form method="POST">
                                <input type="hidden" name="action_user" value="edit">
                                <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                <td class="p-2">
                                    <input type="text" name="nome" value="<?php echo htmlspecialchars($u['nome_completo']); ?>" class="border rounded p-1 w-full text-sm">
                                    <span class="block text-xs text-gray-500 mt-1"><?php echo $u['usuario']; ?></span>
                                </td>
                                <td class="p-2">
                                    <input type="email" name="email" value="<?php echo htmlspecialchars($u['email']); ?>" class="border rounded p-1 w-full text-sm">
                                </td>
                                <td class="p-2">
                                    <select name="nivel" class="border rounded p-1 text-sm">
                                        <option value="servidor" <?php echo $u['nivel']=='servidor'?'selected':''; ?>>Servidor</option>
                                        <option value="administrador" <?php echo $u['nivel']=='administrador'?'selected':''; ?>>Admin</option>
                                    </select>
                                </td>
                                <td class="p-2">
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" name="ativo" <?php echo $u['ativo']?'checked':''; ?> class="text-primary-600"> Ativo
                                    </label>
                                </td>
                                <td class="p-2">
                                    <input type="password" name="senha" placeholder="Deixe em branco para manter" class="border rounded p-1 w-32 text-sm">
                                </td>
                                <td class="p-2 flex gap-2">
                                    <button type="submit" class="text-primary-600 hover:text-primary-800" title="Salvar Edição"><i class="ph ph-floppy-disk text-xl"></i></button>
                                    <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                        <button type="submit" name="action_user" value="delete" onclick="return confirm('Excluir o usuário <?php echo $u['usuario']; ?>?')" class="text-red-500 hover:text-red-700" title="Excluir"><i class="ph ph-trash text-xl"></i></button>
                                    <?php endif; ?>
                                </td>
                            </form>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($tab === 'sistema'): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white p-6 rounded shadow border-l-4 border-orange-500">
                    <h3 class="font-bold text-xl mb-2 text-gray-800">Atualizar Núcleo (Pasta /app)</h3>
                    <p class="text-sm text-gray-500 mb-4">Use para atualizar dashboard.php, admin.php, login.php, etc.</p>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="target_folder" value="app">
                        <input type="file" name="update_file" accept=".php" required class="mb-4 block w-full text-sm">
                        <button class="bg-orange-600 text-white px-4 py-2 rounded font-bold w-full hover:bg-orange-700">Enviar para /app</button>
                    </form>
                </div>
                <div class="bg-white p-6 rounded shadow border-l-4 border-blue-500">
                    <h3 class="font-bold text-xl mb-2 text-gray-800">Atualizar Módulos (Pasta /modules)</h3>
                    <p class="text-sm text-gray-500 mb-4">Use para instalar ou atualizar agenda.module.php, etc.</p>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="target_folder" value="modules">
                        <input type="file" name="update_file" accept=".php" required class="mb-4 block w-full text-sm">
                        <button class="bg-blue-600 text-white px-4 py-2 rounded font-bold w-full hover:bg-blue-700">Enviar para /modules</button>
                    </form>
                </div>
            </div>

        <?php elseif ($tab === 'aparencia'): ?>
            <div class="bg-white p-6 rounded shadow max-w-2xl border border-gray-200">
                <h3 class="font-bold text-xl mb-6 flex items-center gap-2 text-gray-800"><i class="ph ph-paint-brush"></i> Personalização do Sistema</h3>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="save_config" value="1">
                    
                    <div>
                        <label class="block text-sm font-bold text-gray-700">Nome do Sistema</label>
                        <input type="text" name="cfg[nome_sistema]" value="<?php echo htmlspecialchars($configs['nome_sistema']??''); ?>" class="w-full border p-2 rounded">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-bold text-gray-700">Slogan (Página de Login)</label>
                        <input type="text" name="cfg[slogan]" value="<?php echo htmlspecialchars($configs['slogan']??''); ?>" class="w-full border p-2 rounded">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700">Cor Principal</label>
                        <select name="cfg[cor_principal]" class="w-full border p-2 rounded">
                            <?php $cores = ['blue'=>'Azul', 'green'=>'Verde', 'red'=>'Vermelho', 'purple'=>'Roxo', 'orange'=>'Laranja']; ?>
                            <?php foreach($cores as $val => $nome): ?>
                                <option value="<?php echo $val; ?>" <?php echo ($configs['cor_principal']??'')==$val?'selected':''; ?>><?php echo $nome; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="flex items-center gap-2 pt-4">
                        <input type="hidden" name="cfg[permite_cadastro]" value="0">
                        <input type="checkbox" name="cfg[permite_cadastro]" value="1" id="cad" <?php echo ($configs['permite_cadastro']??0)==1?'checked':''; ?> class="w-5 h-5 text-primary-600 rounded">
                        <label for="cad" class="text-gray-700">Permitir Cadastro Gratuito na tela de login?</label>
                    </div>

                    <button class="bg-primary-600 text-white font-bold py-3 px-6 rounded w-full mt-4 hover:bg-primary-700 transition">
                        Salvar Alterações
                    </button>
                </form>
            </div>
        <?php endif; ?>

    </div>
</body>
</html>