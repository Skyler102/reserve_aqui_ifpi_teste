<?php
require_once __DIR__ . '/includes/session_sec.php';
require_once __DIR__ . '/includes/db_conexao.php';
require_once __DIR__ . '/includes/bib.php';

verificar_admin();
gerar_cabecalho('Painel Administrativo');

// ======================================================================
// ⭐️ FUNÇÃO DE AGRUPAMENTO (copiada para cá)
// ======================================================================
if (!function_exists('agrupar_agendamentos')) {
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
// ⭐️ LÓGICA: "GERENCIAR RESERVAS" (Tabela principal do Admin)
// ======================================================================

// Busca TODOS os agendamentos do sistema
$sql_admin_todos = "
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
$stmt_admin_todos = $pdo->prepare($sql_admin_todos);
$stmt_admin_todos->execute();
$admin_agendamentos_lista = $stmt_admin_todos->fetchAll(PDO::FETCH_ASSOC);

// AGRUPA os resultados
$admin_agendamentos_agrupados = agrupar_agendamentos($admin_agendamentos_lista);

// FAZ A PAGINAÇÃO em cima dos GRUPOS
$admin_ag_por_pagina = 10;
$admin_pagina_atual = filter_input(INPUT_GET, 'page_reservas', FILTER_VALIDATE_INT) ?? 1;
$admin_offset = ($admin_pagina_atual - 1) * $admin_ag_por_pagina;
$total_admin_grupos = count($admin_agendamentos_agrupados);
$total_admin_paginas = max(1, ceil($total_admin_grupos / $admin_ag_por_pagina));

// Fatiar o array de grupos para a página atual
$admin_grupos_paginados = array_slice($admin_agendamentos_agrupados, $admin_offset, $admin_ag_por_pagina, true);

?>

<!-- ⭐️ ESTILO DO ACCORDION (copiado para cá) -->
<style>
    .accordion-toggle[aria-expanded="true"] .bi-chevron-right {
        transform: rotate(90deg);
        transition: transform 0.2s ease-in-out;
    }
    .accordion-toggle[aria-expanded="false"] .bi-chevron-right {
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
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
        <div>
            <h2 class="h3 fw-bold">Painel Administrativo</h2>
            <p class="text-muted">Gerencie recursos, reservas, turmas e disciplinas.</p>
        </div>
        <div class="mt-2 mt-md-0">
            <button class="btn btn-outline-secondary ms-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAdicionar">Adicionar Recursos</button>
            <button class="btn btn-outline-secondary ms-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalRecursos">Gerenciar Recursos</button>
            <button class="btn btn-outline-secondary ms-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTurmas">Gerenciar Turmas</button>
            <button class="btn btn-outline-secondary ms-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalDisciplinas">Gerenciar Disciplinas</button>
            <a href="formulario_novo_agendamento.php" class="btn btn-acao shadow-sm">+ Nova Reserva</a>
        </div>
    </div>
    
    <?php render_flash(); ?>

    <!-- 
     ⭐️ BLOCO DE GERENCIAR RESERVAS MODIFICADO
     Agora usa renderização PHP com accordion
    -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0 h5 fw-bold">Gerenciar Reservas</h5>
        </div>
        <!-- ⭐️ ID ADICIONADO AQUI para o JavaScript de ações funcionar -->
        <div class="card-body p-0" id="container-reservas-admin"> 
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 20px;"></th> <!-- Ícone -->
                            <th>Usuário</th>
                            <th>Recurso</th>
                            <th>Finalidade</th>
                            <th>Data/Hora</th>
                            <th class="text-center">Tipo</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($admin_grupos_paginados)): ?>
                            <tr><td colspan="8" class="text-center py-3 text-muted">Nenhuma reserva encontrada.</td></tr>
                        <?php else: ?>
                            <?php foreach ($admin_grupos_paginados as $grupo_key => $grupo): ?>
                                <?php
                                    $instancias = $grupo['instancias'];
                                    $dados_comuns = $grupo['dados_comuns'];
                                    $e_recorrente = count($instancias) > 1;
                                    $collapse_id = 'grupo-admin-' . preg_replace('/[^a-zA-Z0-9]/', '', $grupo_key);
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
                                    <td><?= htmlspecialchars($dados_comuns['usuario_nome']) ?></td>
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
                                        <!-- Ações para o GRUPO -->
                                        <?php if ($dados_comuns['status'] === 'pendente'): ?>
                                            <button class="btn btn-sm btn-success" data-action="aprovar" data-id="<?= $dados_comuns['id'] ?>" data-grupo-id="<?= $e_recorrente ? $dados_comuns['grupo_recorrencia_id'] : '' ?>" title="Aprovar <?= $e_recorrente ? 'Série' : 'Agendamento' ?>"><i class="fas fa-check"></i></button>
                                            <button class="btn btn-sm btn-warning" data-action="recusar" data-id="<?= $dados_comuns['id'] ?>" data-grupo-id="<?= $e_recorrente ? $dados_comuns['grupo_recorrencia_id'] : '' ?>" title="Recusar <?= $e_recorrente ? 'Série' : 'Agendamento' ?>"><i class="fas fa-times"></i></button>
                                        <?php endif; ?>
                                        <button class="btn btn-sm btn-danger" data-action="deletar" data-id="<?= $dados_comuns['id'] ?>" data-grupo-id="<?= $e_recorrente ? $dados_comuns['grupo_recorrencia_id'] : '' ?>" title="Deletar <?= $e_recorrente ? 'Série' : 'Agendamento' ?>"><i class="fas fa-trash-alt"></i></button>
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
                                                                    <strong><?= (new DateTime($instancia['data_hora_inicio']))->format('l, d/m/Y H:i') ?></strong>
                                                                    <span class="badge status-<?= $instancia['status'] ?> ms-2"><?= ucfirst($instancia['status']) ?></span>
                                                                </div>
                                                                <!-- Ações para o item INDIVIDUAL -->
                                                                <div>
                                                                    <?php if ($instancia['status'] === 'pendente'): ?>
                                                                        <button class="btn btn-sm btn-success btn-acao-individual" data-action="aprovar" data-id="<?= $instancia['id'] ?>" title="Aprovar esta data"><i class="fas fa-check"></i></button>
                                                                        <button class="btn btn-sm btn-warning btn-acao-individual" data-action="recusar" data-id="<?= $instancia['id'] ?>" title="Recusar esta data"><i class="fas fa-times"></i></button>
                                                                    <?php endif; ?>
                                                                    <button class="btn btn-sm btn-danger btn-acao-individual" data-action="deletar" data-id="<?= $instancia['id'] ?>" title="Deletar esta data"><i class="fas fa-trash-alt"></i></button>
                                                                </div>
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

            <!-- Paginação (Reservas Admin) -->
            <?php if ($total_admin_paginas > 1): ?>
                <nav class="paginacao mt-3 mb-3 d-flex justify-content-center align-items-center">
                    <a href="?page_reservas=<?= max(1, $admin_pagina_atual) ?>"
                       class="page-link-reservas <?= $admin_pagina_atual <= 1 ? 'disabled' : '' ?>">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <span class="mx-3">Página <?= $admin_pagina_atual ?> de <?= $total_admin_paginas ?></span>
                    <a href="?page_reservas=<?= min($total_admin_paginas, $admin_pagina_atual + 1) ?>"
                       class="page-link-reservas <?= $admin_pagina_atual >= $total_admin_paginas ? 'disabled' : '' ?>">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </nav>
            <?php endif; ?>
        </div>
    </div>
    
    
    <!-- ====================================================== -->
    <!-- MODAIS DE RECURSOS, TURMAS, DISCIPLINAS (SEM ALTERAÇÃO) -->
    <!-- ====================================================== -->

    <!-- MODAL DE ADICIONAR RECURSOS -->
    <div class="modal fade" id="modalAdicionar" tabindex="-1" aria-labelledby="modalAdicionarLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title" id="modalAdicionarLabel">Adicionar Recursos</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button></div>
                <div class="modal-body">
                    <form id="form-recursos">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <div class="mb-3"><label class="form-label">Nome</label><input type="text" name="nome" class="form-control" required></div>
                        <div class="mb-3"><label class="form-label">Tipo</label><select name="tipo" class="form-select" required><option value="laboratorio">Laboratório</option><option value="quadra">Quadra</option><option value="auditorio">Auditório</option><option value="sala">Sala</option></select></div>
                        <div class="mb-3"><label class="form-label">Localização</label><input type="text" name="localizacao" class="form-control" required></div>
                        <div class="mb-3"><label class="form-label">Capacidade</label><input type="number" name="capacidade" class="form-control" min="1"></div>
                        <div class="mb-3"><label class="form-label">Descrição</label><textarea name="descricao" class="form-control" rows="3"></textarea></div>
                        <div class="d-flex justify-content-end"><button type="submit" class="btn btn-primary">Salvar Recurso</button></div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- MODAL DE GERENCIAR RECURSOS -->
    <div class="modal fade" id="modalRecursos" tabindex="-1" aria-labelledby="modalRecursosLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title" id="modalRecursosLabel">Gerenciar Recursos</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button></div>
                <div class="modal-body">
                    <div id="lista-recursos"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL DE GERENCIAR TURMAS -->
    <div class="modal fade" id="modalTurmas" tabindex="-1" aria-labelledby="modalTurmasLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title" id="modalTurmasLabel">Gerenciar Turmas</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button></div>
                <div class="modal-body">
                    <form id="form-turma" class="mb-4">
                        <div class="input-group">
                            <input type="text" name="nome" class="form-control" placeholder="Nome da nova turma" required>
                            <button type="submit" class="btn btn-acao">Adicionar</button>
                        </div>
                    </form>
                    <hr>
                    <h5 class="mt-4">Turmas Cadastradas</h5>
                    <div id="lista-turmas"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL DE GERENCIAR DISCIPLINAS -->
    <div class="modal fade" id="modalDisciplinas" tabindex="-1" aria-labelledby="modalDisciplinasLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title" id="modalDisciplinasLabel">Gerenciar Disciplinas</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button></div>
                <div class="modal-body">
                    <form id="form-disciplina" class="mb-4">
                        <div class="input-group">
                            <input type="text" name="nome" class="form-control" placeholder="Nome da nova disciplina" required>
                            <button type="submit" class="btn btn-acao">Adicionar</button>
                        </div>
                    </form>
                    <hr>
                    <h5 class="mt-4">Disciplinas Cadastradas</h5>
                    <div id="lista-disciplinas"></div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php gerar_rodape(); ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // NOVO: Função helper para escapar HTML e prevenir XSS
    const escapeHTML = (str) => str ? String(str).replace(/[&<>"']/g, match => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;'
    }[match])) : '';

    // --- ⭐️ LÓGICA ATUALIZADA PARA GERENCIAR RESERVAS ---
    
    // O carregamento agora é feito por PHP. 
    // Vamos manter apenas os listeners de AÇÃO
    
    const containerReservasAdminEl = document.getElementById('container-reservas-admin');

    // Listener para Ações (Aprovar, Recusar, Deletar)
    containerReservasAdminEl?.addEventListener('click', async function(e) {
        const btn = e.target.closest('button[data-action]');
        if (!btn) return;
        
        const { id, action, grupoId } = btn.dataset;
        let { id: acaoId, acaoQuery } = (grupoId && action !== 'deletar') 
            ? { id: grupoId, acaoQuery: `acao=${action}&grupo_id=${grupoId}` } // Ação em grupo (Aprovar/Recusar)
            : { id: id, acaoQuery: `acao=${action}&id=${id}` }; // Ação individual ou Deletar

        const confirmMessages = { 
            aprovar: `Deseja realmente APROVAR ${grupoId ? 'esta SÉRIE' : 'esta reserva'}?`, 
            recusar: `Deseja realmente RECUSAR ${grupoId ? 'esta SÉRIE' : 'esta reserva'}?`, 
            deletar: `Deseja realmente DELETAR ${grupoId ? 'esta SÉRIE' : 'esta reserva'}? Esta ação é irreversível.` 
        };
        
        // Ajuste para deleção individual dentro de um grupo
        if (action === 'deletar' && !grupoId) {
             confirmMessages.deletar = 'Deseja realmente DELETAR esta reserva individual? Esta ação é irreversível.';
        }
        
        if (confirm(confirmMessages[action])) {
            try {
                // A API é chamada da mesma forma
                const res = await fetch(`${BASE_URL}/api/agendamentos.php?${acaoQuery}`);
                const data = await res.json();
                if (!res.ok || !data.success) throw new Error(data.error || 'A API retornou um erro.');
                
                // ⭐️ MUDANÇA: Em vez de carregar, apenas recarrega a página
                alert('Ação executada com sucesso!');
                window.location.reload(); 
            
            } catch (e) {
                console.error(`Falha ao ${action} reserva:`, e);
                alert(`Erro ao executar ação: ${e.message}`);
            }
        }
    });
    
    // --- LÓGICA PARA ADICIONAR E GERENCIAR RECURSOS (sem alterações) ---

    const modalAdicionarRecursoEl = document.getElementById('modalAdicionar');
    const formRecursosEl = document.getElementById('form-recursos');
    const modalGerenciarRecursosEl = document.getElementById('modalRecursos');
    const listaRecursosEl = document.getElementById('lista-recursos');
    const RECURSOS_API_URL = `${BASE_URL}/api/recursos.php`;

    // 1. Lógica para Adicionar um novo recurso
    formRecursosEl?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(formRecursosEl);
        
        try {
            const res = await fetch(RECURSOS_API_URL, { method: 'POST', body: formData });
            const data = await res.json();

            if (!res.ok || !data.success) throw new Error(data.error || 'Falha ao salvar o recurso.');

            formRecursosEl.reset();
            const modalInstance = bootstrap.Modal.getInstance(modalAdicionarRecursoEl);
            modalInstance.hide();
            alert('Recurso adicionado com sucesso!');
            window.location.reload(); 
        } catch (err) {
            console.error('Erro ao adicionar recurso:', err);
            alert(`Erro ao salvar: ${err.message}`);
        }
    });

    // 2. Lógica para Gerenciar (Listar e Ativar/Desativar) recursos
    const carregarRecursos = async () => {
        if (!listaRecursosEl) return;
        listaRecursosEl.innerHTML = '<p class="text-center py-3">Carregando recursos...</p>';
        try {
            const res = await fetch(`${RECURSOS_API_URL}?list=1`, { cache: 'no-store' });
            const data = await res.json();
            if (!res.ok || !data.success) throw new Error(data.error || 'Erro na API de recursos.');

            if (data.items.length === 0) {
                listaRecursosEl.innerHTML = '<p class="text-center text-muted">Nenhum recurso cadastrado.</p>';
                return;
            }

            const tabelaHtml = `
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Tipo</th>
                            <th>Localização</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${data.items.map(item => `
                            <tr>
                                <td>${escapeHTML(item.nome)}</td>
                                <td>${escapeHTML(item.tipo_recurso)}</td>
                                <td>${escapeHTML(item.localizacao)}</td>
                                <td class="text-center">
                                    <div class="form-check form-switch d-inline-block">
                                        <input 
                                            class="form-check-input" 
                                            type="checkbox" 
                                            role="switch" 
                                            id="toggle-recurso-${item.id}" 
                                            ${item.ativo ? 'checked' : ''}
                                            data-action="toggle_status"
                                            data-id="${item.id}"
                                            data-status="${item.ativo ? 0 : 1}"
                                        >
                                        <label class="form-check-label" for="toggle-recurso-${item.id}">
                                            ${item.ativo ? 'Ativo' : 'Inativo'}
                                        </label>
                                    </div>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
            listaRecursosEl.innerHTML = tabelaHtml;
        } catch (e) {
            console.error('Falha ao carregar recursos:', e);
            listaRecursosEl.innerHTML = '<div class="alert alert-danger">Erro ao carregar dados dos recursos.</div>';
        }
    };
    
    modalGerenciarRecursosEl?.addEventListener('shown.bs.modal', carregarRecursos);
    
    listaRecursosEl?.addEventListener('click', async (e) => {
        const toggle = e.target.closest('input[data-action="toggle_status"]');
        if (!toggle) return;

        const { id, status } = toggle.dataset;
        try {
            const res = await fetch(`${RECURSOS_API_URL}?acao=toggle_status&id=${id}&status=${status}`);
            const data = await res.json();
            if (!res.ok || !data.success) throw new Error(data.error || 'Falha ao atualizar status.');
            
            await carregarRecursos(); // Recarrega a lista dentro do modal
            
        } catch (err) {
            console.error('Erro ao mudar status do recurso:', err);
            alert(`Erro ao mudar status: ${err.message}`);
        }
    });

    // --- LÓGICA REUTILIZÁVEL PARA TURMAS E DISCIPLINAS (sem alterações) ---
    const configurarGerenciador = ({ modalId, formId, listaId, apiUrl, nomeEntidade }) => {
        const modalEl = document.getElementById(modalId);
        const formEl = document.getElementById(formId);
        const listaEl = document.getElementById(listaId);

        if (!modalEl || !formEl || !listaEl) return;

        const carregarItens = async () => {
            listaEl.innerHTML = `<p class="text-center">Carregando...</p>`;
            try {
                const res = await fetch(`${apiUrl}?list=1`, { cache: 'no-store' });
                const data = await res.json();
                if (!res.ok || !data.success) throw new Error(data.error || 'Erro na API.');

                if (data.items.length === 0) {
                    listaEl.innerHTML = `<p class="text-center text-muted">Nenhum(a) ${nomeEntidade.toLowerCase()} cadastrado(a).</p>`;
                    return;
                }

                const listaHtml = data.items.map(item => `
                    <div class="d-flex justify-content-between align-items-center p-2 border-bottom">
                        <span>${escapeHTML(item.nome)}</span>
                        <button class="btn btn-sm btn-danger" data-action="deletar" data-id="${item.id}">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                `).join('');
                listaEl.innerHTML = listaHtml;
            } catch (e) {
                console.error(`Falha ao carregar ${nomeEntidade.toLowerCase()}s:`, e);
                listaEl.innerHTML = `<div class="alert alert-danger">Erro ao carregar dados.</div>`;
            }
        };

        formEl.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(formEl);
            try {
                const res = await fetch(apiUrl, { method: 'POST', body: formData });
                const data = await res.json();
                if (!res.ok || !data.success) throw new Error(data.error || 'Falha ao salvar.');
                
                formEl.reset();
                await carregarItens();
            } catch (e) {
                console.error(`Falha ao salvar ${nomeEntidade.toLowerCase()}:`, e);
                alert(`Erro ao salvar: ${e.message}`);
            }
        });

        listaEl.addEventListener('click', async (e) => {
            const btn = e.target.closest('button[data-action="deletar"]');
            if (!btn) return;

            const id = btn.dataset.id;
            if (confirm(`Deseja realmente deletar esta ${nomeEntidade.toLowerCase()}?`)) {
                try {
                    const res = await fetch(`${apiUrl}?acao=deletar&id=${id}`);
                    const data = await res.json();
                    if (!res.ok || !data.success) throw new Error(data.error || 'Falha ao deletar.');

                    await carregarItens();
                } catch (e) {
                    console.error(`Falha ao deletar ${nomeEntidade.toLowerCase()}:`, e);
                    alert(`Erro ao deletar: ${e.message}`);
                }
            }
        });

        modalEl.addEventListener('shown.bs.modal', carregarItens);
    };

    // Configurar o gerenciador para TURMAS
    configurarGerenciador({
        modalId: 'modalTurmas',
        formId: 'form-turma',
        listaId: 'lista-turmas',
        apiUrl: `${BASE_URL}/api/turmas.php`,
        nomeEntidade: 'Turma'
    });

    // Configurar o gerenciador para DISCIPLINAS
    configurarGerenciador({
        modalId: 'modalDisciplinas',
        formId: 'form-disciplina',
        listaId: 'lista-disciplinas',
        apiUrl: `${BASE_URL}/api/disciplinas.php`,
        nomeEntidade: 'Disciplina'
    });
});
</script>
</body>
</html>
