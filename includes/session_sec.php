<?php
// Secure session initializer: configures cookie params and starts session if needed
// Use this include at the top of any script that needs sessions.

// Determine if connection is secure
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

// Security headers (minimal set, safe for most pages)
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer-when-downgrade');
if ($secure) {
    // HSTS only when connection is HTTPS
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

// Prevent caching of secure pages
if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['usuario_id'])) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
}

// Set cookie params (lifetime 0 = session cookie)
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '',
    'secure' => $secure,
    'httponly' => true,
    'samesite' => 'Lax'
]);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Ensure we have a CSRF token available
if (!isset($_SESSION['csrf_token'])) {
    try {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } catch (Exception $e) {
        // Fallback
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
        }
    }
}

// Compatibilidade: normalizar mensagens legadas para o formato único $_SESSION['flash']
// Aceita chaves antigas: 'mensagem' + 'mensagem_tipo' | 'tipo_mensagem', 'erro_login', 'login_error', 'register_error'
if (!isset($_SESSION['flash'])) {
    $flashMsg = null;
    $flashType = null;

    if (isset($_SESSION['mensagem'])) {
        $flashMsg = $_SESSION['mensagem'];
        // procurar tipo nas duas chaves usadas no projeto
        if (isset($_SESSION['mensagem_tipo'])) {
            $flashType = $_SESSION['mensagem_tipo'];
        } elseif (isset($_SESSION['tipo_mensagem'])) {
            $flashType = $_SESSION['tipo_mensagem'];
        }
    } elseif (isset($_SESSION['erro_login'])) {
        $flashMsg = $_SESSION['erro_login'];
        $flashType = 'danger';
    } elseif (isset($_SESSION['login_error'])) {
        $flashMsg = $_SESSION['login_error'];
        $flashType = 'danger';
    } elseif (isset($_SESSION['register_error'])) {
        $flashMsg = $_SESSION['register_error'];
        $flashType = 'danger';
    } elseif (isset($_SESSION['register_success'])) {
        $flashMsg = $_SESSION['register_success'];
        $flashType = 'success';
    }

    if ($flashMsg !== null) {
        // Normaliza tipos comuns
        $t = strtolower((string)($flashType ?? 'info'));
        if (in_array($t, ['error', 'erro', 'danger', 'dangero'], true)) $t = 'danger';
        if (in_array($t, ['success', 'sucesso'], true)) $t = 'success';
        if (in_array($t, ['warning', 'warn'], true)) $t = 'warning';
        if (in_array($t, ['info', 'information'], true)) $t = 'info';

        $_SESSION['flash'] = ['message' => $flashMsg, 'type' => $t];

        // Limpa chaves antigas para evitar dupla exibição
        unset($_SESSION['mensagem'], $_SESSION['mensagem_tipo'], $_SESSION['tipo_mensagem']);
        unset($_SESSION['erro_login'], $_SESSION['login_error'], $_SESSION['register_error']);
    }
}

?>
