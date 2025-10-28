<?php
session_start();
// Inclua aqui o seu arquivo de conexão com o banco de dados
require_once '../../includes/db_conexao.php';

// Verifica se o método da requisição é POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Coleta e sanitiza os dados do formulário
    $nome_completo = trim($_POST['full_name']);
    $matricula = trim($_POST['registration']);
    $email = trim($_POST['email']);
    $senha = $_POST['password'];
    $senha_confirm = $_POST['password_confirm'];
    
    // Determina o tipo de usuário baseado no prefixo da matrícula
    $prefixo = substr($matricula, 0, 4);
    if ($prefixo === 'PROF') {
        $tipo_usuario = 'professor';
    } elseif ($prefixo === 'GEST') {
        $tipo_usuario = 'admin';
    } else {
        $_SESSION['register_error'] = "Matrícula inválida. Use uma matrícula gerada pelo sistema.";
        header("Location: ../registro.php");
        exit();
    }

    // 2. Validações
    if (empty($nome_completo) || empty($matricula) || empty($email) || empty($senha)) {
        $_SESSION['register_error'] = "Todos os campos são obrigatórios.";
        header("Location: ../registro.php");
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['register_error'] = "O formato do email é inválido.";
        header("Location: ../registro.php");
        exit();
    }

    if ($senha !== $senha_confirm) {
        $_SESSION['register_error'] = "As senhas não coincidem.";
        header("Location: ../registro.php");
        exit();
    }
    
    // 3. Verifica se a matrícula existe no banco de matrículas geradas e está disponível
    $stmt = $pdo->prepare("SELECT tipo_usuario, usado FROM matriculas_geradas WHERE matricula = ?");
    $stmt->execute([$matricula]);
    $matricula_info = $stmt->fetch();
    
    if (!$matricula_info) {
        $_SESSION['register_error'] = "Esta matrícula não foi gerada pelo sistema. Por favor, contacte um administrador.";
        header("Location: ../registro.php");
        exit();
    }

    if ($matricula_info['usado']) {
        $_SESSION['register_error'] = "Esta matrícula já está em uso.";
        header("Location: ../registro.php");
        exit();
    }

    if ($matricula_info['tipo_usuario'] !== $tipo_usuario) {
        $_SESSION['register_error'] = "Tipo de usuário não corresponde ao tipo da matrícula.";
        header("Location: ../registro.php");
        exit();
    }

    // 4. Verifica se o email já existe no banco
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $_SESSION['register_error'] = "Este email já está cadastrado.";
        header("Location: ../registro.php");
        exit();
    }

    // 4. Criptografa a senha com segurança
    $senha_hash = password_hash($senha, PASSWORD_ARGON2ID);

    // 5. Insere o novo usuário no banco de dados com o tipo especificado
    try {
        // Inicia a transação
        $pdo->beginTransaction();

        // Insere o novo usuário
        $stmt = $pdo->prepare(
            "INSERT INTO usuarios (nome_completo, matricula, email, senha_hash, tipo_usuario) 
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$nome_completo, $matricula, $email, $senha_hash, $tipo_usuario]);

        // Marca a matrícula como usada
        $stmt = $pdo->prepare("UPDATE matriculas_geradas SET usado = TRUE WHERE matricula = ?");
        $stmt->execute([$matricula]);

        // Confirma a transação
        $pdo->commit();

        $_SESSION['register_success'] = "Conta criada com sucesso! Faça o login.";
        header("Location: ../login.php");
        exit();

    } catch (PDOException $e) {
        // Desfaz a transação em caso de erro
        $pdo->rollBack();
        
        // Em um ambiente de produção, seria bom logar o erro em vez de exibi-lo
        $_SESSION['register_error'] = "Erro ao criar a conta. Tente novamente.";
        // $_SESSION['register_error'] = "Erro: " . $e->getMessage(); // Para depuração
        header("Location: ../registro.php");
        exit();
    }

} else {
    // Redireciona se o acesso não for via POST
    header("Location: ../registro.php");
    exit();
}
?>