<?php
require_once __DIR__ . '/includes/session_sec.php';
require_once __DIR__ . '/includes/db_conexao.php';
require_once __DIR__ . '/includes/bib.php';

verificar_admin();
gerar_cabecalho('Painel Administrativo');
?>
<?php
// --- Lógica de Paginação para Reservas ---
$reservas_por_pagina = 10;

// 1. Obter o número total de reservas
$stmt_total_reservas = $pdo->query("SELECT COUNT(id) FROM agendamentos");
$total_reservas = $stmt_total_reservas->fetchColumn();

// 2. Calcular o número total de páginas
$total_paginas_reservas = ceil($total_reservas / $reservas_por_pagina);
$total_paginas_reservas = max($total_paginas_reservas, 1); // Garante que haja pelo menos 1 página

// 3. Obter a página atual da URL, default é 1
$pagina_atual_reservas = filter_input(INPUT_GET, 'page_reservas', FILTER_VALIDATE_INT) ?? 1;
if ($pagina_atual_reservas < 1 || $pagina_atual_reservas > $total_paginas_reservas) $pagina_atual_reservas = 1;

// 4. Calcular o offset para a consulta SQL
$offset_reservas = ($pagina_atual_reservas - 1) * $reservas_por_pagina;

// Fetch reservations with pagination
$sql_reservas = "
    SELECT 
        a.id, a.motivo, a.data_hora_inicio, a.data_hora_fim, a.status,
        u.nome_completo as usuario_nome, r.nome as recurso_nome
    FROM agendamentos a
    JOIN usuarios u ON a.id_usuario = u.id
    JOIN recursos r ON a.id_recurso = r.id
    ORDER BY a.data_hora_inicio DESC
    LIMIT :limit OFFSET :offset
";
$stmt_reservas = $pdo->prepare($sql_reservas);
$stmt_reservas->bindValue(':limit', $reservas_por_pagina, PDO::PARAM_INT);
$stmt_reservas->bindValue(':offset', $offset_reservas, PDO::PARAM_INT);
$stmt_reservas->execute();
$reservas = $stmt_reservas->fetchAll(PDO::FETCH_ASSOC);
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
        </div>
    </div>
    
    <?php render_flash(); ?>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header">Recursos Ativos</div>
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr><th>Nome</th><th>Tipo</th><th>Localização</th><th>QR Code</th></tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $pdo->query("SELECT id, nome, tipo_recurso, localizacao FROM recursos WHERE ativo = 1 ORDER BY nome");
                    while ($lab = $stmt->fetch()) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($lab['nome']) . "</td>";
                        echo "<td>" . htmlspecialchars($lab['tipo_recurso']) . "</td>";
                        echo "<td>" . htmlspecialchars($lab['localizacao']) . "</td>";
                        echo "<td><a href='pagina_qr_code.php?id={$lab['id']}&nome=" . urlencode($lab['nome']) . 
                             "' class='btn btn-sm btn-outline-secondary'><i class='fas fa-qrcode'></i> Ver QR Code</a></td>";
                        echo "</tr>";
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
                        <div class="mb-3"><label class="form-label">Capacidade</label><input type="number" name="capacidade" class="form-control"></div>
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

    <div class="card shadow-sm border-0">
        <div class="card-header">Gerenciar Reservas</div>
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuário</th>
                        <th>Recurso</th>
                        <th>Motivo</th>
                        <th>Início</th>
                        <th>Fim</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reservas)): ?>
                        <tr><td colspan="8" class="text-center py-3 text-muted">Nenhuma reserva encontrada.</td></tr>
                    <?php else: ?>
                        <?php foreach ($reservas as $reserva): ?>
                            <?php
                                $inicio = new DateTime($reserva['data_hora_inicio']);
                                $fim = new DateTime($reserva['data_hora_fim']);
                                $statusClasses = [
                                    'pendente' => 'badge bg-warning text-dark',
                                    'aprovado' => 'badge bg-success',
                                    'recusado' => 'badge bg-danger'
                                ];
                                $statusClass = $statusClasses[$reserva['status']] ?? 'badge bg-secondary';
                            ?>
                            <tr>
                                <td>#<?= htmlspecialchars($reserva['id']) ?></td>
                                <td><?= htmlspecialchars($reserva['usuario_nome']) ?></td>
                                <td><?= htmlspecialchars($reserva['recurso_nome']) ?></td>
                                <td><?= htmlspecialchars($reserva['motivo']) ?></td>
                                <td><?= $inicio->format('d/m/Y H:i') ?></td>
                                <td><?= $fim->format('d/m/Y H:i') ?></td>
                                <td><span class="<?= $statusClass ?>"><?= ucfirst($reserva['status']) ?></span></td>
                                <td>
                                    <?php if ($reserva['status'] === 'pendente'): ?>
                                        <button class="btn btn-sm btn-acao me-1" data-action="aprovar" data-id="<?= $reserva['id'] ?>">Aprovar</button>
                                        <button class="btn btn-sm btn-acao-outline me-1" data-action="recusar" data-id="<?= $reserva['id'] ?>">Recusar</button>
                                    <?php endif; ?>
                                    <button class="btn btn-sm btn-danger" data-action="deletar" data-id="<?= $reserva['id'] ?>">Deletar</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <!-- Controles de Paginação para Reservas -->
            <?php if ($total_paginas_reservas > 1): ?>
                <nav class="paginacao mt-3 mb-3" aria-label="Navegação de Reservas">
                    <a href="?page_reservas=<?= max(1, $pagina_atual_reservas - 1); ?>" 
                       class="<?= ($pagina_atual_reservas <= 1) ? 'disabled' : ''; ?>" 
                       aria-label="Anterior">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <span>Página <?= $pagina_atual_reservas; ?> de <?= $total_paginas_reservas; ?></span>
                    <a href="?page_reservas=<?= min($total_paginas_reservas, $pagina_atual_reservas + 1); ?>" 
                       class="<?= ($pagina_atual_reservas >= $total_paginas_reservas) ? 'disabled' : ''; ?>" 
                       aria-label="Próxima">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </nav>
            <?php endif; ?>
            </div>
    </div>
