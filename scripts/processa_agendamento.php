<?php
require_once __DIR__ . '/../includes/session_sec.php';
require_once __DIR__ . '/../includes/db_conexao.php';

// Verifica se usuário logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../registro/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../formulario_novo_agendamento.php');
    exit();
}

// CSRF
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    $_SESSION['mensagem'] = 'Token inválido. Tente novamente.';
    $_SESSION['mensagem_tipo'] = 'danger';
    header('Location: ../formulario_novo_agendamento.php');
    exit();
}

// Coleta e valida
$id_recurso = filter_input(INPUT_POST, 'resource', FILTER_VALIDATE_INT);
$motivo = trim(filter_input(INPUT_POST, 'purpose', FILTER_SANITIZE_STRING));
$tipo_agendamento = $_POST['tipo-agendamento'] ?? 'unico';

if (!$id_recurso || !$motivo) {
    $_SESSION['mensagem'] = 'Dados incompletos.';
    $_SESSION['mensagem_tipo'] = 'danger';
    header('Location: ../formulario_novo_agendamento.php');
    exit();
}

try {
    if ($tipo_agendamento === 'unico') {
        $start = $_POST['start_time'] ?? '';
        $end = $_POST['end_time'] ?? '';
        if (empty($start) || empty($end)) {
            throw new Exception('Datas/hora inválidas para agendamento único.');
        }
        $dtStart = new DateTime($start);
        $dtEnd = new DateTime($end);
        $data_inicio = $dtStart->format('Y-m-d H:i:s');
        $data_fim = $dtEnd->format('Y-m-d H:i:s');
        $recorrencia_info = null;
    } else {
        // recorrente: montar data inicial e final a partir da data de início e horários
        $recurrence_start = $_POST['recurrence_start'] ?? '';
        $recurrence_end = $_POST['recurrence_end'] ?? '';
        $recurrence_start_time = $_POST['recurrence_start_time'] ?? '';
        $recurrence_end_time = $_POST['recurrence_end_time'] ?? '';
        $weekday = isset($_POST['recurrence_weekday']) ? (int)$_POST['recurrence_weekday'] : null;

        if (empty($recurrence_start) || empty($recurrence_end) || empty($recurrence_start_time) || empty($recurrence_end_time)) {
            throw new Exception('Dados de recorrência incompletos.');
        }

        // Combine date and time for first occurrence
        $dtStart = new DateTime($recurrence_start . ' ' . $recurrence_start_time);
        $dtEnd = new DateTime($recurrence_start . ' ' . $recurrence_end_time);
        $data_inicio = $dtStart->format('Y-m-d H:i:s');
        $data_fim = $dtEnd->format('Y-m-d H:i:s');

        $recorrencia_info = json_encode([
            'weekday' => $weekday,
            'start_time' => $recurrence_start_time,
            'end_time' => $recurrence_end_time,
            'recurrence_start' => $recurrence_start,
            'recurrence_end' => $recurrence_end
        ]);
    }

    $stmt = $pdo->prepare("INSERT INTO agendamentos (id_usuario, id_recurso, motivo, data_hora_inicio, data_hora_fim, tipo_agendamento, recorrencia_info, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pendente')");
    $stmt->execute([$_SESSION['usuario_id'], $id_recurso, $motivo, $data_inicio, $data_fim, $tipo_agendamento, $recorrencia_info]);

    $_SESSION['mensagem'] = 'Agendamento solicitado com sucesso. Aguarde aprovação.';
    $_SESSION['mensagem_tipo'] = 'success';
    header('Location: ../painel_agendamentos.php');
    exit();

} catch (Exception $e) {
    error_log('[processa_agendamento] ' . $e->getMessage());
    $_SESSION['mensagem'] = 'Erro ao processar agendamento. Verifique os dados e tente novamente.';
    $_SESSION['mensagem_tipo'] = 'danger';
    header('Location: ../formulario_novo_agendamento.php');
    exit();
}
?>