<?php
// /scripts/remover_agendamento.php
// NOVO ARQUIVO: Lida com a remoção (delete) de agendamentos 'cancelados' ou 'recusados' pelo PRÓPRIO usuário.

require_once __DIR__ . '/../includes/session_sec.php';
require_once __DIR__ . '/../includes/db_conexao.php';
require_once __DIR__ . '/../includes/bib.php';

// 1. Verifica se o usuário está logado
verificar_login();

// 2. Verifica CSRF token
if (!isset($_GET['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_GET['csrf_token'])) {
    set_flash('Erro de validação de segurança. Tente novamente.', 'error');
    header('Location: ../painel_agendamentos.php');
    exit;
}

// 3. Pega IDs e o ID do usuário da sessão
$id_usuario_logado = $_SESSION['id_usuario'];
$id_agendamento = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$id_grupo = filter_input(INPUT_GET, 'grupo_id', FILTER_SANITIZE_SPECIAL_CHARS);

try {
    $pdo->beginTransaction();

    if ($id_grupo) {
        // Remover uma SÉRIE inteira (apenas se já estiverem cancelados/recusados)
        $stmt = $pdo->prepare(
            "DELETE FROM agendamentos 
             WHERE grupo_recorrencia_id = :grupo_id 
               AND id_usuario = :id_usuario 
               AND status IN ('cancelado', 'recusado')"
        );
        $stmt->execute([':grupo_id' => $id_grupo, ':id_usuario' => $id_usuario_logado]);
        $afetados = $stmt->rowCount();

    } elseif ($id_agendamento) {
        // Remover um ÚNICO agendamento
        $stmt = $pdo->prepare(
            "DELETE FROM agendamentos 
             WHERE id = :id 
               AND id_usuario = :id_usuario 
               AND status IN ('cancelado', 'recusado')"
        );
        $stmt->execute([':id' => $id_agendamento, ':id_usuario' => $id_usuario_logado]);
        $afetados = $stmt->rowCount();
        
    } else {
        throw new Exception('Nenhum ID de agendamento ou grupo fornecido.');
    }

    $pdo->commit();

    if ($afetados > 0) {
        set_flash('Agendamento(s) removido(s) com sucesso.', 'success');
    } else {
        set_flash('Nenhum agendamento encontrado para remoção ou você não tem permissão.', 'error');
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    set_flash('Erro ao remover agendamento: ' . $e->getMessage(), 'error');
}

// Redireciona de volta para o painel
header('Location: ../painel_agendamentos.php');
exit;
