<?php
// Requer os arquivos necessários
require_once 'includes/db_conexao.php';
require_once 'includes/bib.php';
require_once 'vendor/autoload.php'; // Carrega o TCPDF instalado via Composer

// Protege o script, garantindo que apenas usuários logados possam gerar o PDF
verificar_login();

// 1. Pega o ID do recurso da URL e valida
$id_recurso = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_recurso) {
    die("ID do recurso é inválido.");
}

// 2. Busca os dados do recurso e dos seus agendamentos aprovados
try {
    // Busca o nome do recurso
    $stmt_recurso = $pdo->prepare("SELECT nome FROM recursos WHERE id = ?");
    $stmt_recurso->execute([$id_recurso]);
    $recurso = $stmt_recurso->fetch();

    if (!$recurso) {
        die("Recurso não encontrado.");
    }

    // Busca agendamentos aprovados para este recurso
    $stmt_agendamentos = $pdo->prepare("
        SELECT u.nome_completo, a.data_hora_inicio
        FROM agendamentos a
        JOIN usuarios u ON a.id_usuario = u.id
        WHERE a.id_recurso = ? AND a.status = 'aprovado'
    ");
    $stmt_agendamentos->execute([$id_recurso]);
    $agendamentos = $stmt_agendamentos->fetchAll();

} catch (PDOException $e) {
    die("Erro ao consultar o banco de dados: " . $e->getMessage());
}

// 3. Monta a estrutura de dados do cronograma (matriz)
// Horários baseados na imagem que você enviou
$dias_semana = ['Seg', 'Ter', 'Qua', 'Qui', 'Sex'];
$horarios = [
    '1ª aula 8:00 - 9:00', '2ª aula 9:00 - 10:00', '3ª aula 10:10 - 11:10', '4ª aula 11:10 - 12:00',
    '5ª aula 12:00 - 13:00', '6ª aula 13:00 - 14:00' // Adicione mais se necessário
];
$cronograma = array_fill_keys($horarios, array_fill_keys($dias_semana, ''));

// 4. Preenche o cronograma com os dados do banco
foreach ($agendamentos as $ag) {
    $data = new DateTime($ag['data_hora_inicio']);
    $dia_num = (int)$data->format('N') - 1; // 0=Seg, 1=Ter, ...
    $hora_inicio = $data->format('H:i');

    if (isset($dias_semana[$dia_num])) {
        $dia_chave = $dias_semana[$dia_num];
        // Lógica simples para encontrar o horário correspondente
        foreach ($horarios as $horario_chave) {
            if (strpos($horario_chave, $data->format('H:00')) !== false) {
                $cronograma[$horario_chave][$dia_chave] = $ag['nome_completo'];
                break;
            }
        }
    }
}

// 5. Inicia a criação do PDF
$pdf_check_msg = null;
if (!class_exists('TCPDF')) {
    // Ambiente sem TCPDF instalado. Informamos o usuário e abortamos com mensagem amigável.
    // Recomenda-se instalar via Composer: composer require tecnickcom/tcpdf
    set_flash('Geração de PDF indisponível: a biblioteca TCPDF não foi encontrada no servidor. Instale com <code>composer require tecnickcom/tcpdf</code> ou habilite o pacote vendor/autoload.php.', 'danger');
    header('Location: /painel_agendamentos.php');
    exit;
}

$pdf = new TCPDF('L', PDF_UNIT, 'A4', true, 'UTF-8', false); // 'L' para paisagem

// Definições seguras caso as constantes não existam (evita erros estáticos quando TCPDF não está presente)
if (!defined('PDF_UNIT')) define('PDF_UNIT', 'mm');
if (!defined('PDF_CREATOR')) define('PDF_CREATOR', 'Resolva Aqui IFPI');

// Configurações do documento
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Resolva Aqui IFPI');
$pdf->SetTitle('Horário - ' . $recurso['nome']);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(TRUE, 10);
$pdf->AddPage();

// 6. Monta o HTML da tabela para ser inserido no PDF
$html = '
<style>
    h1 { font-size: 14pt; text-align: center; font-family: helvetica; font-weight: bold; }
    table { width: 100%; border-collapse: collapse; font-family: helvetica; font-size: 8pt; }
    th, td { border: 1px solid #333; padding: 5px; text-align: center; }
    th { background-color: #EFEFEF; font-weight: bold; }
    .horario-col { width: 18%; text-align: left; }
</style>

<h1>Laboratório de Informática 3 - ' . htmlspecialchars($recurso['nome']) . '</h1>
<br><br>
<table>
    <thead>
        <tr>
            <th class="horario-col"></th>';
foreach ($dias_semana as $dia) {
    $html .= "<th>{$dia}</th>";
}
$html .= '
        </tr>
    </thead>
    <tbody>';
foreach ($cronograma as $horario => $dias) {
    $html .= '<tr><td class="horario-col">' . $horario . '</td>';
    foreach ($dias_semana as $dia) {
        $html .= '<td>' . htmlspecialchars($dias[$dia]) . '</td>';
    }
    $html .= '</tr>';
}
$html .= '
    </tbody>
</table>';

// Escreve o HTML no PDF
$pdf->writeHTML($html, true, false, true, false, '');

// Limpa qualquer saída de buffer antes de enviar o PDF
ob_end_clean();

// Fecha e envia o documento PDF para o navegador
$pdf->Output('horario_recurso_' . $id_recurso . '.pdf', 'I');