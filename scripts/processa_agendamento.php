<?php
// /scripts/processa_agendamento.php

require_once __DIR__ . '/../includes/session_sec.php';
require_once __DIR__ . '/../includes/db_conexao.php';
require_once __DIR__ . '/../includes/bib.php'; // Corretamente incluído

// ======================================================
// 🔹 FUNÇÃO DE APOIO: Verificar conflito de agendamento
// ======================================================

/**
 * Função para verificar conflitos de agendamento para um recurso.
 * Verifica se já existe um agendamento aprovado ou pendente para o mesmo recurso
 * no intervalo de tempo especificado.
 *
 * @param PDO $pdo Conexão com o banco de dados.
 * @param int $id_recurso ID do recurso a ser verificado.
 * @param string $inicio_str Data e hora de início no formato 'Y-m-d H:i:s'.
 * @param string $fim_str Data e hora de fim no formato 'Y-m-d H:i:s'.
 * @return bool Retorna true se houver conflito, false caso contrário.
 */
function verificarConflito(PDO $pdo, int $id_recurso, string $inicio_str, string $fim_str): bool {
    // Esta lógica está correta para encontrar sobreposições (overlaps)
    $sql = "SELECT COUNT(id) 
            FROM agendamentos 
            WHERE id_recurso = ? 
              AND status IN ('pendente', 'aprovado')
              AND CAST(? AS DATETIME) < data_hora_fim 
              AND CAST(? AS DATETIME) > data_hora_inicio";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_recurso, $inicio_str, $fim_str]);
    
    return $stmt->fetchColumn() > 0;
}

// ======================================================
// 🔹 VERIFICAÇÕES DE SEGURANÇA
// ======================================================
// ... (O código de segurança está correto, sem mudanças) ...
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../formulario_novo_agendamento.php');
    exit;
}

if (!isset($_SESSION['id_usuario'])) {
    set_flash('Você precisa estar logado para fazer um agendamento.', 'error');
    header('Location: ../login.php');
    exit;
}

if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    set_flash('Erro de validação de segurança. Tente novamente.', 'error');
    header('Location: ../formulario_novo_agendamento.php');
    exit;
}
// ======================================================
// 🔹 PROCESSAMENTO PRINCIPAL
// ======================================================

