<?php
require_once __DIR__ . '/includes/session_sec.php';
require_once __DIR__ . '/includes/db_conexao.php';
require_once __DIR__ . '/includes/bib.php';

verificar_admin();
gerar_cabecalho('Painel Administrativo');
?>
<?php
// CORREÇÃO: Busca pelos recursos ativos que estava faltando no seu código original
$recursos_ativos = $pdo->query("SELECT id, nome, tipo_recurso, localizacao FROM recursos WHERE ativo = 1 ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
?>

<main id="conteudo-principal" class="container mt-4 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h3 fw-bold">Painel Administrativo</h2>
            <p class="text-muted">Gerencie recursos e reservas.</p>
        </div>
        <div>
            <a href="formulario_novo_agendamento.php" class="btn btn-acao shadow-sm">+ Nova Reserva</a>
            <button id="btn-gerenciar-recursos" class="btn btn-outline-secondary ms-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalRecursos">Gerenciar Recursos</button>
            <button id="btn-gerenciar-reservas" class="btn btn-outline-secondary ms-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalReservas">Gerenciar Reservas</button>
        </div>
    </div>
    
    <?php render_flash(); ?>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header">Recursos Ativos</div>
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr><th>Nome</th><th>Tipo</th><th>Localização</th><th>Ações</th></tr>
                </thead>
                <tbody>
                    <?php
                    if (empty($recursos_ativos)) {
                        echo "<tr><td colspan='4' class='text-center py-3 text-muted'>Nenhum recurso ativo encontrado.</td></tr>";
                    } else {
                        foreach ($recursos_ativos as $lab) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($lab['nome']) . "</td>";
                        echo "<td>" . htmlspecialchars($lab['tipo_recurso']) . "</td>";
                        echo "<td>" . htmlspecialchars($lab['localizacao']) . "</td>";
                        echo "<td>";
                        echo "<a href='gerar_horario_pdf.php?id={$lab['id']}' class='btn btn-sm btn-outline-danger me-2' target='_blank'><i class='fas fa-file-pdf'></i> Gerar PDF</a>";
                        echo "<a href='pagina_qr_code.php?id={$lab['id']}&nome=" . urlencode($lab['nome']) . "' class='btn btn-sm btn-outline-secondary'><i class='fas fa-qrcode'></i> Ver QR Code</a>";
                        echo "</td>";
                        echo "</tr>";
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="modalRecursos" tabindex="-1" aria-labelledby="modalRecursosLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title" id="modalRecursosLabel">Gerenciar Recursos</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button></div>
                <div class="modal-body">
                    <form id="form-recursos">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <div class="mb-3"><label class="form-label">Nome</label><input type="text" name="nome" class="form-control" required></div>
                        <div class="mb-3"><label class="form-label">Tipo</label><select name="tipo" class="form-select" required><option value="Laboratório">Laboratório</option><option value="Quadra">Quadra</option><option value="Auditório">Auditório</option></select></div>
                        <div class="mb-3"><label class="form-label">Localização</label><input type="text" name="localizacao" class="form-control" required></div>
                        <div class="mb-3"><label class="form-label">Capacidade</label><input type="number" name="capacidade" class="form-control" min="1"></div>
                        <div class="mb-3"><label class="form-label">Descrição</label><textarea name="descricao" class="form-control" rows="3"></textarea></div>
                        <div class="d-flex justify-content-end"><button type="submit" class="btn btn-primary">Salvar Recurso</button></div>
                    </form>
                    <hr>
                    <h5 class="mt-4">Recursos Cadastrados</h5>
                    <div id="lista-recursos"> </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Novo Modal para Gerenciar Reservas -->
    <div class="modal fade" id="modalReservas" tabindex="-1" aria-labelledby="modalReservasLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalReservasLabel">Gerenciar Reservas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div id="lista-reservas-modal">
                        <!-- Conteúdo das reservas será carregado aqui via JS -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php gerar_rodape(); ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
    // --- Lógica para o Modal de Gerenciar Reservas ---
    const modalReservasEl = document.getElementById('modalReservas');
    const listaReservasModalEl = document.getElementById('lista-reservas-modal');

    const carregarReservas = async (page = 1) => {
        listaReservasModalEl.innerHTML = '<p class="text-center py-3">Carregando reservas...</p>';
        try {
            const res = await fetch(`${BASE_URL}/api/agendamentos.php?list=1&page=${page}`, { cache: 'no-store' });
            const data = await res.json();

            if (!res.ok || !data.success) {
                throw new Error(data.error || 'A API de reservas retornou uma resposta inesperada.');
            }

            const { items, total_paginas, pagina_atual } = data;
            let htmlReservas = '';

            if (items.length === 0) {
                htmlReservas = '<tr><td colspan="8" class="text-center py-3 text-muted">Nenhuma reserva encontrada.</td></tr>';
            } else {
                htmlReservas = items.map(reserva => {
                    const inicio = new Date(reserva.data_hora_inicio);
                    const fim = new Date(reserva.data_hora_fim);
                    const statusClasses = {
                        'pendente': 'badge bg-warning text-dark',
                        'aprovado': 'badge bg-success',
                        'recusado': 'badge bg-danger',
                        'finalizado': 'badge bg-secondary'
                    };
                    const statusClass = statusClasses[reserva.status] ?? 'badge bg-light text-dark';
                    
                    const acoesHtml = reserva.status === 'pendente'
                        ? `<button class="btn btn-sm btn-acao me-1" data-action="aprovar" data-id="${reserva.id}">Aprovar</button>` +
                          `<button class="btn btn-sm btn-acao-outline me-1" data-action="recusar" data-id="${reserva.id}">Recusar</button>`
                        : '';
                    
                    return `<tr>
                        <td>#${reserva.id}</td>
                        <td>${reserva.usuario_nome}</td>
                        <td>${reserva.recurso_nome}</td>
                        <td>${reserva.motivo}</td>
                        <td>${inicio.toLocaleDateString('pt-BR')} ${inicio.toLocaleTimeString('pt-BR', {hour: '2-digit', minute:'2-digit'})}</td>
                        <td>${fim.toLocaleDateString('pt-BR')} ${fim.toLocaleTimeString('pt-BR', {hour: '2-digit', minute:'2-digit'})}</td>
                        <td><span class="${statusClass}">${reserva.status.charAt(0).toUpperCase() + reserva.status.slice(1)}</span></td>
                        <td>
                            ${acoesHtml}
                            <button class="btn btn-sm btn-danger" data-action="deletar" data-id="${reserva.id}">Deletar</button>
                        </td>
                    </tr>`;
                }).join('');
            }

            const paginacaoHtml = total_paginas > 1 ? `
                <nav class="paginacao mt-3 mb-3" aria-label="Navegação de Reservas">
                    <a href="#" data-page="${Math.max(1, pagina_atual - 1)}" class="page-link-reservas ${pagina_atual <= 1 ? 'disabled' : ''}" aria-label="Anterior"><i class="fas fa-chevron-left"></i></a>
                    <span>Página ${pagina_atual} de ${total_paginas}</span>
                    <a href="#" data-page="${Math.min(total_paginas, pagina_atual + 1)}" class="page-link-reservas ${pagina_atual >= total_paginas ? 'disabled' : ''}" aria-label="Próxima"><i class="fas fa-chevron-right"></i></a>
                </nav>` : '';

            listaReservasModalEl.innerHTML = `
                <table class="table table-hover align-middle mb-0">
                    <thead><tr><th>ID</th><th>Usuário</th><th>Recurso</th><th>Motivo</th><th>Início</th><th>Fim</th><th>Status</th><th>Ações</th></tr></thead>
                    <tbody>${htmlReservas}</tbody>
                </table>${paginacaoHtml}`;

        } catch (e) {
            console.error('Falha ao carregar reservas:', e);
            listaReservasModalEl.innerHTML = `<div class="alert alert-danger">Erro ao carregar reservas. Verifique a conexão e o console (F12).</div>`;
        }
    };

    modalReservasEl?.addEventListener('shown.bs.modal', () => carregarReservas(1)); // Carrega a primeira página ao abrir o modal
    
    listaReservasModalEl?.addEventListener('click', async function(e) {
        const btn = e.target.closest('button[data-action]');
        if (!btn) return;

        const { id, action } = btn.dataset; // approve, refuse, delete
        
        const confirmMessages = {
            aprovar: 'Deseja realmente APROVAR esta reserva?',
            recusar: 'Deseja realmente RECUSAR esta reserva?',
            deletar: 'Deseja realmente DELETAR esta reserva? Esta ação é irreversível.'
        };

        if (confirm(confirmMessages[action])) {
            try {
                const res = await fetch(`${BASE_URL}/api/agendamentos.php?acao=${action}&id=${id}`);
                const data = await res.json();
                
                if (!res.ok || !data.success) {
                    throw new Error(data.error || 'A API retornou um erro.');
                }
                
                await carregarReservas(1); // Recarrega a lista de reservas no modal

            } catch (e) {
                console.error(`Falha ao ${action} reserva:`, e);
                alert(`Erro ao executar ação: ${e.message}`);
            }
        }
    });

    // Delegação de eventos para paginação de reservas
    listaReservasModalEl?.addEventListener('click', function(e) {
        const pageLink = e.target.closest('.page-link-reservas');
        if (pageLink && !pageLink.classList.contains('disabled')) {
            e.preventDefault();
            carregarReservas(parseInt(pageLink.dataset.page));
        }
    });
    });
</script>
</body>
</html>