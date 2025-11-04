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
// ⭐️ FUNÇÃO DE AGRUPAMENTO
// ======================================================================
function agrupar_agendamentos($agendamentos) {
    $agrupados = [];
    foreach ($agendamentos as $ag) {
        // Se for fixo, usa o ID do grupo. Se for único, cria um 'grupo' falso.
        $grupo_key = $ag['grupo_recorrencia_id'] ?? 'unico_' . $ag['id'];

        // Adiciona a instância (o agendamento individual) a esse grupo
        $agrupados[$grupo_key]['instancias'][] = $ag;

        // Armazena os dados comuns do grupo (pega do primeiro item)
        if (!isset($agrupados[$grupo_key]['dados_comuns'])) {
            $agrupados[$grupo_key]['dados_comuns'] = $ag;
        }
    }
    return $agrupados;
}

// ======================================================================
// 1️⃣ LÓGICA: "MEUS AGENDAMENTOS" (Modal do usuário logado)
// ======================================================================

// Busca TODOS os agendamentos do usuário logado
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

// AGRUPA os resultados
$meus_agendamentos_agrupados = agrupar_agendamentos($meus_agendamentos_lista);

// FAZ A PAGINAÇÃO em cima dos GRUPOS
$ag_por_pagina_meus = 5;
$pagina_atual_meus = filter_input(INPUT_GET, 'page_meus', FILTER_VALIDATE_INT) ?? 1;
$offset_meus = ($pagina_atual_meus - 1) * $ag_por_pagina_meus;
$total_grupos_meus = count($meus_agendamentos_agrupados);
$total_paginas_meus = max(1, ceil($total_grupos_meus / $ag_por_pagina_meus));

// Fatiar o array de grupos para a página atual
$meus_grupos_paginados = array_slice($meus_agendamentos_agrupados, $offset_meus, $ag_por_pagina_meus, true);


// ======================================================================
// 2️⃣ LÓGICA: "TODOS OS AGENDAMENTOS" (Tabela principal)
// ======================================================================

// Busca TODOS os agendamentos do sistema
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
    ORDER BY a.grupo_recorrencia_id, a.data_hora_inicio DESC
";
$stmt_todos_completo = $pdo->prepare($sql_todos_completo);
$stmt_todos_completo->execute();
$todos_agendamentos_lista = $stmt_todos_completo->fetchAll(PDO::FETCH_ASSOC);

// AGRUPA os resultados
$todos_agendamentos_agrupados = agrupar_agendamentos($todos_agendamentos_lista);

// FAZ A PAGINAÇÃO em cima dos GRUPOS
$ag_por_pagina_todos = 10;
$pagina_atual_todos = filter_input(INPUT_GET, 'page_todos', FILTER_VALIDATE_INT) ?? 1;
$offset_todos = ($pagina_atual_todos - 1) * $ag_por_pagina_todos;
$total_grupos_todos = count($todos_agendamentos_agrupados);
$total_paginas_todos = max(1, ceil($total_grupos_todos / $ag_por_pagina_todos));

// Fatiar o array de grupos para a página atual
$todos_grupos_paginados = array_slice($todos_agendamentos_agrupados, $offset_todos, $ag_por_pagina_todos, true);


// ======================================================================
// Cabeçalho HTML
// ======================================================================
gerar_cabecalho('Painel de Agendamentos');
?>

