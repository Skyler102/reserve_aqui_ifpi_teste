<?php
namespace App\Models;

class Usuario {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function autenticar($email, $senha) {
        $stmt = $this->pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();
        
        if ($usuario && password_verify($senha, $usuario['senha_hash'])) {
            unset($usuario['senha_hash']); // Remove a senha do array
            return $usuario;
        }
        
        return false;
    }
    
    public function criar($nome, $matricula, $email, $senha, $tipo = 'professor') {
        $senha_hash = password_hash($senha, PASSWORD_ARGON2ID);
        
        $stmt = $this->pdo->prepare(
            "INSERT INTO usuarios (nome_completo, matricula, email, senha_hash, tipo_usuario) 
             VALUES (?, ?, ?, ?, ?)"
        );
        
        return $stmt->execute([$nome, $matricula, $email, $senha_hash, $tipo]);
    }
    
    public function buscarPorId($id) {
        $stmt = $this->pdo->prepare("SELECT id, nome_completo, matricula, email, tipo_usuario FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function listarTodos() {
        $stmt = $this->pdo->query("SELECT id, nome_completo, matricula, email, tipo_usuario FROM usuarios");
        return $stmt->fetchAll();
    }
}