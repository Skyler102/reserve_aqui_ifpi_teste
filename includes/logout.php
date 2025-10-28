<?php
/**
 * Script para encerrar a sessão do usuário (logout).
 */

// Inicia a sessão segura para poder manipulá-la.
require_once __DIR__ . '/../includes/session_sec.php';

// 1. Limpa todas as variáveis da sessão.
$_SESSION = [];

// 2. Apaga o cookie de sessão para invalidá-lo no navegador.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
}

// 3. Finalmente, destrói a sessão no servidor.
session_destroy();

// 4. Redireciona o usuário para a página de login.
header("Location: ../registro/login.php");
exit();