try {
    $id_recurso = filter_input(INPUT_POST, 'recurso_id', FILTER_VALIDATE_INT);
    $turma_id = filter_input(INPUT_POST, 'turma_id', FILTER_VALIDATE_INT);
    $disciplina_id = filter_input(INPUT_POST, 'disciplina_id', FILTER_VALIDATE_INT);
    $motivo = trim(filter_input(INPUT_POST, 'motivo', FILTER_UNSAFE_RAW));
    $tipo_agendamento = filter_input(INPUT_POST, 'tipo_agendamento', FILTER_SANITIZE_SPECIAL_CHARS);

    if (!$id_recurso || !$turma_id || !$disciplina_id || empty($motivo)) {
        throw new Exception('Dados incompletos. Preencha todos os campos obrigatórios.');
    }

    if (!in_array($tipo_agendamento, ['unico', 'fixo'])) {
        throw new Exception('Tipo de agendamento inválido.');
    }

    $pdo->beginTransaction();

    // ======================================================
    // 🔸 AGENDAMENTO ÚNICO
    // ======================================================
    if ($tipo_agendamento === 'unico') {
        $data_hora_inicio_str = filter_input(INPUT_POST, 'data_hora_inicio', FILTER_UNSAFE_RAW);
        $data_hora_fim_str = filter_input(INPUT_POST, 'data_hora_fim', FILTER_UNSAFE_RAW);

        if (empty($data_hora_inicio_str) || empty($data_hora_fim_str)) {
            throw new Exception('Para agendamento único, as datas de início e fim são obrigatórias.');
        }

        // Formata para o padrão do MySQL (Y-m-d H:i:s)
        $inicio_dt = new DateTime($data_hora_inicio_str);
        $fim_dt = new DateTime($data_hora_fim_str);
        
        $inicio_sql = $inicio_dt->format('Y-m-d H:i:s');
        $fim_sql = $fim_dt->format('Y-m-d H:i:s');


        if ($fim_dt <= $inicio_dt) {
            throw new Exception('A data/hora de fim deve ser posterior à data/hora de início.');
        }

        if (verificarConflito($pdo, $id_recurso, $inicio_sql, $fim_sql)) {
            throw new Exception('O recurso selecionado já está agendado neste horário. Por favor, escolha outro horário.');
        }

        $stmt = $pdo->prepare("
            INSERT INTO agendamentos 
                (id_usuario, id_recurso, id_turma, id_disciplina, motivo, data_hora_inicio, data_hora_fim, status, tipo_agendamento)
            VALUES 
                (?, ?, ?, ?, ?, ?, ?, 'pendente', 'unico')
        ");
        $stmt->execute([
            $_SESSION['id_usuario'], $id_recurso, $turma_id, $disciplina_id, 
            $motivo, $inicio_sql, $fim_sql
        ]);
    }

    // ======================================================
    // 🔸 AGENDAMENTO FIXO (RECORRENTE)
    // ======================================================
    elseif ($tipo_agendamento === 'fixo') {
        $dia_semana = filter_input(INPUT_POST, 'dia_semana_recorrencia', FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 7] // Supondo 1=Segunda... 7=Domingo
        ]);
        $hora_inicio = filter_input(INPUT_POST, 'hora_inicio_recorrencia', FILTER_UNSAFE_RAW);
        $hora_fim = filter_input(INPUT_POST, 'hora_fim_recorrencia', FILTER_UNSAFE_RAW);
        $data_inicio_rec = filter_input(INPUT_POST, 'data_inicio_recorrencia', FILTER_UNSAFE_RAW);
        $data_fim_rec = filter_input(INPUT_POST, 'data_fim_recorrencia', FILTER_UNSAFE_RAW);

        if (!$dia_semana || empty($hora_inicio) || empty($hora_fim) || empty($data_inicio_rec) || empty($data_fim_rec)) {
            throw new Exception('Para agendamento fixo, todos os campos de recorrência são obrigatórios.');
        }

        if (new DateTime($data_fim_rec) < new DateTime($data_inicio_rec) || $hora_fim <= $hora_inicio) {
            throw new Exception('Datas ou horários de recorrência inválidos.');
        }

        $data_atual = new DateTime($data_inicio_rec);
        $data_fim_obj = new DateTime($data_fim_rec);
        $agendamentos_criados = 0;

        $grupo_id = uniqid('rec_');

        $stmt = $pdo->prepare("
            INSERT INTO agendamentos 
                (id_usuario, id_recurso, id_turma, id_disciplina, motivo, data_hora_inicio, data_hora_fim, status, tipo_agendamento, grupo_recorrencia_id)
            VALUES 
                (?, ?, ?, ?, ?, ?, ?, 'pendente', 'fixo', ?)
        ");

        while ($data_atual <= $data_fim_obj) {
            // format('N') retorna 1 para Segunda ... 7 para Domingo
            if ($data_atual->format('N') == $dia_semana) { 
                $inicio_agendamento = $data_atual->format('Y-m-d') . ' ' . $hora_inicio;
                $fim_agendamento = $data_atual->format('Y-m-d') . ' ' . $hora_fim;

                if (verificarConflito($pdo, $id_recurso, $inicio_agendamento, $fim_agendamento)) {
                    // Importante: Se der conflito em UMA data, cancela TUDO.
                    throw new Exception('Conflito encontrado para o dia ' . $data_atual->format('d/m/Y') . '. A operação foi cancelada.');
                }

                $stmt->execute([
                    $_SESSION['id_usuario'], $id_recurso, $turma_id, $disciplina_id, 
                    $motivo, $inicio_agendamento, $fim_agendamento, $grupo_id
                ]);
                $agendamentos_criados++;
            }
            $data_atual->modify('+1 day');
        }

        if ($agendamentos_criados === 0) {
            throw new Exception('Nenhuma data correspondente ao dia da semana foi encontrada no intervalo selecionado.');
        }
    }

    // ======================================================
    // 🔹 FINALIZAÇÃO
    // ======================================================
    $pdo->commit();
    set_flash('Solicitação de agendamento enviada com sucesso! Aguarde a aprovação.', 'success');
    
    // ⭐️ MUDANÇA: Redireciona para a página correta
    header('Location: ../painel_agendamentos.php'); 
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    set_flash('Erro ao processar agendamento: ' . $e->getMessage(), 'error');
    header('Location: ../formulario_novo_agendamento.php');
    exit;
}

