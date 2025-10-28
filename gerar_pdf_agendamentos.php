<?php
require_once __DIR__ . '/includes/db_conexao.php';

try {
    $id_agendamento = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if (!$id_agendamento) {
        throw new Exception("ID de agendamento inválido.");
    }

    // Busca os dados completos do agendamento
    $sql = "
        SELECT a.*, r.nome as recurso_nome, u.nome_completo as usuario_nome
        FROM agendamentos a
        JOIN recursos r ON a.id_recurso = r.id
        JOIN usuarios u ON a.id_usuario = u.id
        WHERE a.id = ?
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_agendamento]);
    $ag = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ag) {
        throw new Exception("Agendamento não encontrado.");
    }

    // --- Geração do QR Code ---
    // URL que o QR Code irá conter.
    $checkin_url = "https://seusistema.com.br/check_in.php?id=" . $ag['id'];
    // Usa a API externa para gerar o QR Code
    $qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?data=" . urlencode($checkin_url) . "&size=150x150&margin=10";

    // --- Geração da página HTML ---
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Comprovante - <?= htmlspecialchars($ag['id']) ?></title>
    <style>
        body { font-family: sans-serif; font-size: 14px; }
        .container { width: 80%; margin: auto; }
        .header { text-align: center; border-bottom: 2px solid #2E7D32; padding-bottom: 10px; }
        .header h1 { margin: 0; color: #2E7D32; }
        .details { margin-top: 30px; line-height: 1.8; }
        .details strong { display: inline-block; width: 120px; }
        .qr-code { text-align: center; margin-top: 40px; }
        .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #888; }
        .print-button { text-align: center; margin-top: 20px; }
        @media print {
            .print-button { display: none; }
        }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>Comprovante de Agendamento</h1>
        </div>

        <div class='details'>
            <p><strong>Recurso:</strong> " . htmlspecialchars($ag['recurso_nome']) . "</p>
            <p><strong>Usuário:</strong> " . htmlspecialchars($ag['usuario_nome']) . "</p>
            <p><strong>Início:</strong> " . (new DateTime($ag['data_hora_inicio']))->format('d/m/Y \à\s H:i') . "</p>
            <p><strong>Fim:</strong> " . (new DateTime($ag['data_hora_fim']))->format('d/m/Y \à\s H:i') . "</p>
            <p><strong>Status:</strong> " . ucfirst($ag['status']) . "</p>
        </div>

        <div class='qr-code'>
            <img src='{$qrCodeUrl}' alt='QR Code de Check-in'>
            <p>Apresente este QR Code para realizar o check-in.</p>
        </div>

        <div class='footer'>
            Resolva Aqui IFPI - Gerado em " . date('d/m/Y H:i') . "
        </div>
        
        <div class='print-button'>
            <button onclick='window.print()'>Imprimir ou Salvar como PDF</button>
        </div>
    </div>
</body>
</html>
<?php
} catch (Exception $e) {
    header("Content-Type: text/html; charset=utf-8");
    die("Erro ao gerar comprovante: " . $e->getMessage());
}
?>