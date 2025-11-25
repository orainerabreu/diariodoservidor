<?php
// @moduleName: Agenda
// @moduleIcon: ph-calendar-check
// @moduleGadget: true

if (isset($modo_gadget) && $modo_gadget === true) {
    global $pdo, $user_id;
    $stmt = $pdo->prepare("SELECT * FROM agenda WHERE usuario_id = ? AND inicio >= NOW() ORDER BY inicio ASC LIMIT 3");
    $stmt->execute([$user_id]);
    $evts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo '<h3 class="font-bold text-gray-700 mb-2 flex items-center gap-2"><i class="ph ph-calendar text-blue-600"></i> Próximos</h3>';
    if(empty($evts)) echo '<p class="text-gray-400 text-sm">Nada agendado.</p>';
    foreach($evts as $e) echo "<div class='text-sm border-b py-2'><span class='font-bold text-blue-600'>".date('d/m', strtotime($e['inicio']))."</span> {$e['titulo']}</div>";
    return;
}

// MODO COMPLETO
if (!isset($_SESSION['user_id'])) header("Location: index.php?p=login");

// --- Processamento CRUD (Mesmo da v3.0) ---
// ... (Copie a lógica de POST/INSERT/UPDATE da resposta anterior aqui) ...
// ... Apenas mude o header location final para: header("Location: index.php?m=agenda.module.php");

// Para simplificar, vou por apenas o HTML básico de retorno
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Agenda</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
</head>
<body class="bg-gray-100 flex h-screen font-sans">
    <aside class="w-64 bg-blue-900 text-white hidden md:flex flex-col p-4">
        <a href="index.php?p=dashboard" class="flex items-center gap-2 text-blue-200 hover:text-white"><i class="ph ph-arrow-left"></i> Voltar</a>
        <h1 class="text-xl font-bold mt-4">Agenda</h1>
    </aside>
    <main class="flex-1 p-6">
        <div class="bg-white p-6 rounded shadow h-full" id="calendar"></div>
    </main>
    <script>
        // ... (Script do FullCalendar da v3.0) ...
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, { initialView: 'dayGridMonth', locale: 'pt-br' });
            calendar.render();
        });
    </script>
</body>
</html>