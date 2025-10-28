<?php
// Inicia a sessão se ela ainda não tiver sido iniciada.
// Essencial para que as variáveis de sessão ($_SESSION) funcionem.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Retorna a URL base da aplicação, tratando subdiretórios.
 * @return string A URL base, ex: "" ou "/subpasta"
 */
function get_base_url() {
    $script_dir = dirname($_SERVER['SCRIPT_NAME']);
    // Se o script está em uma subpasta como /registro, sobe um nível.
    if (basename($script_dir) === 'registro' || basename($script_dir) === 'scripts') {
        return dirname($script_dir);
    }
    // Normaliza para "" se estiver na raiz, ou "/subpasta" se não.
    return rtrim($script_dir, '/\\');
}

/**
 * Gera o cabeçalho HTML padrão para as páginas do painel.
 * Inclui o <head>, a barra de acessibilidade e a navegação principal.
 * @param string $titulo_pagina O título que aparecerá na aba do navegador.
 */
function gerar_cabecalho($titulo_pagina) {
    $base_url = get_base_url();
    
    // Lógica para definir o link principal com base no tipo de usuário logado
    $link_inicio = $base_url . '/painel_agendamentos.php'; // Link padrão para 'professor'
    if (isset($_SESSION['usuario_tipo']) && $_SESSION['usuario_tipo'] === 'admin') {
        $link_inicio = $base_url . '/painel_admin.php';
    }

    // Define o caminho para o CSS global
    $path_css = 'global.css';
    if (strpos($_SERVER['REQUEST_URI'], '/registro/') !== false) $path_css = 'registro/global.css';

    // Usando a sintaxe HEREDOC para escrever o bloco de HTML de forma limpa
    echo <<<HTML
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$titulo_pagina} - Resolva Aqui IFPI</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/main.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700;800&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{$base_url}/{$path_css}">

    <script>
        // Define a URL base para ser usada em chamadas JavaScript (fetch)
        const BASE_URL = '{$base_url}';
    </script>
</head>
<body>

<header>
    <div id="barra-acessibilidade">
        <div class="container">
            <ul id="links-acessibilidade">
                <li><a href="#conteudo-principal">Ir para o conteúdo <span>1</span></a></li>
                <li><a href="#navegacao-servicos">Ir para o menu <span>2</span></a></li>
                <li><a href="#TextoBuscavel">Ir para a busca <span>3</span></a></li>
                <li><a href="#rodape-principal">Ir para o rodapé <span>4</span></a></li>
            </ul>
        </div>
    </div>
    <div id="cabecalho-principal">
        <div class="container">
            <div id="container-logo">
                <p class="marca-subtitulo m-0">Resolva Aqui</p>
                <h1 class="marca-titulo">IFPI</h1>
                <p class="marca-descricao m-0">MINISTÉRIO DA EDUCAÇÃO</p>
            </div>
            <div id="container-busca-social">
                <div id="caixa-busca-portal">
                    <form action="#">
                        <label class="estrutura-oculta" for="TextoBuscavel">Buscar no portal</label>
                        <input name="TextoBuscavel" type="text" title="Buscar no portal" placeholder="Buscar no portal" class="campo-busca" id="TextoBuscavel">
                        <button class="botao-busca" type="submit" aria-label="Buscar"><i class="fa fa-search"></i></button>
                    </form>
                </div>
                <div id="icones-sociais">
                    <ul>
                        <li><a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a></li>
                        <li><a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a></li>
                        <li><a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a></li>
                        <li><a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <nav id="barra-servicos" aria-label="Menu de Serviços">
        <div class="container">
            <ul id="navegacao-servicos">
                <li><a href="{$link_inicio}" class="btn-nav btn-nav-principal">Início</a></li>
                <li><a href="{$base_url}/includes/logout.php" class="btn-nav btn-nav-sair">Sair</a></li>
            </ul>
        </div>
    </nav>
</header>
HTML;
}

/**
 * Gera o rodapé padrão da página, incluindo scripts JS.
 */
function gerar_rodape() {
    echo <<<HTML
<footer id="rodape-principal" class="rodape">
    <div class="container"><span>© 2025 Resolva Aqui IFPI - Instituto Federal do Piauí</span></div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
HTML;
}

