<?php
// Inicia a sessão para verificar as credenciais do usuário
session_start();

// Verifica se a sessão do usuário já existe
if (isset($_SESSION['usuario_id'])) {
    // Se existir, verifica o tipo de usuário e redireciona para o painel apropriado
    if ($_SESSION['usuario_tipo'] === 'admin') {
        header("Location: painel_admin.php");
    } else {
        header("Location: painel_agendamentos.php");
    }
} else {
    // Se não houver sessão ativa, redireciona para a página de login
    header("Location: registro/login.php");
}

// A função exit() garante que nenhum outro código será executado após o redirecionamento
exit();
?>