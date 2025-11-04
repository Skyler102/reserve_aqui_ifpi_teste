<?php
require_once __DIR__ . '/includes/session_sec.php';
require_once __DIR__ . '/includes/db_conexao.php';
require_once __DIR__ . '/includes/bib.php';

// Verifica se o usuário está logado
verificar_login();

// Armazena dados do usuário logado
$id_usuario_logado = $_SESSION['id_usuario'];
$csrf_token = $_SESSION['csrf_token'];

// ======================================================================
// ⭐️ NOVAS VARIÁVEIS DE ORDENAÇÃO E PAGINAÇÃO
// ======================================================================
// --- Para "Todos os Agendamentos" ---
$sort_col_todos = filter_input(INPUT_GET, 'sort', FILTER_SANITIZE_SPECIAL_CHARS) ?? 'data';
$sort_order_todos = filter_input(INPUT_GET, 'order', FILTER_SANITIZE_SPECIAL_CHARS) ?? 'DESC';
// ⭐️ Lógica de 'per_page' atualizada para usar o nome do parâmetro correto
$per_page_todos = filter_input(INPUT_GET, 'per_page_todos', FILTER_VALIDATE_INT) ?? 10;
$pagina_atual_todos = filter_input(INPUT_GET, 'page_todos', FILTER_VALIDATE_INT) ?? 1;

// Mapeia colunas amigáveis para colunas do DB
$sort_map_todos = [
    'recurso' => 'r.nome',
    'usuario' => 'u.nome_completo',
    'finalidade' => 'd.nome',
    'data' => 'a.data_hora_inicio',
    'status' => 'a.status'
];
$order_by_sql_todos = ($sort_map_todos[$sort_col_todos] ?? 'a.data_hora_inicio') . ' ' . (strtoupper($sort_order_todos) === 'ASC' ? 'ASC' : 'DESC');

// --- Para "Meus Agendamentos" (Modal) ---
$per_page_meus = filter_input(INPUT_GET, 'per_page_meus', FILTER_VALIDATE_INT) ?? 5;
$pagina_atual_meus = filter_input(INPUT_GET, 'page_meus', FILTER_VALIDATE_INT) ?? 1;


// ======================================================================
// ⭐️ FUNÇÃO DE AGRUPAMENTO (Não muda)
// ======================================================================
if (!function_exists('agrupar_agendamentos')) { // Previne redeclaração
    function agrupar_agendamentos($agendamentos) {
        $agrupados = [];
        foreach ($agendamentos as $ag) {
            $grupo_key = $ag['grupo_recorrencia_id'] ?? 'unico_' . $ag['id'];
            $agrupados[$grupo_key]['instancias'][] = $ag;
            if (!isset($agrupados[$grupo_key]['dados_comuns'])) {
                $agrupados[$grupo_key]['dados_comuns'] = $ag;
            }
        }
        return $agrupados;
    }
}

// ======================================================================
// 1️⃣ LÓGICA: "MEUS AGENDAMENTOS" (Modal)
// ======================================================================
$sql_meus_todos = "
    SELECT 
        a.id, a.motivo, a.status, a.data_hora_inicio, a.data_hora_fim, a.tipo_agendamento, a.grupo_recorrencia_id,
        r.nome AS recurso_nome,
        d.nome AS disciplina_nome,
        t.nome AS turma_nome
    FROM agendamentos a 
    JOIN recursos r ON a.id_recurso = r.id
    LEFT JOIN disciplinas d ON a.id_disciplina = d.id
    LEFT JOIN turmas t ON a.id_turma = t.id
    WHERE a.id_usuario = :id_usuario 
    ORDER BY a.grupo_recorrencia_id, a.data_hora_inicio DESC
