<?php
if (!isset($_SESSION['user_id'])) { header("Location: index.php?p=login"); exit; }
if (isset($_SESSION['trocar_senha']) && $_SESSION['trocar_senha'] == 1) { header("Location: index.php?p=trocar_senha"); exit; }

$user_id = $_SESSION['user_id'];
$nome = explode(' ', $_SESSION['user_nome'])[0];

// Salvar Gadgets
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gadgets_update'])) {
    $pdo->prepare("DELETE FROM user_gadgets WHERE user_id = ?")->execute([$user_id]);
    $stmt = $pdo->prepare("INSERT INTO user_gadgets (user_id, module_file, ativo) VALUES (?, ?, 1)");
    foreach ($_POST['gadgets'] ?? [] as $mod) { $stmt->execute([$user_id, $mod]); }
    header("Location: index.php?p=dashboard"); exit;
}

// Carregar Módulos
$modulos = [];
foreach (glob('modules/*.module.php') as $f) {
    $c = file_get_contents($f);
    preg_match('/@moduleName:(.*)/', $c, $n);
    preg_match('/@moduleIcon:(.*)/', $c, $i);
    preg_match('/@moduleGadget:\s*(true|1)/', $c, $g);
    $modulos[basename($f)] = ['nome'=>trim($n[1]??'Nome'), 'icone'=>trim($i[1]??'ph-cube'), 'gadget'=>!empty($g), 'path'=>$f, 'file'=>basename($f)];
}

// Carregar Preferências
$ativos = $pdo->query("SELECT module_file FROM user_gadgets WHERE user_id=$user_id")->fetchAll(PDO::FETCH_COLUMN);
if (empty($ativos)) { foreach($modulos as $m) if($m['gadget']) $ativos[] = $m['file']; }
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
</head>
<body class="bg-gray-50 flex h-screen font-sans overflow-hidden">
    <aside class="w-64 bg-blue-900 text-white hidden md:flex flex-col">
        <div class="p-6 text-center border-b border-blue-800 font-bold text-xl">DIÁRIO v4.0</div>
        <nav class="flex-1 py-4 px-2 space-y-1">
            <a href="index.php?p=dashboard" class="flex items-center gap-3 bg-blue-800 px-4 py-3 rounded"><i class="ph ph-squares-four text-xl"></i> Visão Geral</a>
            <?php foreach ($modulos as $m): ?>
                <a href="index.php?m=<?php echo $m['file']; ?>" class="flex items-center gap-3 hover:bg-blue-800 px-4 py-3 rounded transition"><i class="ph <?php echo $m['icone']; ?> text-xl"></i> <?php echo $m['nome']; ?></a>
            <?php endforeach; ?>
            <?php if ($_SESSION['user_nivel'] === 'administrador'): ?>
                <a href="index.php?p=admin" class="flex items-center gap-3 text-yellow-300 hover:bg-blue-800 px-4 py-3 rounded mt-4"><i class="ph ph-wrench text-xl"></i> Admin</a>
            <?php endif; ?>
        </nav>
        <a href="index.php?p=logout" class="block p-4 text-center text-red-300 hover:text-white border-t border-blue-800">Sair</a>
    </aside>

    <main class="flex-1 flex flex-col h-screen overflow-hidden">
        <div class="p-8 overflow-y-auto flex-1">
            <header class="flex justify-between items-end mb-8">
                <h2 class="text-3xl font-bold text-gray-800">Olá, <?php echo $nome; ?>!</h2>
                <button onclick="document.getElementById('modal').classList.remove('hidden')" class="bg-blue-100 text-blue-700 px-4 py-2 rounded font-bold"><i class="ph ph-sliders"></i> Personalizar</button>
            </header>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($modulos as $m): ?>
                    <?php if ($m['gadget'] && in_array($m['file'], $ativos)): ?>
                        <div class="bg-white p-6 rounded shadow border h-64 flex flex-col">
                            <?php $modo_gadget = true; include $m['path']; ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <div id="modal" class="fixed inset-0 bg-black/50 hidden flex items-center justify-center p-4">
        <form method="POST" action="index.php?p=dashboard" class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6">
            <input type="hidden" name="gadgets_update" value="1">
            <h3 class="text-xl font-bold mb-4">Widgets</h3>
            <?php foreach ($modulos as $m): if ($m['gadget']): ?>
                <label class="flex items-center gap-3 p-2 hover:bg-gray-50 border mb-2 rounded cursor-pointer">
                    <input type="checkbox" name="gadgets[]" value="<?php echo $m['file']; ?>" <?php echo in_array($m['file'], $ativos)?'checked':''; ?>>
                    <span><?php echo $m['nome']; ?></span>
                </label>
            <?php endif; endforeach; ?>
            <div class="flex justify-end gap-2 mt-4">
                <button type="button" onclick="document.getElementById('modal').classList.add('hidden')" class="px-4 py-2 text-gray-500">Cancelar</button>
                <button class="px-4 py-2 bg-blue-600 text-white rounded">Salvar</button>
            </div>
        </form>
    </div>
</body>
</html>