<!-- ⭐️ ESTILO DO ACCORDION -->
<style>
    /* Estilo para a seta do accordion */
    .accordion-toggle[aria-expanded="true"] .bi-chevron-right {
        transform: rotate(90deg);
        transition: transform 0.2s ease-in-out;
    }
    
    .accordion-toggle[aria-expanded="false"] .bi-chevron-right {
        transform: rotate(0deg);
        transition: transform 0.2s ease-in-out;
    }

    /* Remove a borda padrão feia do bootstrap no collapse da tabela */
    .table > :not(caption) > * > .accordion-body {
        padding: 0;
        border-top: none; 
    }
    
    .table tr.accordion-toggle + tr > td {
        border-top: none !important;
        padding-top: 0;
    }

    .table tr.accordion-toggle {
        cursor: pointer;
    }
    
    .table tr.tr-unico {
        cursor: default;
    }

    /* Ícones dos botões de ação */
    .btn-acao-individual {
        font-size: 0.8rem;
    }
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
        <div class="card-header">Todos os Agendamentos</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 20px;"></th> <!-- Coluna do ícone -->
                            <th>Recurso</th>
                            <th>Usuário</th>
                            <th>Finalidade</th>
                            <th>Data e Hora</th>
                            <th class="text-center">Tipo</th>
                            <th class="text-center">Status</th>
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
                                        <?php if ($e_recorrente): ?>
                                            <strong>Recorrente (<?= count($instancias) ?>x)</strong><br>
                                            <small>
                                                De: <?= (new DateTime(end($instancias)['data_hora_inicio']))->format('d/m/y') ?>
                                                Até: <?= (new DateTime(reset($instancias)['data_hora_inicio']))->format('d/m/y') ?>
                                            </small>
                                        <?php else: ?>
                                            <?= (new DateTime($dados_comuns['data_hora_inicio']))->format('d/m/Y H:i') ?>
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
                                        <?php // Ações para o grupo (se necessário) ou para o item único
                                        $ag_id_acao = $dados_comuns['id']; // ID do primeiro item
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
                                                                    <strong><?= (new DateTime($instancia['data_hora_inicio']))->format('l, d/m/Y') ?></strong>
                                                                    <span class="text-muted ms-2">
                                                                        (<?= (new DateTime($instancia['data_hora_inicio']))->format('H:i') ?>
                                                                        - <?= (new DateTime($instancia['data_hora_fim']))->format('H:i') ?>)
                                                                    </span>
                                                                </div>
                                                                <!-- Ações para o item INDIVIDUAL -->
                                                                <?php if ($instancia['id_usuario'] == $id_usuario_logado && in_array($instancia['status'], ['pendente', 'aprovado'])): ?>
                                                                <a href="scripts/cancelar_agendamento.php?id=<?= $instancia['id'] ?>&csrf_token=<?= $csrf_token ?>" 
                                                                   class="btn btn-sm btn-outline-warning btn-acao-individual"
                                                                   onclick="return confirm('Tem certeza que deseja CANCELAR apenas o agendamento do dia <?= (new DateTime($instancia['data_hora_inicio']))->format('d/m/Y') ?>?');"
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

            <!-- Paginação (Todos os Agendamentos) -->
            <?php if ($total_paginas_todos > 1): ?>
                <nav class="paginacao mt-3 mb-3 d-flex justify-content-center align-items-center">
                    <a href="?page_todos=<?= max(1, $pagina_atual_todos - 1) ?>&page_meus=<?= $pagina_atual_meus ?>"
                       class="page-link-reservas <?= $pagina_atual_todos <= 1 ? 'disabled' : '' ?>">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <span class="mx-3">Página <?= $pagina_atual_todos ?> de <?= $total_paginas_todos ?></span>
                    <a href="?page_todos=<?= min($total_paginas_todos, $pagina_atual_todos + 1) ?>&page_meus=<?= $pagina_atual_meus ?>"
                       class="page-link-reservas <?= $pagina_atual_todos >= $total_paginas_todos ? 'disabled' : '' ?>">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </nav>
            <?php endif; ?>
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
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                         <thead>
                            <tr>
                                <th style="width: 20px;"></th> <!-- Coluna do ícone -->
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
                                        <td>
                                            <?php if (!empty($dados_comuns['disciplina_nome'])): ?>
                                                <strong><?= htmlspecialchars($dados_comuns['disciplina_nome']) ?></strong><br>
                                                <small class="text-muted"><?= htmlspecialchars($dados_comuns['turma_nome']) ?></small>
                                            <?php else: ?>
                                                <?= htmlspecialchars($dados_comuns['motivo'] ?: 'N/A') ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($e_recorrente): ?>
                                                <strong>Recorrente (<?= count($instancias) ?>x)</strong><br>
                                                <small>
                                                    De: <?= (new DateTime(end($instancias)['data_hora_inicio']))->format('d/m/y') ?>
                                                    Até: <?= (new DateTime(reset($instancias)['data_hora_inicio']))->format('d/m/y') ?>
                                                </small>
                                            <?php else: ?>
                                                <?= (new DateTime($dados_comuns['data_hora_inicio']))->format('d/m/Y H:i') ?>
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
                                                                        <strong><?= (new DateTime($instancia['data_hora_inicio']))->format('l, d/m/Y') ?></strong>
                                                                        <span class="text-muted ms-2">
                                                                            (<?= (new DateTime($instancia['data_hora_inicio']))->format('H:i') ?>
                                                                            - <?= (new DateTime($instancia['data_hora_fim']))->format('H:i') ?>)
                                                                        </span>
                                                                    </div>
                                                                    <?php if (in_array($instancia['status'], ['pendente', 'aprovado'])): ?>
                                                                    <a href="scripts/cancelar_agendamento.php?id=<?= $instancia['id'] ?>&csrf_token=<?= $csrf_token ?>" 
                                                                       class="btn btn-sm btn-outline-warning btn-acao-individual"
                                                                       onclick="return confirm('Tem certeza que deseja CANCELAR apenas o agendamento do dia <?= (new DateTime($instancia['data_hora_inicio']))->format('d/m/Y') ?>?');"
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

                <!-- Paginação (Meus Agendamentos) -->
                <?php if ($total_paginas_meus > 1): ?>
                    <nav class="paginacao mt-3 mb-3 d-flex justify-content-center align-items-center">
                        <a href="?page_meus=<?= max(1, $pagina_atual_meus - 1) ?>&page_todos=<?= $pagina_atual_todos ?>"
                           class="page-link-reservas <?= $pagina_atual_meus <= 1 ? 'disabled' : '' ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                        <span class="mx-3">Página <?= $pagina_atual_meus ?> de <?= $total_paginas_meus ?></span>
                        <a href="?page_meus=<?= min($total_paginas_meus, $pagina_atual_meus + 1) ?>&page_todos=<?= $pagina_atual_todos ?>"
                           class="page-link-reservas <?= $pagina_atual_meus >= $total_paginas_meus ? 'disabled' : '' ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php gerar_rodape(); ?>
