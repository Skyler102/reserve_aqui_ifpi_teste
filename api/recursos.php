<?php
// Linhas para forçar a exibição de erros - MUITO ÚTIL PARA DEPURAR
// Se a página continuar em branco ou dando erro, descomente estas 3 linhas.
ini_set('display_errors', 0); // Desativar em produção
error_reporting(0);

require_once '../includes/db_conexao.php'; // Garante que $pdo existe
require_once '../includes/bib.php';       // Para a função de verificação de admin

header('Content-Type: application/json');

try {
    // A verificação de admin deve vir antes de qualquer outra lógica
    verificar_admin(); 

    $resultado = false;

    // --- ROTA: Requisições via POST (Criar ou Atualizar) ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nome = filter_input(INPUT_POST, 'nome', FILTER_UNSAFE_RAW);
        $tipo = filter_input(INPUT_POST, 'tipo', FILTER_UNSAFE_RAW);
        $localizacao = filter_input(INPUT_POST, 'localizacao', FILTER_UNSAFE_RAW);
        $capacidade = filter_input(INPUT_POST, 'capacidade', FILTER_VALIDATE_INT, ['options' => ['default' => null]]);
        $descricao = filter_input(INPUT_POST, 'descricao', FILTER_UNSAFE_RAW);
        
        if (empty($nome) || empty($tipo) || empty($localizacao)) {
            throw new Exception('Os campos Nome, Tipo e Localização são obrigatórios.');
        }
        
        // Criação de novo recurso
        $stmt = $pdo->prepare(
            "INSERT INTO recursos (nome, tipo_recurso, localizacao, capacidade, descricao, ativo) 
             VALUES (:nome, :tipo, :localizacao, :capacidade, :descricao, 1)"
        );
        $resultado = $stmt->execute([
            ':nome' => $nome,
            ':tipo' => $tipo,
            ':localizacao' => $localizacao,
            ':capacidade' => $capacidade,
            ':descricao' => $descricao
        ]);

    // --- ROTA: Requisições via GET (Listar ou Mudar Status) ---
    } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Se o parâmetro 'list' existir, retorna todos os recursos
        if (isset($_GET['list'])) {
            $stmt = $pdo->query("SELECT id, nome, tipo_recurso, localizacao, ativo FROM recursos ORDER BY nome");
            $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'items' => $itens]);
            exit; // Termina o script aqui, pois a resposta já foi enviada
        }

        // Se a ação for 'toggle_status'
        if (isset($_GET['acao']) && $_GET['acao'] === 'toggle_status') {
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
            $status = filter_input(INPUT_GET, 'status', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 1]]);

            if ($id === false || $status === null) {
                throw new Exception('ID ou status inválido para a operação.');
            }
            
            $stmt = $pdo->prepare("UPDATE recursos SET ativo = :status WHERE id = :id");
            $resultado = $stmt->execute([':status' => $status, ':id' => $id]);
        }
    } else {
        throw new Exception('Método HTTP não suportado.');
    }

    if (!$resultado) {
        throw new Exception('A operação no banco de dados falhou por um motivo desconhecido.');
    }

    echo json_encode(['success' => true, 'message' => 'Operação concluída com sucesso.']);

} catch (Exception $e) {
    // Se qualquer coisa der errado, captura o erro e envia uma resposta JSON padronizada
    http_response_code(400); // Bad Request
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}