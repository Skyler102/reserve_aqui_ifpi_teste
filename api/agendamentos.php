<?php
require_once '../includes/bib.php';
require_once '../includes/db_conexao.php';

// Verifica se está logado e é admin
verificar_admin();

header('Content-Type: application/json');

try {
    $acao = $_GET['acao'] ?? '';
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    
    if (!$id) {
        // Se não há ID, pode ser uma requisição para listar todos (com paginação) ou eventos do calendário
        if ($acao === 'calendar_events') {
            // Retorna todos os agendamentos para o calendário (aprovados, pendentes, recusados, finalizados)
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
                else if ($ag['status'] === 'finalizado') $color = '#6c757d'; // Cinza

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
        }
        // Se não há ID e não é para eventos de calendário, pode ser uma requisição para listar todos com paginação
        if ($acao === '' && isset($_GET['list'])) {
            $reservas_por_pagina = 10;
            $pagina_atual = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?? 1;
            if ($pagina_atual < 1) $pagina_atual = 1;

            $stmt_total = $pdo->query("SELECT COUNT(id) FROM agendamentos");
            $total_reservas = $stmt_total->fetchColumn();
            $total_paginas = ceil($total_reservas / $reservas_por_pagina);
            $total_paginas = max($total_paginas, 1);

            if ($pagina_atual > $total_paginas) $pagina_atual = $total_paginas;

            $offset = ($pagina_atual - 1) * $reservas_por_pagina;

            $stmt_reservas = $pdo->prepare(
                "SELECT a.id, a.motivo, a.data_hora_inicio, a.data_hora_fim, a.status,
                        u.nome_completo as usuario_nome, r.nome as recurso_nome
                 FROM agendamentos a
                 JOIN usuarios u ON a.id_usuario = u.id
                 JOIN recursos r ON a.id_recurso = r.id
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
        throw new Exception('ID inválido para a ação solicitada.');
    }
    
    switch ($acao) {
        case 'aprovar':
            $stmt = $pdo->prepare("UPDATE agendamentos SET status = 'aprovado' WHERE id = ?");
            $resultado = $stmt->execute([$id]);
            if ($resultado) {
                // Busca o agendamento para enviar notificação
                $stmt_ag = $pdo->prepare(
                    "SELECT a.*, u.nome_completo as nome_usuario, u.email, r.nome as nome_recurso 
                     FROM agendamentos a
                     JOIN usuarios u ON a.id_usuario = u.id
                     JOIN recursos r ON a.id_recurso = r.id
                     WHERE a.id = ?"
                );
                $stmt_ag->execute([$id]);
                $agendamento = $stmt_ag->fetch();
                if ($agendamento && isset($agendamento['email'])) {
                    $mensagem = <<<HTML
                    <h3>Agendamento Aprovado</h3>
                    <p>Olá {$agendamento['nome_usuario']},</p>
                    <p>Seu agendamento para {$agendamento['nome_recurso']} foi aprovado.</p>
                    <p><strong>Data/Hora Início:</strong> {$agendamento['data_hora_inicio']}</p>
                    <p><strong>Data/Hora Fim:</strong> {$agendamento['data_hora_fim']}</p>
                    <p><strong>Motivo:</strong> {$agendamento['motivo']}</p>
                    <p>Você pode acessar os detalhes do agendamento em seu painel.</p>
                    HTML;
                    
                    enviar_notificacao($agendamento['email'], 'Agendamento Aprovado', $mensagem);
                }
            }
            break;
            
        case 'recusar':
            $stmt = $pdo->prepare("UPDATE agendamentos SET status = 'recusado' WHERE id = ?");
            $resultado = $stmt->execute([$id]);
            if ($resultado) {
                // Busca o agendamento para enviar notificação
                $stmt_ag = $pdo->prepare(
                    "SELECT a.*, u.nome_completo as nome_usuario, u.email, r.nome as nome_recurso 
                     FROM agendamentos a
                     JOIN usuarios u ON a.id_usuario = u.id
                     JOIN recursos r ON a.id_recurso = r.id
                     WHERE a.id = ?"
                );
                $stmt_ag->execute([$id]);
                $agendamento = $stmt_ag->fetch();
                if ($agendamento && isset($agendamento['email'])) {
                    $mensagem = <<<HTML
                    <h3>Agendamento Recusado</h3>
                    <p>Olá {$agendamento['nome_usuario']},</p>
                    <p>Infelizmente seu agendamento para {$agendamento['nome_recurso']} foi recusado.</p>
                    <p><strong>Data/Hora Início:</strong> {$agendamento['data_hora_inicio']}</p>
                    <p><strong>Data/Hora Fim:</strong> {$agendamento['data_hora_fim']}</p>
                    <p><strong>Motivo:</strong> {$agendamento['motivo']}</p>
                    <p>Por favor, tente agendar em outro horário ou entre em contato com a administração para mais informações.</p>
                    HTML;
                    
                    enviar_notificacao($agendamento['email'], 'Agendamento Recusado', $mensagem);
                }
            }
            break;

        case 'deletar':
            $stmt = $pdo->prepare("DELETE FROM agendamentos WHERE id = ?");
            $resultado = $stmt->execute([$id]);
            if (!$resultado) throw new Exception('Falha ao deletar agendamento.');
            break;
            
        default:
            throw new Exception('Ação inválida');
    }
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}