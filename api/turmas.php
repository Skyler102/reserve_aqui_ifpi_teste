<?php
// api/turmas.php

// Includes essenciais para conexão com o banco e funções de segurança
require_once '../includes/db_conexao.php';
require_once '../includes/bib.php';

// Define o tipo de resposta como JSON para todas as saídas
header('Content-Type: application/json');

try {
    // A primeira e mais importante etapa: garantir que apenas administradores acessem esta API.
    verificar_admin();

    // --- ROTA: Requisições via GET (Listar ou Deletar) ---
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        
        // Ação: LISTAR todas as turmas
        if (isset($_GET['list'])) {
            $stmt = $pdo->query("SELECT id, nome FROM turmas ORDER BY nome");
            $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'items' => $itens]);
            exit; // Finaliza o script, pois a resposta já foi enviada
        }

        // Ação: DELETAR uma turma
        if (isset($_GET['acao']) && $_GET['acao'] === 'deletar') {
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
            if (!$id) {
                throw new Exception('ID da turma para deletar é inválido.');
            }
            $stmt = $pdo->prepare("DELETE FROM turmas WHERE id = ?");
            if (!$stmt->execute([$id])) {
                 throw new Exception('Falha ao deletar a turma.');
            }
            echo json_encode(['success' => true, 'message' => 'Turma deletada com sucesso.']);
            exit;
        }

    // --- ROTA: Requisições via POST (Criar ou Atualizar) ---
    } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        
        $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING);
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

        if (empty($nome)) {
            throw new Exception('O nome da turma é obrigatório.');
        }

        // Se um ID foi enviado, a ação é ATUALIZAR
        if ($id) {
            $stmt = $pdo->prepare("UPDATE turmas SET nome = :nome WHERE id = :id");
            $resultado = $stmt->execute([':nome' => $nome, ':id' => $id]);
            $mensagem = 'Turma atualizada com sucesso.';

        // Se não há ID, a ação é CRIAR
        } else {
            $stmt = $pdo->prepare("INSERT INTO turmas (nome) VALUES (?)");
            $resultado = $stmt->execute([$nome]);
            $mensagem = 'Turma criada com sucesso.';
        }
        
        if (!$resultado) {
            throw new Exception('A operação no banco de dados falhou.');
        }
        
        echo json_encode(['success' => true, 'message' => $mensagem]);
        exit;

    } else {
        // Se o método não for GET ou POST, retorna um erro.
        throw new Exception('Método HTTP não suportado.');
    }

} catch (Exception $e) {
    // Bloco de captura de erros: Se qualquer 'throw new Exception' for acionado,
    // o código vem para cá e envia uma resposta de erro padronizada.
    http_response_code(400); // Código de erro "Bad Request"
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}