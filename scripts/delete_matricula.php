<?php
require_once __DIR__ . '/../includes/session_sec.php';
require_once __DIR__ . '/../includes/db_conexao.php';

// Verifica autenticação
if (!isset($_SESSION['auth_gerador']) || $_SESSION['auth_gerador'] !== true) {
    header('Location: ../login_gerador.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../gerarmatriculas.php');
    exit();
}

// Validate CSRF token
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    $_SESSION['mensagem'] = 'Token inválido. Ação não autorizada.';
    $_SESSION['tipo_mensagem'] = 'danger';
    header('Location: ../gerarmatriculas.php');
    exit();
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
if ($id <= 0) {
    $_SESSION['mensagem'] = 'ID inválido.';
    $_SESSION['tipo_mensagem'] = 'danger';
    header('Location: ../gerarmatriculas.php');
    exit();
}

try {    
    $pdo->beginTransaction();

    // Pega a matrícula para poder encontrar o usuário
    $stmt_get_matricula = $pdo->prepare('SELECT matricula FROM matriculas_geradas WHERE id = ?');
    $stmt_get_matricula->execute([$id]);
    $matricula_info = $stmt_get_matricula->fetch();

    if ($matricula_info) {
        // Se a matrícula existe, deleta o usuário que a possui (se houver)
        $stmt_delete_user = $pdo->prepare('DELETE FROM usuarios WHERE matricula = ?');
        $stmt_delete_user->execute([$matricula_info['matricula']]);
    }

    // Excluir a matrícula da tabela de matrículas geradas
    $stmt = $pdo->prepare('DELETE FROM matriculas_geradas WHERE id = ?');
    $stmt->execute([$id]);

    $pdo->commit();

    $_SESSION['mensagem'] = 'Matrícula excluída com sucesso.';
    $_SESSION['tipo_mensagem'] = 'success';
    header('Location: ../gerarmatriculas.php');
    exit();

} catch (PDOException $e) {
    // Log do erro para depuração sem expor detalhes ao usuário
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[delete_matricula] Erro ao excluir matrícula: ' . $e->getMessage());
    $_SESSION['mensagem'] = 'Erro ao excluir matrícula. Tente novamente mais tarde.';
    $_SESSION['tipo_mensagem'] = 'danger';
    header('Location: ../gerarmatriculas.php');
    exit();
}
