<?php
require_once __DIR__ . '/../../includes/session_sec.php';
require_once __DIR__ . '/../../includes/db_conexao.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Coleta os dados
    $email = $_POST['email'];
    $senha = $_POST['password'];

    // 2. Validação básica
    if (empty($email) || empty($senha)) {
        $_SESSION['login_error'] = "Email e senha são obrigatórios.";
        header("Location: ../login.php");
        exit();
    }

    // 3. Busca o usuário pelo email
    try {
        $stmt = $pdo->prepare("SELECT id, nome_completo, senha_hash, tipo_usuario FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();

        // 4. Verifica se o usuário existe e se a senha está correta
        if ($usuario && password_verify($senha, $usuario['senha_hash'])) {
            // Login bem-sucedido!
            
            // 5. Regenera o ID da sessão para segurança
            session_regenerate_id(true);

            // 6. Armazena os dados do usuário na sessão
            $_SESSION['id_usuario'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome_completo'];
            $_SESSION['usuario_tipo'] = $usuario['tipo_usuario'];

            // 7. Redireciona com base no tipo de usuário
            if ($usuario['tipo_usuario'] === 'admin') {
                header("Location: ../../painel_admin.php");
            } else {
                header("Location: ../../painel_agendamentos.php");
            }
            exit();

        } else {
            // Credenciais inválidas
            $_SESSION['login_error'] = "Email ou senha incorretos.";
            header("Location: ../login.php");
            exit();
        }

    } catch (PDOException $e) {
        $_SESSION['login_error'] = "Erro no servidor. Tente novamente mais tarde.";
        header("Location: ../login.php");
        exit();
    }

} else {
    header("Location: ../login.php");
    exit();
}
?>