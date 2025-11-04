<?php
// /api/agendamentos.php
// API para o PAINEL ADMIN
// MODIFICADO para aceitar 'grupo_id' nas ações de aprovar, recusar e deletar.

require_once '../includes/session_sec.php'; // ⭐️ Corrigido o caminho
require_once '../includes/db_conexao.php';
require_once '../includes/bib.php';

// Verifica se está logado e é admin
verificar_admin();

header('Content-Type: application/json');

try {
    $acao = $_GET['acao'] ?? null;
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $grupo_id = filter_input(INPUT_GET, 'grupo_id', FILTER_SANITIZE_SPECIAL_CHARS); // ⭐️ NOVO: Pega o grupo_id
    
    // Se for 'list' ou 'calendar_events', não precisa de ID
    if ($acao === 'calendar_events') {
        // ... (seu código de calendar_events, parece correto) ...
        $stmt = $pdo->query("
            SELECT 
                a.id, a.motivo, a.data_hora_inicio, a.data_hora_fim, a.status,
                u.nome_completo as usuario_nome, r.nome as recurso_nome
            FROM agendamentos a
            JOIN usuarios u ON a.id_usuario = u.id
            JOIN recursos r ON a.id_recurso = r.id
            ORDER BY a.data_hora_inicio
        ");
        $events = [];
        while ($ag = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $color = '';
            if ($ag['status'] === 'aprovado') $color = '#28a745'; // Verde
            else if ($ag['status'] === 'pendente') $color = '#ffc107'; // Amarelo
            else if ($ag['status'] === 'recusado') $color = '#dc3545'; // Vermelho
            else if ($ag['status'] === 'cancelado') $color = '#6c757d'; // Cinza (Adicionado)

            $events[] = [
                'id' => $ag['id'],
                'title' => $ag['recurso_nome'] . ' (' . ucfirst($ag['status']) . ')',
                'start' => $ag['data_hora_inicio'],
                'end' => $ag['data_hora_fim'],
                'color' => $color,
                'extendedProps' => ['motivo' => $ag['motivo'], 'usuario' => $ag['usuario_nome']]
            ];
        }
        echo json_encode($events);
        exit();

    } elseif (isset($_GET['list'])) {
        // ⭐️ ATENÇÃO: Esta listagem de API não suporta o accordion.
        // O `painel_admin.php` que gerei na última rodada *não usa* esta API para listar.
        // Ele renderiza a lista via PHP.
        // Estou mantendo seu código aqui, mas ele não vai gerar a lista agrupada.
        // O ideal seria remover esta lógica 'list' e o JS 'carregarReservas' do painel admin.
        
        $reservas_por_pagina = 10;
        $pagina_atual = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?? 1;
        if ($pagina_atual < 1) $pagina_atual = 1;

        $stmt_total = $pdo->query("SELECT COUNT(id) FROM agendamentos");
        $total_reservas = $stmt_total->fetchColumn();
        $total_paginas = ceil($total_reservas / $reservas_por_pagina);
        $total_paginas = max($total_paginas, 1);

        if ($pagina_atual > $total_paginas) $pagina_atual = $total_paginas;

        $offset = ($pagina_atual - 1) * $reservas_por_pagina;

        // ⭐️ Query ATUALIZADA para incluir os campos que o JS (do seu admin antigo) espera
        $stmt_reservas = $pdo->prepare(
            "SELECT a.*,
                    u.nome_completo as usuario_nome, 
                    r.nome as recurso_nome,
                    d.nome as disciplina_nome,
                    t.nome as turma_nome
             FROM agendamentos a
             JOIN usuarios u ON a.id_usuario = u.id
             JOIN recursos r ON a.id_recurso = r.id
             LEFT JOIN disciplinas d ON a.id_disciplina = d.id
             LEFT JOIN turmas t ON a.id_turma = t.id
             ORDER BY a.data_hora_inicio DESC
             LIMIT :limit OFFSET :offset"
        );
        $stmt_reservas->bindValue(':limit', $reservas_por_pagina, PDO::PARAM_INT);
        $stmt_reservas->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt_reservas->execute();
        $reservas = $stmt_reservas->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'items' => $reservas,
            'total_paginas' => $total_paginas,
            'pagina_atual' => $pagina_atual
        ]);
        exit();
    }
    
    // Se não for 'list' ou 'calendar', precisa de uma ação e ID/Grupo_ID
    if (!$acao || (!$id && !$grupo_id)) {
        throw new Exception('Ação ou ID/Grupo inválido para a operação.');
    }
    
    // ⭐️ LÓGICA DE AÇÃO MODIFICADA
    $sql_where = "";
    $params = [];

    if ($grupo_id) {
        $sql_where = "grupo_recorrencia_id = ?";
        $params[] = $grupo_id;
    } else {
        $sql_where = "id = ?";
        $params[] = $id;
    }

    switch ($acao) {
        case 'aprovar':
            $stmt = $pdo->prepare("UPDATE agendamentos SET status = 'aprovado' WHERE $sql_where AND status = 'pendente'");
            $resultado = $stmt->execute($params);
            // (Seu código de enviar e-mail de aprovação iria aqui)
            break;
            
        case 'recusar':
            $stmt = $pdo->prepare("UPDATE agendamentos SET status = 'recusado' WHERE $sql_where AND status = 'pendente'");
            $resultado = $stmt->execute($params);
            // (Seu código de enviar e-mail de recusa iria aqui)
            break;

        case 'deletar':
            // Admin pode deletar em qualquer status
            $stmt = $pdo->prepare("DELETE FROM agendamentos WHERE $sql_where");
            $resultado = $stmt->execute($params);
            if (!$resultado) throw new Exception('Falha ao deletar agendamento.');
            break;
            
        default:
            throw new Exception('Ação inválida');
    }
    
    echo json_encode(['success' => true, 'affected_rows' => $stmt->rowCount()]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
