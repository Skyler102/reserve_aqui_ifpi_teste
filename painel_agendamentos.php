<?php
require_once __DIR__ . '/includes/session_sec.php';
require_once __DIR__ . '/includes/db_conexao.php';
require_once __DIR__ . '/includes/bib.php';

verificar_login(); // Garante que o usuário está logado

gerar_cabecalho('Painel de Agendamentos');
?>

<main id="conteudo-principal" class="container mt-4 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3"><strong>Painel de Agendamentos</strong></h2>
        <a href="formulario_novo_agendamento.php" class="btn btn-acao shadow-sm">+ Novo Agendamento</a>
    </div>

    <?php
        // Exibe mensagem flash (centralizada)
        render_flash();
    ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <div id='calendar'></div>
        </div>
    </div>
</main>

<?php gerar_rodape(); ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        var calendar = new FullCalendar.Calendar(calendarEl, {
            locale: 'pt-br',
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,listWeek'
            },
            buttonText: {
                today: 'Hoje',
                month: 'Mês',
                week: 'Semana',
                list: 'Lista'
            },
            events: 'api/agendamentos.php'
        });
        calendar.render();
    });
</script>