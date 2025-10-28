<?php
namespace App\Models;

class Recurso {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function criar($nome, $tipo, $localizacao, $capacidade = null, $descricao = null) {
        $stmt = $this->pdo->prepare(
            "INSERT INTO recursos (nome, tipo_recurso, localizacao, capacidade, descricao) 
             VALUES (?, ?, ?, ?, ?)"
        );
        
        return $stmt->execute([$nome, $tipo, $localizacao, $capacidade, $descricao]);
    }
    
    public function atualizar($id, $nome, $tipo, $localizacao, $capacidade, $descricao, $ativo) {
        $stmt = $this->pdo->prepare(
            "UPDATE recursos 
             SET nome = ?, tipo_recurso = ?, localizacao = ?, 
                 capacidade = ?, descricao = ?, ativo = ?
             WHERE id = ?"
        );
        
        return $stmt->execute([$nome, $tipo, $localizacao, $capacidade, $descricao, $ativo, $id]);
    }
    
    public function buscarPorId($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM recursos WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function listarTodos($apenasAtivos = true) {
        $sql = "SELECT * FROM recursos";
        if ($apenasAtivos) {
            $sql .= " WHERE ativo = TRUE";
        }
        $sql .= " ORDER BY nome";
        
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }
    
    public function listarPorTipo($tipo, $apenasAtivos = true) {
        $sql = "SELECT * FROM recursos WHERE tipo_recurso = ?";
        if ($apenasAtivos) {
            $sql .= " AND ativo = TRUE";
        }
        $sql .= " ORDER BY nome";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$tipo]);
        return $stmt->fetchAll();
    }
}