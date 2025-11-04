<?php
// /includes/bib.php

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
                <img src="{$base_url}/assets/logos/logo_resolva_ifpi.png" alt="Logo Resolva Aqui IFPI" class="logo-resolva">
                <div class="texto-logo">
                    <p class="marca-subtitulo m-0">Resolva Aqui</p>
                    <h1 class="marca-titulo">IFPI</h1>
                    <p class="marca-descricao m-0">RESOLVA SEUS PROBLEMAS AQUI</p>
                </div>
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
        // Se a variável de sessão 'id_usuario' não existir, significa que não há ninguém logado.
        $base_url = get_base_url();
        if (!isset($_SESSION['id_usuario'])) {
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
 * - use_mail: bool  -> forçar uso de mail() quando disponível
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

// ======================================================
// ⭐️ FUNÇÕES ADICIONADAS (ORDENAÇÃO, PAGINAÇÃO, TRADUÇÃO)
// ======================================================

/**
 * Constrói uma query string preservando os parâmetros GET atuais.
 * Usado para criar links de paginação e ordenação.
 * @param array $novos_params Parâmetros para adicionar ou sobrescrever (ex: ['page' => 2])
 * @return string A query string completa (ex: "?sort=data&page=2")
 */
function construir_query_string(array $novos_params): string {
    // Pega todos os parâmetros GET atuais
    $params = $_GET;
    
    // Sobrescreve ou adiciona os novos parâmetros
    $params = array_merge($params, $novos_params);
    
    // Constrói e retorna a query string
    return '?' . http_build_query($params);
}

/**
 * Gera o HTML para um cabeçalho de tabela (<th>) ordenável.
 * @param string $label O texto do cabeçalho (ex: "Recurso")
 * @param string $coluna_nome O nome da coluna na query (ex: "recurso")
 * @param string $sort_col_atual A coluna de ordenação atual
 * @param string $sort_order_atual A direção da ordenação atual (ASC ou DESC)
 */
function th_sortable(string $label, string $coluna_nome, string $sort_col_atual, string $sort_order_atual) {
    $icone = '';
    $proxima_order = 'ASC';

    if ($coluna_nome === $sort_col_atual) {
        if ($sort_order_atual === 'ASC') {
            $icone = ' <i class="fas fa-sort-up"></i>';
            $proxima_order = 'DESC';
        } else {
            $icone = ' <i class="fas fa-sort-down"></i>';
            $proxima_order = 'ASC';
        }
    } else {
        $icone = ' <i class="fas fa-sort text-muted"></i>';
    }

    $link = construir_query_string(['sort' => $coluna_nome, 'order' => $proxima_order]);
    echo '<th><a href="' . $link . '" class="text-decoration-none text-dark">' . $label . $icone . '</a></th>';
}

/**
 * Gera o HTML para o seletor de "Itens por Página".
 * @param int $per_page_atual O número de itens por página atual
 * @param array $opcoes As opções para o select (ex: [10, 25, 50])
 * @param string $page_param_name O nome do parâmetro da página (page_todos, page_meus, page_reservas)
 */
function seletor_itens_por_pagina(int $per_page_atual, array $opcoes = [5, 10, 25, 50], string $page_param_name = 'per_page') {
    echo '<form method="GET" class="d-flex align-items-center mb-0" style="max-width: 200px;">';
    
    // Preserva outros parâmetros GET
    foreach ($_GET as $key => $value) {
        // Não preserva o próprio 'per_page' ou a página que estamos controlando
        if ($key !== $page_param_name && $key !== 'page_todos' && $key !== 'page_meus' && $key !== 'page_reservas') {
            echo '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($value) . '">';
        }
    }
    
    // Preserva os outros contadores de página
    if ($page_param_name != 'per_page' && isset($_GET['page_todos'])) { // hack para o 'per_page' principal
         echo '<input type="hidden" name="page_todos" value="' . htmlspecialchars($_GET['page_todos']) . '">';
    }
    if ($page_param_name != 'per_page_meus' && isset($_GET['page_meus'])) {
         echo '<input type="hidden" name="page_meus" value="' . htmlspecialchars($_GET['page_meus']) . '">';
    }
    if ($page_param_name != 'per_page' && isset($_GET['page_reservas'])) { // hack para o 'per_page' principal
         echo '<input type="hidden" name="page_reservas" value="' . htmlspecialchars($_GET['page_reservas']) . '">';
    }


    echo '<label for="' . $page_param_name . '" class="form-label me-2 mb-0" style="white-space: nowrap;">Ver:</label>';
    echo '<select name="' . $page_param_name . '" id="' . $page_param_name . '" class="form-select form-select-sm" onchange="this.form.submit()">';
    
    foreach ($opcoes as $opcao) {
        $selected = ($opcao == $per_page_atual) ? 'selected' : '';
        echo '<option value="' . $opcao . '" ' . $selected . '>' . $opcao . ' por página</option>';
    }
    
    echo '</select>';
    echo '</form>';
}

/**
 * Gera o HTML para a navegação de paginação.
 * @param int $pagina_atual A página atual
 * @param int $total_paginas O total de páginas
 * @param string $param_nome O nome do parâmetro GET para a página (ex: "page_todos")
 */
function gerar_paginacao(int $pagina_atual, int $total_paginas, string $param_nome) {
    if ($total_paginas <= 1) return;

    echo '<nav class="paginacao mt-3 mb-3 d-flex justify-content-center align-items-center">';
    
    // Link "Anterior"
    $link_anterior = construir_query_string([$param_nome => max(1, $pagina_atual - 1)]);
    $disabled_anterior = ($pagina_atual <= 1) ? 'disabled' : '';
    echo '<a href="' . $link_anterior . '" class="page-link-reservas ' . $disabled_anterior . '"><i class="fas fa-chevron-left"></i></a>';
    
    // Texto da Página
    echo '<span class="mx-3">Página ' . $pagina_atual . ' de ' . $total_paginas . '</span>';
    
    // Link "Próxima"
    $link_proxima = construir_query_string([$param_nome => min($total_paginas, $pagina_atual + 1)]);
    $disabled_proxima = ($pagina_atual >= $total_paginas) ? 'disabled' : '';
    echo '<a href="' . $link_proxima . '" class="page-link-reservas ' . $disabled_proxima . '"><i class="fas fa-chevron-right"></i></a>';
    
    echo '</nav>';
}

/**
 * ⭐️ FUNÇÃO DE TRADUÇÃO ATUALIZADA (COM FALLBACK)
 * Formata uma data/hora (string ou DateTime) para Português do Brasil.
 * Inclui um fallback caso a extensão Intl não esteja habilitada.
 * @param string|DateTime $data_hora O objeto DateTime ou string da data.
 * @param string $formato O formato desejado (ex: 'EEEE, d/M/y' ou 'HH:mm')
 * @return string A data formatada.
 */
function formatar_data_br($data_hora, string $formato): string {
    if (is_string($data_hora)) {
        try {
            $data_hora = new DateTime($data_hora);
        } catch (Exception $e) {
            return 'Data Inválida';
        }
    }

    // 1. Tenta usar o formatador Intl (preferencial)
    if (class_exists('IntlDateFormatter')) {
        $formatter = new IntlDateFormatter(
            'pt_BR',
            IntlDateFormatter::FULL,
            IntlDateFormatter::FULL,
            'America/Sao_Paulo', // Ajuste para seu fuso horário se necessário
            IntlDateFormatter::GREGORIAN,
            $formato
        );
        // Corrige bug do Intl com 'EEEE' (dia da semana)
        if ($formato === 'EEEE') {
             return ucfirst($formatter->format($data_hora));
        }
        return $formatter->format($data_hora);
    }

    // 2. --- Fallback Simples (se Intl não estiver disponível) ---
    // Mapeamento manual
    $dias_semana = [
        'Monday'    => 'Segunda-feira',
        'Tuesday'   => 'Terça-feira',
        'Wednesday' => 'Quarta-feira',
        'Thursday'  => 'Quinta-feira',
        'Friday'    => 'Sexta-feira',
        'Saturday'  => 'Sábado',
        'Sunday'    => 'Domingo'
    ];
    
    $meses_abrev = [
        'Jan' => 'Jan', 'Feb' => 'Fev', 'Mar' => 'Mar', 'Apr' => 'Abr',
        'May' => 'Mai', 'Jun' => 'Jun', 'Jul' => 'Jul', 'Aug' => 'Ago',
        'Sep' => 'Set', 'Oct' => 'Out', 'Nov' => 'Nov', 'Dec' => 'Dez'
    ];

    // Converte o formato Intl para o formato date() do PHP
    $formato_date = $formato;
    $formato_date = str_replace('EEEE', 'l', $formato_date);     // Dia da semana completo (ex: Monday)
    $formato_date = str_replace('d/MM/Y', 'd/m/Y', $formato_date); // Data (ex: 31/12/2025)
    $formato_date = str_replace('d/MM/y', 'd/m/y', $formato_date); // Data (ex: 31/12/25)
    $formato_date = str_replace('d/M/y', 'd/m/y', $formato_date);   // Data (ex: 31/12/25)
    $formato_date = str_replace('HH:mm', 'H:i', $formato_date);    // Hora (ex: 14:30)
    
    $data_formatada = $data_hora->format($formato_date);

    // Traduz manualmente os dias da semana
    $data_formatada = str_replace(array_keys($dias_semana), array_values($dias_semana), $data_formatada);
    
    // Traduz manualmente os meses (se o formato pedir)
    // (Esta parte é mais simples, pode não pegar todos os casos de formato Intl)
    $data_formatada = str_replace(array_keys($meses_abrev), array_values($meses_abrev), $data_formatada);

    return $data_formatada;
}
?>

