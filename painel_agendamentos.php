<?php
require_once __DIR__ . '/includes/session_sec.php';
require_once __DIR__ . '/includes/db_conexao.php';
require_once __DIR__ . '/includes/bib.php';

verificar_login(); // Garante que o usuário está logado
$id_usuario_logado = $_SESSION['usuario_id'];

// --- Lógica de Paginação para Meus Agendamentos ---
$ag_por_pagina_meus = 5;
$stmt_total_meus = $pdo->prepare("SELECT COUNT(id) FROM agendamentos WHERE id_usuario = ?");
$stmt_total_meus->execute([$id_usuario_logado]);
$total_ag_meus = $stmt_total_meus->fetchColumn();
$total_paginas_meus = ceil($total_ag_meus / $ag_por_pagina_meus);
$pagina_atual_meus = filter_input(INPUT_GET, 'page_meus', FILTER_VALIDATE_INT) ?? 1;
$offset_meus = ($pagina_atual_meus - 1) * $ag_por_pagina_meus;

$sql_meus = "SELECT a.id, a.motivo, a.status, r.nome as recurso_nome FROM agendamentos a JOIN recursos r ON a.id_recurso = r.id WHERE a.id_usuario = :id_usuario ORDER BY a.data_hora_inicio DESC LIMIT :limit OFFSET :offset";
$stmt_meus = $pdo->prepare($sql_meus);
$stmt_meus->bindValue(':id_usuario', $id_usuario_logado, PDO::PARAM_INT);
$stmt_meus->bindValue(':limit', $ag_por_pagina_meus, PDO::PARAM_INT);
$stmt_meus->bindValue(':offset', $offset_meus, PDO::PARAM_INT);
$stmt_meus->execute();
$meus_agendamentos = $stmt_meus->fetchAll(PDO::FETCH_ASSOC);

// --- Lógica de Paginação para Todos os Agendamentos ---
$ag_por_pagina_todos = 10;
$stmt_total_todos = $pdo->query("SELECT COUNT(id) FROM agendamentos");
$total_ag_todos = $stmt_total_todos->fetchColumn();
$total_paginas_todos = ceil($total_ag_todos / $ag_por_pagina_todos);
$pagina_atual_todos = filter_input(INPUT_GET, 'page_todos', FILTER_VALIDATE_INT) ?? 1;
$offset_todos = ($pagina_atual_todos - 1) * $ag_por_pagina_todos;

$sql_todos = "SELECT a.id, r.nome as recurso_nome, u.nome_completo as usuario_nome, a.data_hora_inicio, a.status FROM agendamentos a JOIN recursos r ON a.id_recurso = r.id JOIN usuarios u ON a.id_usuario = u.id ORDER BY a.data_hora_inicio DESC LIMIT :limit OFFSET :offset";
$stmt_todos = $pdo->prepare($sql_todos);
$stmt_todos->bindValue(':limit', $ag_por_pagina_todos, PDO::PARAM_INT);
$stmt_todos->bindValue(':offset', $offset_todos, PDO::PARAM_INT);
$stmt_todos->execute();
$todos_agendamentos = $stmt_todos->fetchAll(PDO::FETCH_ASSOC);

gerar_cabecalho('Painel de Agendamentos');
?>

<main id="conteudo-principal" class="container mt-4 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 fw-bold">Painel de Agendamentos</h2>
        <a href="formulario_novo_agendamento.php" class="btn btn-acao shadow-sm">+ Novo Agendamento</a>
    </div>

    <?php render_flash(); ?>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header">Meus Agendamentos</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Recurso</th>
                            <th>Motivo</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($meus_agendamentos)): ?>
                            <tr><td colspan="3" class="text-center py-3 text-muted">Você ainda não fez nenhum agendamento.</td></tr>
                        <?php else: ?>
                            <?php foreach ($meus_agendamentos as $ag): ?>
                                <tr>
                                    <td><?= htmlspecialchars($ag['recurso_nome']) ?></td>
                                    <td><?= htmlspecialchars($ag['motivo']) ?></td>
                                    <td class="text-center"><span class="badge status-<?= $ag['status'] ?>"><?= ucfirst($ag['status']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if ($total_paginas_meus > 1): ?>
            <nav class="paginacao" aria-label="Navegação de Meus Agendamentos">
                 <a href="?page_meus=<?= max(1, $pagina_atual_meus - 1); ?>" class="<?= ($pagina_atual_meus <= 1) ? 'disabled' : ''; ?>"><i class="fas fa-chevron-left"></i></a>
                 <span>Página <?= $pagina_atual_meus; ?> de <?= $total_paginas_meus; ?></span>
                 <a href="?page_meus=<?= min($total_paginas_meus, $pagina_atual_meus + 1); ?>" class="<?= ($pagina_atual_meus >= $total_paginas_meus) ? 'disabled' : ''; ?>"><i class="fas fa-chevron-right"></i></a>
            </nav>
        <?php endif; ?>
        </div>

    <div class="card shadow-sm border-0 mt-5">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Todos os Agendamentos</span>
            <a href="gerar_relatorio_agendamentos.php" target="_blank" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-print me-1"></i>Imprimir Relatório
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Recurso</th>
                            <th>Usuário</th>
                            <th>Data e Hora</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($todos_agendamentos)): ?>
                            <tr><td colspan="5" class="text-center py-3 text-muted">Nenhum agendamento encontrado.</td></tr>
                        <?php else: ?>
                            <?php foreach ($todos_agendamentos as $ag): ?>
                                <tr>
                                    <td><?= htmlspecialchars($ag['recurso_nome']) ?></td>
                                    <td><?= htmlspecialchars($ag['usuario_nome']) ?></td>
                                    <td><?= (new DateTime($ag['data_hora_inicio']))->format('d/m/Y H:i') ?></td>
                                    <td class="text-center"><span class="badge status-<?= $ag['status'] ?>"><?= ucfirst($ag['status']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if ($total_paginas_todos > 1): ?>
            <nav class="paginacao" aria-label="Navegação de Todos os Agendamentos">
                 <a href="?page_todos=<?= max(1, $pagina_atual_todos - 1); ?>" class="<?= ($pagina_atual_todos <= 1) ? 'disabled' : ''; ?>"><i class="fas fa-chevron-left"></i></a>
                 <span>Página <?= $pagina_atual_todos; ?> de <?= $total_paginas_todos; ?></span>
                 <a href="?page_todos=<?= min($total_paginas_todos, $pagina_atual_todos + 1); ?>" class="<?= ($pagina_atual_todos >= $total_paginas_todos) ? 'disabled' : ''; ?>"><i class="fas fa-chevron-right"></i></a>
            </nav>
        <?php endif; ?>
    </div>
</main>

<?php gerar_rodape(); ?>