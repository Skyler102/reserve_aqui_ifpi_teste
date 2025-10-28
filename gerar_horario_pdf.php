<?php
require_once __DIR__ . '/includes/db_conexao.php';

try {
    // 1. Validar e buscar dados do recurso
    $id_recurso = filter_input(INPUT_GET, 'id_recurso', FILTER_VALIDATE_INT);
    if (!$id_recurso) throw new Exception("ID de recurso inválido.");

    $stmt_recurso = $pdo->prepare("SELECT nome FROM recursos WHERE id = ?");
    $stmt_recurso->execute([$id_recurso]);
    $recurso = $stmt_recurso->fetch(PDO::FETCH_ASSOC);
    if (!$recurso) throw new Exception("Recurso não encontrado.");
    $nome_recurso = $recurso['nome'];

    // 2. Buscar agendamentos da semana atual (de segunda a domingo)
    $sql_ag = "SELECT u.nome_completo, a.motivo, a.data_hora_inicio FROM agendamentos a JOIN usuarios u ON a.id_usuario = u.id WHERE a.id_recurso = ? AND a.status = 'aprovado' AND WEEK(a.data_hora_inicio, 1) = WEEK(CURDATE(), 1) AND YEAR(a.data_hora_inicio) = YEAR(CURDATE())";
    $stmt_ag = $pdo->prepare($sql_ag);
    $stmt_ag->execute([$id_recurso]);
    $agendamentos = $stmt_ag->fetchAll(PDO::FETCH_ASSOC);

    // 3. Definir a estrutura completa da grade de horários (AJUSTADO CONFORME IMAGEM)
    $dias_semana = [1 => 'Seg', 2 => 'Ter', 3 => 'Qua', 4 => 'Qui', 5 => 'Sex'];
    $horarios_aula = [
        // Manhã
        '1ª aula' => ['inicio' => '07:00', 'fim' => '08:00', 'label' => '7:00 - 8:00'],
        '2ª aula' => ['inicio' => '08:00', 'fim' => '09:00', 'label' => '8:00 - 9:00'],
        '3ª aula' => ['inicio' => '09:00', 'fim' => '09:50', 'label' => '9:00 - 9:50'],
        '4ª aula' => ['inicio' => '10:10', 'fim' => '11:10', 'label' => '10:10 - 11:10'],
        '5ª aula' => ['inicio' => '11:10', 'fim' => '12:00', 'label' => '11:10 - 12:00'],
        '6ª aula' => ['inicio' => '12:00', 'fim' => '13:00', 'label' => '12:00 - 13:00'],
        'intervalo1' => ['label' => 'Final/Início de turno'], // Linha de separação
        // Tarde
        '1ª aula tarde' => ['inicio' => '14:00', 'fim' => '15:00', 'label' => '14:00 - 15:00'],
        '2ª aula tarde' => ['inicio' => '15:00', 'fim' => '16:00', 'label' => '15:00 - 16:00'],
        '3ª aula tarde' => ['inicio' => '16:00', 'fim' => '17:00', 'label' => '16:00 - 17:00'],
        '4ª aula tarde' => ['inicio' => '17:00', 'fim' => '18:00', 'label' => '17:00 - 18:00'],
        'intervalo2' => ['label' => 'Final/Início de turno'], // Linha de separação
        // Noite
        '1ª aula noite' => ['inicio' => '18:00', 'fim' => '19:00', 'label' => '18:00 - 19:00'],
        '2ª aula noite' => ['inicio' => '19:00', 'fim' => '20:00', 'label' => '19:00 - 20:00'],
        '3ª aula noite' => ['inicio' => '20:00', 'fim' => '21:00', 'label' => '20:00 - 21:00'],
        '4ª aula noite' => ['inicio' => '21:00', 'fim' => '22:00', 'label' => '21:00 - 22:00'],
    ];

    // 4. Preencher a grade com os agendamentos
    $grade = [];
    foreach ($agendamentos as $ag) {
        $inicio = new DateTime($ag['data_hora_inicio']);
        $dia = (int) $inicio->format('N');
        $hora = $inicio->format('H:i');

        foreach ($horarios_aula as $key => $slot) {
            if (isset($slot['inicio']) && $hora >= $slot['inicio'] && $hora < $slot['fim']) {
                $grade[$key][$dia] = "<b>" . htmlspecialchars($ag['nome_completo']) . "</b><br><small>" . htmlspecialchars($ag['motivo']) . "</small>";
                break;
            }
        }
    }

    // 6. Montar o HTML para o PDF
    $html = '<style> table { border-collapse: collapse; width: 100%; font-size: 7.5pt; } th, td { border: 1px solid #999; padding: 4px; text-align: center; vertical-align: middle; height: 35px; } th { background-color: #E0E0E0; font-weight: bold; } .hora { font-weight: bold; width: 15%; text-align: right; padding-right: 5px; } .intervalo { background-color: #E0E0E0; font-weight: bold; font-style: italic; } </style>';
    $html .= '<h3>' . htmlspecialchars($nome_recurso) . '</h3><p>Instituto Federal do Piauí, Campus Floriano, PI</p>';
    $html .= '<table><thead><tr><th class="hora"></th>';
    foreach ($dias_semana as $dia) $html .= "<th>$dia</th>";
    $html .= '</tr></thead><tbody>';
    foreach ($horarios_aula as $key => $slot) {
        if (isset($slot['inicio'])) { // É um slot de aula normal
            $html .= "<tr><td class='hora'>{$key}<br><small>{$slot['label']}</small></td>";
            for ($i = 1; $i <= 5; $i++) {
                $html .= '<td>' . ($grade[$key][$i] ?? '') . '</td>';
            }
            $html .= '</tr>';
        } else { // É uma linha de intervalo
            $html .= "<tr><td colspan='6' class='intervalo'>{$slot['label']}</td></tr>";
        }
    }
    $html .= '</tbody></table>';
    $html .= '<div style="position: absolute; bottom: 10px; left: 10px; font-size: 6pt;">Horário criado: ' . date('d/m/Y') . '</div>';
    

    // 7. Gerar PDF com TCPDF
    $pdf = new \TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('Resolva Aqui IFPI');
    $pdf->SetTitle('Horário - ' . $nome_recurso);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(10, 10, 10);
    $pdf->AddPage();
    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Output('horario_' . $id_recurso . '.pdf', 'I');

} catch (Exception $e) {
    die("ERRO: " . $e->getMessage());
}