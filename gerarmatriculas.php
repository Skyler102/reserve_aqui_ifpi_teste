<?php
require_once __DIR__ . '/includes/session_sec.php';
require_once __DIR__ . '/includes/db_conexao.php';
require_once __DIR__ . '/includes/bib.php';

// Verificar se o usuário está autenticado
if (!isset($_SESSION['auth_gerador']) || $_SESSION['auth_gerador'] !== true) {
    header("Location: login_gerador.php");
    exit();
}

// --- Lógica de Paginação ---
$itens_por_pagina = 10;
$stmt_total = $pdo->query("SELECT COUNT(id) FROM matriculas_geradas");
$total_itens = $stmt_total->fetchColumn();
$total_paginas = ceil($total_itens / $itens_por_pagina);
$total_paginas = max($total_paginas, 1);
$pagina_atual = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?? 1;
if ($pagina_atual < 1 || $pagina_atual > $total_paginas) $pagina_atual = 1;
$offset = ($pagina_atual - 1) * $itens_por_pagina;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerador de Matrículas - Resolva Aqui IFPI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="registro/global.css">
</head>
<body>
    <main class="conteudo-principal">
        <div id="painel-gerador" class="gerenciador-container"> 
            
            <h2>Gerador de Matrículas</h2>
            <p class="subtitulo-form">Gere e gerencie matrículas para novos usuários do sistema.</p>

            <?php
                render_flash();
            ?>
            
            <form id="form-gerar-matricula" action="scripts/processa_geracao_matricula.php" method="post" class="mt-4">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <div id="form-gerador-linha">
                    <div id="form-grupo-select">
                        <label for="tipo_matricula" class="rotulo-campo">Tipo de Matrícula</label>
                        <select name="tipo_matricula" id="tipo_matricula" class="campo-input" required>
                            <option value="">Selecione o tipo de usuário</option>
                            <option value="PROF">Professor</option>
                            <option value="GEST">Gestor/Administrador</option>
                        </select>
                    </div>
                    
                    <button type="submit" id="btn-gerar-matricula" class="botao-acessar">
                        <i class="fas fa-plus-circle me-2"></i>Gerar Nova Matrícula
                    </button>
                </div>
            </form>
            
            <hr class="separador-gerador">

            <div class="tabela-container">
                <h3 class="mb-4">Matrículas Recentes</h3>
                <div class="tabela-matriculas">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Matrícula</th>
                                <th>Tipo</th>
                                <th>Data Geração</th>
                                <th>Status</th>
                                <th>Usuário</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            try {
                                $sql = "SELECT m.id, m.matricula, m.tipo_usuario, m.data_criacao, m.usado,
                                        IFNULL(u.nome_completo, 'Não utilizado') as usuario
                                        FROM matriculas_geradas m
                                        LEFT JOIN usuarios u ON m.matricula = u.matricula
                                        ORDER BY m.id DESC
                                        LIMIT :limit OFFSET :offset";
                                $stmt = $pdo->prepare($sql);
                                $stmt->bindValue(':limit', $itens_por_pagina, PDO::PARAM_INT);
                                $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
                                $stmt->execute();
                                while ($row = $stmt->fetch()) {
                                    $tipo = ($row['tipo_usuario'] == 'professor' || $row['tipo_usuario'] == 'PROF') ? 'Professor' : 'Gestor';
                                    $status = $row['usado'] ? '<span class="status-usado">Em uso</span>' : '<span class="status-disponivel">Disponível</span>';
                                    echo "<tr>";
                                    echo "<td>#{$row['id']}</td>";
                                    echo "<td>{$row['matricula']}</td>";
                                    echo "<td>{$tipo}</td>";
                                    echo "<td>" . date('d/m/Y H:i', strtotime($row['data_criacao'])) . "</td>";
                                    echo "<td>{$status}</td>";
                                    echo "<td>{$row['usuario']}</td>";
                                    echo "<td>";
                                    echo "<form method='post' action='scripts/delete_matricula.php' onsubmit=\"return confirm('Confirma excluir a matrícula {$row['matricula']}? Esta ação também removerá o usuário associado, se houver.');\">";
                                    echo "<input type='hidden' name='csrf_token' value='{$_SESSION['csrf_token']}'>";
                                    echo "<input type='hidden' name='id' value='{$row['id']}'>";
                                    echo "<button type='submit' class='btn-text-danger'><i class='fas fa-trash-alt'></i></button>";
                                    echo "</form>";
                                    echo "</td>";
                                    echo "</tr>";
                                }
                            } catch (PDOException $e) {
                                echo "<tr><td colspan='7' class='text-center'>Erro ao carregar matrículas</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <nav class="paginacao" aria-label="Navegação de página">
                <a href="?page=<?php echo max(1, $pagina_atual - 1); ?>" 
                   class="<?php echo ($pagina_atual <= 1) ? 'disabled' : ''; ?>" aria-label="Anterior">
                    <i class="fas fa-chevron-left"></i>
                </a>
                <span>Página <?php echo $pagina_atual; ?> de <?php echo $total_paginas; ?></span>
                <a href="?page=<?php echo min($total_paginas, $pagina_atual + 1); ?>" 
                   class="<?php echo ($pagina_atual >= $total_paginas) ? 'disabled' : ''; ?>" aria-label="Próxima">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </nav>
        </div>
    </main>
</body>
</html>