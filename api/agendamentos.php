<?php
require_once '../includes/bib.php';
require_once '../includes/db_conexao.php';
require_once '../vendor/autoload.php';

use App\Models\Agendamento;

// Verifica se está logado e é admin
verificar_admin();

header('Content-Type: application/json');

$agendamentoModel = new Agendamento($pdo);

try {
    $acao = $_GET['acao'] ?? '';
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    
    if (!$id) {
        throw new Exception('ID inválido');
    }
    
    switch ($acao) {
        case 'aprovar':
            $resultado = $agendamentoModel->atualizarStatus($id, 'aprovado');
            if ($resultado) {
                // Busca o agendamento para enviar notificação
                $agendamento = $agendamentoModel->buscarPorId($id);
                if ($agendamento) {
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
            $resultado = $agendamentoModel->atualizarStatus($id, 'recusado');
            if ($resultado) {
                // Busca o agendamento para enviar notificação
                $agendamento = $agendamentoModel->buscarPorId($id);
                if ($agendamento) {
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
            $resultado = $agendamentoModel->deletar($id);
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