";
$stmt_meus_todos = $pdo->prepare($sql_meus_todos);
$stmt_meus_todos->bindValue(':id_usuario', $id_usuario_logado, PDO::PARAM_INT);
$stmt_meus_todos->execute();
$meus_agendamentos_lista = $stmt_meus_todos->fetchAll(PDO::FETCH_ASSOC);
$meus_agendamentos_agrupados = agrupar_agendamentos($meus_agendamentos_lista);
$total_grupos_meus = count($meus_agendamentos_agrupados);
$total_paginas_meus = max(1, ceil($total_grupos_meus / $per_page_meus));
$pagina_atual_meus = min($pagina_atual_meus, $total_paginas_meus); // Corrige se a página for inválida
$offset_meus = ($pagina_atual_meus - 1) * $per_page_meus;
$meus_grupos_paginados = array_slice($meus_agendamentos_agrupados, $offset_meus, $per_page_meus, true);


// ======================================================================
// 2️⃣ LÓGICA: "TODOS OS AGENDAMENTOS" (Tabela principal)
// ======================================================================
$sql_todos_completo = "
    SELECT 
        a.id, a.motivo, a.status, a.data_hora_inicio, a.data_hora_fim, a.tipo_agendamento, a.id_usuario, a.grupo_recorrencia_id,
        u.nome_completo AS usuario_nome,
        r.nome AS recurso_nome,
        d.nome AS disciplina_nome,
        t.nome AS turma_nome
    FROM agendamentos a 
    JOIN usuarios u ON a.id_usuario = u.id
    JOIN recursos r ON a.id_recurso = r.id
    LEFT JOIN disciplinas d ON a.id_disciplina = d.id
    LEFT JOIN turmas t ON a.id_turma = t.id
    ORDER BY $order_by_sql_todos, a.grupo_recorrencia_id
"; // ⭐️ ORDER BY ATUALIZADO
$stmt_todos_completo = $pdo->prepare($sql_todos_completo);
$stmt_todos_completo->execute();
$todos_agendamentos_lista = $stmt_todos_completo->fetchAll(PDO::FETCH_ASSOC);

// AGRUPA os resultados
$todos_agendamentos_agrupados = agrupar_agendamentos($todos_agendamentos_lista);

// FAZ A PAGINAÇÃO em cima dos GRUPOS
$total_grupos_todos = count($todos_agendamentos_agrupados);
$total_paginas_todos = max(1, ceil($total_grupos_todos / $per_page_todos));
$pagina_atual_todos = min($pagina_atual_todos, $total_paginas_todos); // Corrige se a página for inválida
$offset_todos = ($pagina_atual_todos - 1) * $per_page_todos;

// Fatiar o array de grupos para a página atual
$todos_grupos_paginados = array_slice($todos_agendamentos_agrupados, $offset_todos, $per_page_todos, true);


// ======================================================================
// Cabeçalho HTML
// ======================================================================
gerar_cabecalho('Painel de Agendamentos');
?>

<!-- ⭐️ ESTILO DO ACCORDION (Não muda) -->
<style>
    .accordion-toggle[aria-expanded="true"] .fa-chevron-right {
        transform: rotate(90deg);
        transition: transform 0.2s ease-in-out;
    }
    .accordion-toggle[aria-expanded="false"] .fa-chevron-right {
        transform: rotate(0deg);
        transition: transform 0.2s ease-in-out;
    }
    .table > :not(caption) > * > .accordion-body {
        padding: 0;
        border-top: none; 
    }
    .table tr.accordion-toggle + tr > td {
        border-top: none !important;
        padding-top: 0;
    }
    .table tr.accordion-toggle { cursor: pointer; }
    .table tr.tr-unico { cursor: default; }
    .btn-acao-individual { font-size: 0.8rem; }
</style>

