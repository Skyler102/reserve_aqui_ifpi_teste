<?php
require_once __DIR__ . '/../includes/session_sec.php';
require_once __DIR__ . '/../includes/db_conexao.php';

// Autenticação do gerador agora feita contra a tabela 'usuarios'.
if ($_SERVER["REQUEST_METHOD"] !== 'POST') {
    header("Location: ../login_gerador.php");
    exit();
}

$usuario = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
$senha = isset($_POST['senha']) ? $_POST['senha'] : '';

if ($usuario === '' || $senha === '') {
    $_SESSION['erro_login'] = "Usuário e senha são obrigatórios.";
    header("Location: ../login_gerador.php");
    exit();
}

// Adiciona uma verificação para o usuário "mestre" (gabriel/1234)
if ($usuario === 'gabriel' && $senha === '1234') {
    // Usuário mestre autenticado com sucesso
    session_regenerate_id(true);
    $_SESSION['auth_gerador'] = true;
    // Você pode opcionalmente definir um nome para a sessão
    $_SESSION['usuario_nome'] = 'Administrador Mestre';
    header("Location: ../gerarmatriculas.php");
    exit();
}

try {
    // Permitir busca por email ou matrícula
    $stmt = $pdo->prepare('SELECT id, nome_completo, senha_hash, tipo_usuario FROM usuarios WHERE email = ? OR matricula = ? LIMIT 1');
    $stmt->execute([$usuario, $usuario]);
    $row = $stmt->fetch();

    if ($row && isset($row['senha_hash']) && password_verify($senha, $row['senha_hash'])) {
        // Adicionado: Verifica se o usuário é um administrador
        if ($row['tipo_usuario'] !== 'admin') {
            $_SESSION['erro_login'] = "Acesso negado. Apenas administradores podem usar o gerador.";
            header("Location: ../login_gerador.php");
            exit();
        }

        // Usuário autenticado
        session_regenerate_id(true);
        $_SESSION['auth_gerador'] = true;
        $_SESSION['usuario_id'] = $row['id'];
        $_SESSION['usuario_nome'] = $row['nome_completo'];
        $_SESSION['usuario_tipo'] = $row['tipo_usuario'];

        header("Location: ../gerarmatriculas.php");
        exit();
    }

    // Falha na autenticação
    $_SESSION['erro_login'] = "Usuário ou senha incorretos.";
    header("Location: ../login_gerador.php");
    exit();

} catch (PDOException $e) {
    error_log('[auth_gerador] Erro ao autenticar: ' . $e->getMessage());
    $_SESSION['erro_login'] = 'Erro no servidor. Tente novamente mais tarde.';
    header("Location: ../login_gerador.php");
    exit();
}