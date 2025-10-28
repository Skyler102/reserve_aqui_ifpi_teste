<?php
require_once __DIR__ . '/../includes/session_sec.php';
require_once __DIR__ . '/../includes/db_conexao.php';

// Verificar autenticação
if (!isset($_SESSION['auth_gerador']) || $_SESSION['auth_gerador'] !== true) {
    header("Location: ../login_gerador.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF validation
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $_SESSION['mensagem'] = 'Token inválido. Tente novamente.';
        $_SESSION['tipo_mensagem'] = 'danger';
        header("Location: ../gerarmatriculas.php");
        exit();
    }

    $tipo = $_POST['tipo_matricula'];
    // Validação do tipo esperado
    if (!in_array($tipo, ['PROF', 'GEST'], true)) {
        $_SESSION['mensagem'] = 'Tipo de matrícula inválido.';
        $_SESSION['tipo_mensagem'] = 'danger';
        header("Location: ../gerarmatriculas.php");
        exit();
    }
    $ano_atual = date('Y');
    
    try {
        // Buscar o último número sequencial para o tipo e ano
        $stmt = $pdo->prepare("SELECT MAX(SUBSTRING(matricula, 9, 4)) as ultimo_numero 
                              FROM matriculas_geradas 
                              WHERE matricula LIKE ? AND SUBSTRING(matricula, 5, 4) = ?");
        $stmt->execute([$tipo . '%', $ano_atual]);
        $resultado = $stmt->fetch();

        $proximo_numero = str_pad(($resultado['ultimo_numero'] ?? 0) + 1, 4, '0', STR_PAD_LEFT);
        $nova_matricula = $tipo . $ano_atual . $proximo_numero;

        // Inserir a nova matrícula gerada reutilizando o menor id disponível
        $tipo_usuario = ($tipo === 'PROF') ? 'professor' : 'admin';

        // Iniciar transação e bloquear os ids existentes para evitar concorrência
        $pdo->beginTransaction();

        // Selecionar todos os ids em ordem para achar o menor gap (FOR UPDATE para bloqueio)
        $stmt = $pdo->query('SELECT id FROM matriculas_geradas ORDER BY id ASC FOR UPDATE');
        $expected = 1;
        while ($row = $stmt->fetch()) {
            if ((int)$row['id'] !== $expected) {
                break; // found the gap: expected is available
            }
            $expected++;
        }
        $id_para_inserir = $expected;

        // Inserir especificando o id encontrado
        $insert = $pdo->prepare('INSERT INTO matriculas_geradas (id, matricula, tipo_usuario) VALUES (?, ?, ?)');
        $insert->execute([$id_para_inserir, $nova_matricula, $tipo_usuario]);

        $pdo->commit();

        $_SESSION['mensagem'] = "Matrícula gerada com sucesso: " . $nova_matricula . " (id: " . $id_para_inserir . ")";
        $_SESSION['tipo_mensagem'] = "success";
    } catch (PDOException $e) {
        // Se algo falhar, tentar rollback se in transaction
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        // Não expor mensagens detalhadas ao usuário em produção
        error_log('[processa_geracao_matricula] Erro ao gerar matrícula: ' . $e->getMessage());
        $_SESSION['mensagem'] = "Erro ao gerar matrícula. Tente novamente mais tarde.";
        $_SESSION['tipo_mensagem'] = "danger";
    }
    
    header("Location: ../gerarmatriculas.php");
    exit();
} else {
    header("Location: ../gerarmatriculas.php");
    exit();
}