<?php
namespace App\Models;

class Agendamento {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function criar($idUsuario, $idRecurso, $motivo, $dataInicio, $dataFim, $tipoAgendamento = 'unico', $recorrenciaInfo = null) {
        $stmt = $this->pdo->prepare(
            "INSERT INTO agendamentos (id_usuario, id_recurso, motivo, data_hora_inicio, 
                                     data_hora_fim, tipo_agendamento, recorrencia_info) 
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        
        return $stmt->execute([
            $idUsuario, $idRecurso, $motivo, $dataInicio, $dataFim, 
            $tipoAgendamento, $recorrenciaInfo ? json_encode($recorrenciaInfo) : null
        ]);
    }
    
    public function atualizarStatus($id, $status) {
        $stmt = $this->pdo->prepare(
            "UPDATE agendamentos SET status = ? WHERE id = ?"
        );
        
        return $stmt->execute([$status, $id]);
    }
    
    public function deletar($id) {
        $stmt = $this->pdo->prepare("DELETE FROM agendamentos WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function buscarPorId($id) {
        $stmt = $this->pdo->prepare(
            "SELECT a.*, u.nome_completo as nome_usuario, r.nome as nome_recurso 
             FROM agendamentos a
             JOIN usuarios u ON a.id_usuario = u.id
             JOIN recursos r ON a.id_recurso = r.id
             WHERE a.id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function listarPorUsuario($idUsuario) {
        $stmt = $this->pdo->prepare(
            "SELECT a.*, r.nome as nome_recurso 
             FROM agendamentos a
             JOIN recursos r ON a.id_recurso = r.id
             WHERE a.id_usuario = ?
             ORDER BY a.data_hora_inicio DESC"
        );
        $stmt->execute([$idUsuario]);
        return $stmt->fetchAll();
    }
    
    public function listarPorRecurso($idRecurso, $dataInicio = null, $dataFim = null) {
        $sql = "SELECT a.*, u.nome_completo as nome_usuario 
                FROM agendamentos a
                JOIN usuarios u ON a.id_usuario = u.id
                WHERE a.id_recurso = ?";
        
        $params = [$idRecurso];
        
        if ($dataInicio && $dataFim) {
            $sql .= " AND ((a.data_hora_inicio BETWEEN ? AND ?) 
                          OR (a.data_hora_fim BETWEEN ? AND ?))";
            $params = array_merge($params, [$dataInicio, $dataFim, $dataInicio, $dataFim]);
        }
        
        $sql .= " ORDER BY a.data_hora_inicio";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function listarPendentes() {
        $stmt = $this->pdo->query(
            "SELECT a.*, u.nome_completo as nome_usuario, r.nome as nome_recurso 
             FROM agendamentos a
             JOIN usuarios u ON a.id_usuario = u.id
             JOIN recursos r ON a.id_recurso = r.id
             WHERE a.status = 'pendente'
             ORDER BY a.data_hora_inicio"
        );
        return $stmt->fetchAll();
    }
    
    public function registrarCheckIn($id) {
        $stmt = $this->pdo->prepare(
            "INSERT INTO check_in_out (id_agendamento, hora_check_in) 
             VALUES (?, NOW())"
        );
        return $stmt->execute([$id]);
    }
    
    public function registrarCheckOut($id, $observacoes = null) {
        $stmt = $this->pdo->prepare(
            "UPDATE check_in_out 
             SET hora_check_out = NOW(), observacoes = ?
             WHERE id_agendamento = ? AND hora_check_out IS NULL"
        );
        return $stmt->execute([$observacoes, $id]);
    }
}