/**
 * Verifica se o usuário está logado através da sessão. 
 * Se não estiver, redireciona para a página de login e encerra a execução do script.
 */
    function verificar_login() {
        // Se a variável de sessão 'usuario_id' não existir, significa que não há ninguém logado.
        $base_url = get_base_url();
        if (!isset($_SESSION['usuario_id'])) {
            // Se já estamos na pasta registro, o caminho é diferente
            if (strpos($_SERVER['REQUEST_URI'], '/registro/') !== false) {
                header("Location: login.php");
            } else {
                header("Location: {$base_url}/registro/login.php");
            }
            exit(); // A função exit() é crucial para parar a execução do script imediatamente.
        }
    }

    /**
     * Redireciona o usuário para seu painel correto com base no tipo de usuário.
     * Usar no topo das páginas de painel após verificar_login().
     * @param string $pagina_atual Nome da página atual (ex: 'admin' ou 'agendamentos')
     */
    function redirecionar_painel_correto($pagina_atual) {
        $base_url = get_base_url();
        if (!isset($_SESSION['usuario_tipo'])) {
            header("Location: {$base_url}/registro/login.php");
            exit();
        }

        $eh_admin = $_SESSION['usuario_tipo'] === 'admin';
        $esta_na_pagina_admin = $pagina_atual === 'admin';

        // Se é admin mas não está na página admin, redireciona para painel_admin
        if ($eh_admin && !$esta_na_pagina_admin) {
            header("Location: {$base_url}/painel_admin.php");
            exit();
        }
        
        // Se não é admin mas está na página admin, redireciona para painel_agendamentos
        if (!$eh_admin && $esta_na_pagina_admin) {
            header("Location: {$base_url}/painel_agendamentos.php");
            exit();
        }
    }/**
 * Define uma mensagem flash na sessão (mensagem curta + tipo: success|danger|warning|info)
 */
function set_flash(string $message, string $type = 'info') {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

/**
 * Renderiza (echo) a mensagem flash em HTML bootstrap e a remove da sessão.
 * Uso: chamar nos templates onde se deseja exibir mensagens.
 */
function render_flash() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['flash']) || !is_array($_SESSION['flash'])) return;

    $msg = $_SESSION['flash']['message'] ?? '';
    $type = $_SESSION['flash']['type'] ?? 'info';

    // Normalizar tipo
    $type = strtolower($type);
    if (!in_array($type, ['success','danger','warning','info'], true)) $type = 'info';

    // Output bootstrap alert
    echo '<div class="alert alert-' . htmlspecialchars($type, ENT_QUOTES, 'UTF-8') . ' alert-dismissible fade show" role="alert">';
    echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8');
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>';
    echo '</div>';

    unset($_SESSION['flash']);
}

/**
 * Verifica se o usuário atual é administrador. Se não for, bloqueia o acesso.
 * Para chamadas API (rotas em /api/) retorna JSON 403, caso contrário redireciona para login.
 */
function verificar_admin() {
    $base_url = get_base_url();
    if (!isset($_SESSION['usuario_tipo']) || $_SESSION['usuario_tipo'] !== 'admin') {
        // Detecta se é chamada a partir de /api/
        $isApi = isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/api/') !== false;
        if ($isApi) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Acesso negado: administrador requerido']);
            exit();
        } else {
            header("Location: {$base_url}/registro/login.php");
            exit();
        }
    }
}

/**
 * Envia notificação por email/registro de log.
 * Implementação mínima segura para evitar erros em ambientes sem dependências externas.
 * Retorna true em caso de sucesso (ou quando apenas logado) e false em caso de falha básica.
 *
 * Parâmetros opcionais em $opts:
 *  - use_mail: bool  -> forçar uso de mail() quando disponível
 */
function enviar_notificacao(string $to, string $subject, string $body, array $opts = []): bool {
    // Validação básica do email receptor
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        error_log("enviar_notificacao: email inválido: " . $to);
        return false;
    }

    // Log para auditoria/depuração em ambiente local
    $trimmedBody = mb_substr($body, 0, 1000);
    error_log("enviar_notificacao -> to={" . $to . "}, subject={" . $subject . "}, body_preview={" . $trimmedBody . "}");

    // Se quiser ativar envio real com mail() defina NOTIFICATIONS_USE_MAIL true ou passe ['use_mail' => true]
    $useMail = ($opts['use_mail'] ?? false) || (defined('NOTIFICATIONS_USE_MAIL') && NOTIFICATIONS_USE_MAIL === true);
    if ($useMail) {
        $headers = "From: no-reply@localhost\r\nContent-Type: text/plain; charset=UTF-8\r\n";
        $sent = @mail($to, $subject, $body, $headers);
        if ($sent) return true;
        error_log("enviar_notificacao: mail() falhou para: " . $to);
        // Mas não retornar false para não interromper fluxos críticos — ainda retornamos true
    }

    // Implementação mínima: consideramos o log como "sucesso" no ambiente sem SMTP
    return true;
}

?>