<main id="conteudo-principal" class="container mt-4 mb-5">

    <!-- Cabeçalho da Página -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 fw-bold">Painel de Agendamentos</h2>
        <div>
            <button class="btn btn-outline-secondary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalMeusAgendamentos">
                <i class="fas fa-user-clock me-2"></i>Meus Agendamentos
            </button>
            <a href="formulario_novo_agendamento.php" class="btn btn-acao shadow-sm ms-2">+ Novo Agendamento</a>
        </div>
    </div>

    <?php render_flash(); ?>

    <!-- ⭐️ Tabela de Todos os Agendamentos (MODIFICADA) -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <h5 class="mb-0 me-3">Todos os Agendamentos</h5>
            <!-- ⭐️ NOVO: Seletor de Itens por Página -->
            <?php seletor_itens_por_pagina($per_page_todos, [5, 10, 25], 'per_page_todos'); ?>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 20px;"></th> <!-- Coluna do ícone -->
                            <!-- ⭐️ NOVOS: Cabeçalhos Ordenáveis -->
                            <?php th_sortable('Recurso', 'recurso', $sort_col_todos, $sort_order_todos); ?>
                            <?php th_sortable('Usuário', 'usuario', $sort_col_todos, $sort_order_todos); ?>
                            <?php th_sortable('Finalidade', 'finalidade', $sort_col_todos, $sort_order_todos); ?>
                            <?php th_sortable('Data e Hora', 'data', $sort_col_todos, $sort_order_todos); ?>
                            <th class="text-center">Tipo</th>
                            <?php th_sortable('Status', 'status', $sort_col_todos, $sort_order_todos); ?>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($todos_grupos_paginados)): ?>
                            <tr><td colspan="8" class="text-center py-3 text-muted">Nenhum agendamento encontrado no sistema.</td></tr>
                        <?php else: ?>
                            <?php foreach ($todos_grupos_paginados as $grupo_key => $grupo): ?>
                                <?php
                                    $instancias = $grupo['instancias'];
                                    $dados_comuns = $grupo['dados_comuns'];
                                    $e_recorrente = count($instancias) > 1;
                                    $collapse_id = 'grupo-todos-' . preg_replace('/[^a-zA-Z0-9]/', '', $grupo_key);
                                ?>
                                <!-- LINHA PRINCIPAL (GATILHO) -->
                                <tr 
                                    class="<?= $e_recorrente ? 'accordion-toggle' : 'tr-unico' ?>" 
                                    <?php if ($e_recorrente): ?>
                                        data-bs-toggle="collapse" 
                                        data-bs-target="#<?= $collapse_id ?>" 
                                        aria-expanded="false" 
                                        aria-controls="<?= $collapse_id ?>"
                                    <?php endif; ?>
                                >
                                    <td>
                                        <?php if ($e_recorrente): ?>
                                            <i class="fas fa-chevron-right"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($dados_comuns['recurso_nome']) ?></td>
                                    <td><?= htmlspecialchars($dados_comuns['usuario_nome']) ?></td>
                                    <td>
                                        <?php if (!empty($dados_comuns['disciplina_nome'])): ?>
                                            <strong><?= htmlspecialchars($dados_comuns['disciplina_nome']) ?></strong><br>
                                            <small class="text-muted"><?= htmlspecialchars($dados_comuns['turma_nome']) ?></small>
                                        <?php else: ?>
                                            <?= htmlspecialchars($dados_comuns['motivo'] ?: 'N/A') ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <!-- ⭐️ LÓGICA DE DATA/HORA ATUALIZADA -->
                                        <?php if ($e_recorrente): ?>
                                            <?php
                                                // Pega a data da primeira instância para saber o dia da semana
                                                $dia_semana_pt = formatar_data_br($instancias[0]['data_hora_inicio'], 'EEEE'); // Ex: "segunda-feira"
                                            ?>
                                            <strong><?= $dia_semana_pt ?> (<?= count($instancias) ?>x)</strong><br>
                                            <small>
                                                <?= formatar_data_br(end($instancias)['data_hora_inicio'], 'd/MM/y') ?>
                                                - <?= formatar_data_br(reset($instancias)['data_hora_inicio'], 'd/MM/y') ?>
                                            </small>
                                        <?php else: ?>
                                            <strong><?= formatar_data_br($dados_comuns['data_hora_inicio'], 'd/MM/Y') ?></strong><br>
                                            <small class="text-muted">
                                                <?= formatar_data_br($dados_comuns['data_hora_inicio'], 'HH:mm') ?>
                                                - <?= formatar_data_br($dados_comuns['data_hora_fim'], 'HH:mm') ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-<?= $dados_comuns['tipo_agendamento'] === 'fixo' ? 'info' : 'light text-dark' ?>">
                                            <?= ucfirst($dados_comuns['tipo_agendamento']) ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge status-<?= $dados_comuns['status'] ?>"><?= ucfirst($dados_comuns['status']) ?></span>
                                    </td>
                                    <td class="text-center">
                                        <?php 
                                        $ag_id_acao = $dados_comuns['id'];
                                        $link_params = "id={$ag_id_acao}&csrf_token={$csrf_token}";
                                        if ($e_recorrente) {
                                            $link_params = "grupo_id={$dados_comuns['grupo_recorrencia_id']}&csrf_token={$csrf_token}";
                                        }

                                        if ($dados_comuns['id_usuario'] == $id_usuario_logado && in_array($dados_comuns['status'], ['pendente', 'aprovado'])): ?>
                                            <a href="scripts/cancelar_agendamento.php?<?= $link_params ?>" 
                                               class="btn btn-sm btn-outline-warning"
                                               onclick="return confirm('Tem certeza que deseja CANCELAR <?= $e_recorrente ? 'TODOS OS ' . count($instancias) . ' agendamentos' : 'este agendamento' ?>?');"
                                               title="Cancelar <?= $e_recorrente ? 'Série' : 'Agendamento' ?>">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        <?php elseif ($dados_comuns['id_usuario'] == $id_usuario_logado && in_array($dados_comuns['status'], ['recusado', 'cancelado'])): ?>
                                            <a href="scripts/remover_agendamento.php?<?= $link_params ?>" 
                                               class="btn btn-sm btn-outline-danger"
                                               onclick="return confirm('Tem certeza que deseja REMOVER <?= $e_recorrente ? 'esta série' : 'este agendamento' ?> da sua lista?');"
                                               title="Remover <?= $e_recorrente ? 'Série' : 'Agendamento' ?>">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                
                                <!-- LINHA EXPANSÍVEL (CONTEÚDO) -->
                                <?php if ($e_recorrente): ?>
                                    <tr>
                                        <td colspan="8" class="p-0" style="border-top: none;"> 
                                            <div id="<?= $collapse_id ?>" class="collapse accordion-body">
                                                <div class="p-3" style="background-color: #f8f9fa;">
                                                    <h6 class="mb-2">Instâncias Individuais (<?= count($instancias) ?>)</h6>
                                                    <ul class="list-group">
                                                        <?php foreach ($instancias as $instancia): ?>
                                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                                <div>
                                                                    <i class="fas fa-calendar-day me-2"></i>
                                                                    <!-- ⭐️ TRADUÇÃO APLICADA AQUI -->
                                                                    <strong><?= formatar_data_br($instancia['data_hora_inicio'], 'EEEE, d/MM/Y') ?></strong>
                                                                    <span class="text-muted ms-2">
                                                                        (<?= formatar_data_br($instancia['data_hora_inicio'], 'HH:mm') ?>
                                                                        - <?= formatar_data_br($instancia['data_hora_fim'], 'HH:mm') ?>)
                                                                    </span>
                                                                </div>
                                                                <?php if ($instancia['id_usuario'] == $id_usuario_logado && in_array($instancia['status'], ['pendente', 'aprovado'])): ?>
                                                                <a href="scripts/cancelar_agendamento.php?id=<?= $instancia['id'] ?>&csrf_token=<?= $csrf_token ?>" 
                                                                   class="btn btn-sm btn-outline-warning btn-acao-individual"
                                                                   onclick="return confirm('Tem certeza que deseja CANCELAR apenas o agendamento do dia <?= formatar_data_br($instancia['data_hora_inicio'], 'd/MM/Y') ?>?');"
                                                                   title="Cancelar apenas esta data">
                                                                    <i class="fas fa-times me-1"></i> Cancelar Data
                                                                </a>
                                                                <?php endif; ?>
                                                            </li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>

                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- ⭐️ Paginação (Todos os Agendamentos) ATUALIZADA -->
            <?php gerar_paginacao($pagina_atual_todos, $total_paginas_todos, 'page_todos'); ?>
        </div>
    </div>
