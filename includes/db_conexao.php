<?php
// --- Configurações do Banco de Dados ---
// Altere estas variáveis de acordo com o seu ambiente XAMPP
$host = 'localhost';          // O servidor do banco de dados (geralmente localhost)
$db_name = 'resolva_aqui_ifpi'; // O nome do banco de dados que você vai criar
$username = 'root';           // O usuário do banco de dados (padrão do XAMPP)
$password = '';               // A senha do banco de dados (padrão do XAMPP é vazia)
$charset = 'utf8mb4';         // Conjunto de caracteres para suportar acentos e emojis

// --- Data Source Name (DSN) ---
// String de conexão que informa ao PDO como se conectar
$dsn = "mysql:host=$host;dbname=$db_name;charset=$charset";

// --- Opções do PDO ---
// Configurações para o comportamento da conexão
$options = [
    // Lança exceções em caso de erros, em vez de apenas avisos
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    // Retorna os resultados do banco como arrays associativos (ex: $linha['nome_da_coluna'])
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    // Desativa a emulação de prepared statements para usar o modo nativo do MySQL, que é mais seguro
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// --- Tentativa de Conexão ---
try {
    // Cria a instância do objeto PDO, que representa a conexão com o banco de dados
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (\PDOException $e) {
    // Se a conexão falhar, exibe uma mensagem de erro genérica e interrompe o script.
    // Em um sistema real (produção), você deveria registrar este erro em um arquivo de log
    // em vez de exibi-lo na tela para o usuário.
    // Para depuração, você pode descomentar a linha abaixo para ver o erro detalhado:
    // throw new \PDOException($e->getMessage(), (int)$e->getCode());
    
    die("Erro: Não foi possível conectar ao banco de dados. Por favor, verifique as configurações em 'includes/db_conexao.php'.");
}
?>