</main>

<?php gerar_rodape(); ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const formRecursos = document.getElementById('form-recursos');
    const listaRecursosEl = document.getElementById('lista-recursos');
    const modalRecursosEl = document.getElementById('modalRecursos');

    const exibirAlerta = (container, mensagem, tipo = 'danger') => {
        container.innerHTML = `<div class="alert alert-${tipo}">${mensagem}</div>`;
    };

    const carregarRecursos = async () => {
        listaRecursosEl.innerHTML = '<p>Carregando recursos...</p>';
        try {
            const res = await fetch(`${BASE_URL}/api/recursos.php?list=1`, { cache: 'no-store' });
            const data = await res.json();

            if (!res.ok || !data.success) {
                throw new Error(data.error || 'A API retornou uma resposta inesperada.');
            }

            const itens = data.items || [];
            if (itens.length === 0) {
                exibirAlerta(listaRecursosEl, 'Nenhum recurso cadastrado.', 'info');
                return;
            }

            const html = itens.map(r => {
                const statusBtn = r.ativo 
                    ? `<button class="btn btn-sm btn-outline-danger" data-id="${r.id}" data-action="desativar">Desativar</button>`
                    : `<button class="btn btn-sm btn-outline-success" data-id="${r.id}" data-action="ativar">Ativar</button>`;
                
                return `<li class="list-group-item d-flex justify-content-between align-items-center ${!r.ativo ? 'list-group-item-light text-muted' : ''}">
                    <div>
                        <strong>${r.nome}</strong> ${!r.ativo ? '<span class="badge bg-secondary">Inativo</span>' : ''}
                        <br><small>${r.tipo_recurso} — ${r.localizacao}</small>
                    </div>
                    <div>${statusBtn}</div>
                </li>`;
            }).join('');

            listaRecursosEl.innerHTML = `<ul class="list-group">${html}</ul>`;
        } catch (e) {
            console.error('Falha ao carregar recursos:', e);
            exibirAlerta(listaRecursosEl, `Erro ao carregar recursos. Verifique a conexão e o console (F12).`);
        }
    };

    formRecursos?.addEventListener('submit', async function(ev) {
        ev.preventDefault();
        const btn = ev.submitter;
        btn.disabled = true;
        btn.innerHTML = 'Salvando...';
        
        try {
            const res = await fetch(`${BASE_URL}/api/recursos.php`, { method: 'POST', body: new FormData(formRecursos) });
            const data = await res.json();

            if (!res.ok || !data.success) {
                throw new Error(data.error || 'Não foi possível salvar o recurso.');
            }
            
            formRecursos.reset();
            await carregarRecursos(); // Recarrega a lista no modal
            window.location.reload(); // Recarrega a página para atualizar a tabela principal de recursos ativos

        } catch (e) {
            console.error('Falha ao salvar recurso:', e);
            alert(`Erro: ${e.message}`);
        } finally {
            btn.disabled = false;
            btn.innerHTML = 'Salvar Recurso';
        }
    });

    listaRecursosEl?.addEventListener('click', async function(e) {
        const btn = e.target.closest('button[data-action]');
        if (!btn) return;

        const { id, action } = btn.dataset;
        const newStatus = (action === 'ativar') ? 1 : 0;
        
        if (confirm(`Deseja realmente ${action} este recurso?`)) {
            try {
                const res = await fetch(`${BASE_URL}/api/recursos.php?acao=toggle_status&id=${id}&status=${newStatus}`);
                const data = await res.json();
                
                if (!res.ok || !data.success) {
                    throw new Error(data.error || 'A API retornou um erro.');
                }
                
                await carregarRecursos();
                window.location.reload();

            } catch (e) {
                console.error(`Falha ao ${action} recurso:`, e);
                alert(`Erro ao alterar status: ${e.message}`);
            }
        }
    });

    modalRecursosEl?.addEventListener('shown.bs.modal', carregarRecursos);
});
</script>
</body>
</html>