</main>

<!-- ======================================================================
     ⭐️ MODAL: Meus Agendamentos (MODIFICADO)
====================================================================== -->
<div class="modal fade" id="modalMeusAgendamentos" tabindex="-1" aria-labelledby="modalMeusAgendamentosLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalMeusAgendamentosLabel">Meus Agendamentos</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>

            <div class="modal-body">
                <!-- ⭐️ NOVO: Seletor de Itens por Página (para o MODAL) -->
                <?php seletor_itens_por_pagina($per_page_meus, [5, 10, 25], 'per_page_meus'); ?>
                
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                         <thead>
                            <tr>
                                <th style="width: 20px;"></th>
                                <th>Recurso</th>
                                <th>Finalidade</th>
                                <th>Data e Hora</th>
                                <th class="text-center">Tipo</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($meus_grupos_paginados)): ?>
                                <tr><td colspan="7" class="text-center py-3 text-muted">Você ainda não fez nenhum agendamento.</td></tr>
                            <?php else: ?>
                                <?php foreach ($meus_grupos_paginados as $grupo_key => $grupo): ?>
                                    <?php
                                        $instancias = $grupo['instancias'];
                                        $dados_comuns = $grupo['dados_comuns'];
                                        $e_recorrente = count($instancias) > 1;
                                        $collapse_id = 'grupo-meus-' . preg_replace('/[^a-zA-Z0-9]/', '', $grupo_key);
                                    ?>
                                    <tr 
                                        class="<?= $e_recorrente ? 'accordion-toggle' : 'tr-unico' ?>" 
                                        <?php if ($e_recorrente): ?>
                                            data-bs-toggle="collapse" 
                                            data-bs-target="#<?= $collapse_id ?>" 
                                            aria-expanded="false" 
                                            aria-controls="<?= $collapse_id ?>"
                                        <?php endif; ?>
                                    >
                                        <td>
                                            <?php if ($e_recorrente): ?>
                                                <i class="fas fa-chevron-right"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($dados_comuns['recurso_nome']) ?></td>
                                        <td>
                                            <?php if (!empty($dados_comuns['disciplina_nome'])): ?>
                                                <strong><?= htmlspecialchars($dados_comuns['disciplina_nome']) ?></strong><br>
                                                <small class="text-muted"><?= htmlspecialchars($dados_comuns['turma_nome']) ?></small>
                                            <?php else: ?>
                                                <?= htmlspecialchars($dados_comuns['motivo'] ?: 'N/A') ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <!-- ⭐️ LÓGICA DE DATA/HORA ATUALIZADA -->
                                            <?php if ($e_recorrente): ?>
                                                <?php
                                                    $dia_semana_pt = formatar_data_br($instancias[0]['data_hora_inicio'], 'EEEE');
                                                ?>
                                                <strong><?= $dia_semana_pt ?> (<?= count($instancias) ?>x)</strong><br>
                                                <small>
                                                    <?= formatar_data_br(end($instancias)['data_hora_inicio'], 'd/MM/y') ?>
                                                    - <?= formatar_data_br(reset($instancias)['data_hora_inicio'], 'd/MM/y') ?>
                                                </small>
                                            <?php else: ?>
                                                <strong><?= formatar_data_br($dados_comuns['data_hora_inicio'], 'd/MM/Y') ?></strong><br>
                                                <small class="text-muted">
                                                    <?= formatar_data_br($dados_comuns['data_hora_inicio'], 'HH:mm') ?>
                                                    - <?= formatar_data_br($dados_comuns['data_hora_fim'], 'HH:mm') ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-<?= $dados_comuns['tipo_agendamento'] === 'fixo' ? 'info' : 'light text-dark' ?>">
                                                <?= ucfirst($dados_comuns['tipo_agendamento']) ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge status-<?= $dados_comuns['status'] ?>"><?= ucfirst($dados_comuns['status']) ?></span>
                                        </td>
                                        <td class="text-center">
                                            <?php 
                                            $ag_id_acao = $dados_comuns['id'];
                                            $link_params = "id={$ag_id_acao}&csrf_token={$csrf_token}";
                                            if ($e_recorrente) {
                                                $link_params = "grupo_id={$dados_comuns['grupo_recorrencia_id']}&csrf_token={$csrf_token}";
                                            }

                                            if (in_array($dados_comuns['status'], ['pendente', 'aprovado'])): ?>
                                                <a href="scripts/cancelar_agendamento.php?<?= $link_params ?>" 
                                                   class="btn btn-sm btn-outline-warning"
                                                   onclick="return confirm('Tem certeza que deseja CANCELAR <?= $e_recorrente ? 'TODOS OS ' . count($instancias) . ' agendamentos' : 'este agendamento' ?>?');"
                                                   title="Cancelar <?= $e_recorrente ? 'Série' : 'Agendamento' ?>">
                                                    <i class="fas fa-times"></i>
                                                </a>
                                            <?php elseif (in_array($dados_comuns['status'], ['recusado', 'cancelado'])): ?>
                                                <a href="scripts/remover_agendamento.php?<?= $link_params ?>" 
                                                   class="btn btn-sm btn-outline-danger"
                                                   onclick="return confirm('Tem certeza que deseja REMOVER <?= $e_recorrente ? 'esta série' : 'este agendamento' ?> da sua lista?');"
                                                   title="Remover <?= $e_recorrente ? 'Série' : 'Agendamento' ?>">
                                                    <i class="fas fa-trash-alt"></i>
                                                </a>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    
                                    <!-- LINHA EXPANSÍVEL (CONTEÚDO) -->
                                    <?php if ($e_recorrente): ?>
                                        <tr>
                                            <td colspan="7" class="p-0" style="border-top: none;"> 
                                                <div id="<?= $collapse_id ?>" class="collapse accordion-body">
                                                    <div class="p-3" style="background-color: #f8f9fa;">
                                                        <h6 class="mb-2">Instâncias Individuais (<?= count($instancias) ?>)</h6>
                                                        <ul class="list-group">
                                                            <?php foreach ($instancias as $instancia): ?>
                                                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                                                    <div>
                                                                        <i class="fas fa-calendar-day me-2"></i>
                                                                        <!-- ⭐️ TRADUÇÃO APLICADA AQUI -->
                                                                        <strong><?= formatar_data_br($instancia['data_hora_inicio'], 'EEEE, d/MM/Y') ?></strong>
                                                                        <span class="text-muted ms-2">
                                                                            (<?= formatar_data_br($instancia['data_hora_inicio'], 'HH:mm') ?>
                                                                            - <?= formatar_data_br($instancia['data_hora_fim'], 'HH:mm') ?>)
                                                                        </span>
                                                                    </div>
                                                                    <?php if (in_array($instancia['status'], ['pendente', 'aprovado'])): ?>
                                                                    <a href="scripts/cancelar_agendamento.php?id=<?= $instancia['id'] ?>&csrf_token=<?= $csrf_token ?>" 
                                                                       class="btn btn-sm btn-outline-warning btn-acao-individual"
                                                                       onclick="return confirm('Tem certeza que deseja CANCELAR apenas o agendamento do dia <?= formatar_data_br($instancia['data_hora_inicio'], 'd/MM/Y') ?>?');"
                                                                       title="Cancelar apenas esta data">
                                                                        <i class="fas fa-times me-1"></i> Cancelar Data
                                                                    </a>
                                                                    <?php endif; ?>
                                                                </li>
                                                            <?php endforeach; ?>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- ⭐️ Paginação (Meus Agendamentos) ATUALIZADA -->
                <?php gerar_paginacao($pagina_atual_meus, $total_paginas_meus, 'page_meus'); ?>
            </div>
        </div>
    </div>
</div>

<?php gerar_rodape(); ?>

