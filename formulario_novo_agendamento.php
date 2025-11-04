<?php
// Inicia sessão segura e prepara CSRF token
require_once __DIR__ . '/includes/session_sec.php';
require_once __DIR__ . '/includes/db_conexao.php'; // Adicionado para ter acesso a $pdo
require_once __DIR__ . '/includes/bib.php'; // Adicionado para consistência e futuras funções

// NOVO: Verifica se o usuário está logado para ver esta página
verificar_login(); 

gerar_cabecalho('Novo Agendamento'); // Função para gerar o cabeçalho
?>

<main id="conteudo-principal" class="container mt-4 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-7">
            <div class="card shadow-lg border-0 mt-4">
                <div class="card-header">
                    <h4 class="mb-0" id="textob">Solicitar Agendamento</h4>
                </div>
                <div class="card-body p-4">
                    
                    <?php render_flash(); // Adicionado para mostrar mensagens de erro do processamento ?>

                    <form method="post" action="scripts/processa_agendamento.php" autocomplete="off">
                        <?php if (isset($_SESSION['csrf_token'])): ?>
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label class="form-label" for="recurso">Recurso</label>
                            <select name="recurso_id" class="form-select" id="recurso" required>
                                <option selected disabled value="">Selecione um recurso...</option>
                                <?php
                                $stmt = $pdo->query("SELECT id, nome FROM recursos WHERE ativo = 1 ORDER BY nome");
                                while ($recurso = $stmt->fetch()) {
                                    echo "<option value=\"{$recurso['id']}\">" . htmlspecialchars($recurso['nome']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="turma">Turma</label>
                            <select name="turma_id" class="form-select" id="turma" required>
                                <option selected disabled value="">Selecione uma turma...</option>
                                <?php
                                $stmt_turmas = $pdo->query("SELECT id, nome FROM turmas ORDER BY nome");
                                $turmas_encontradas = false;
                                while ($turma = $stmt_turmas->fetch()) {
                                    $turmas_encontradas = true;
                                    echo "<option value=\"{$turma['id']}\">" . htmlspecialchars($turma['nome']) . "</option>";
                                }
                                if (!$turmas_encontradas) {
                                    echo '<option disabled>Nenhuma turma cadastrada (Vá ao painel admin)</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="disciplina">Disciplina</label>
                            <select name="disciplina_id" class="form-select" id="disciplina" required>
                                <option selected disabled value="">Selecione uma disciplina...</option>
                                <?php
                                $stmt_disciplinas = $pdo->query("SELECT id, nome FROM disciplinas ORDER BY nome");
                                $disciplinas_encontradas = false;
                                while ($disciplina = $stmt_disciplinas->fetch()) {
                                    $disciplinas_encontradas = true;
                                    echo "<option value=\"{$disciplina['id']}\">" . htmlspecialchars($disciplina['nome']) . "</option>";
                                }
                                if (!$disciplinas_encontradas) {
                                    echo '<option disabled>Nenhuma disciplina cadastrada (Vá ao painel admin)</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="motivo">Motivo</label>
                            <textarea name="motivo" rows="3" class="form-control" id="motivo" 
                                placeholder="Ex: Aula prática sobre Células" required></textarea>
                        </div>

                        <div class="seletor-tipo-agendamento">
                            <label class="form-label d-block">Tipo de Agendamento</label>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="tipo_agendamento" 
                                    id="tipo-unico" value="unico" checked>
                                <label class="form-check-label" for="tipo-unico">Agendamento Único</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="tipo_agendamento" 
                                    id="tipo-fixo" value="fixo">
                                <label class="form-check-label" for="tipo-fixo">Agendamento Fixo (Recorrente)</label>
                            </div>
                        </div>

                        <div id="campos-agendamento-unico">
                            <div class="mb-3">
                                <label class="form-label" for="inicio-agendamento">Início do Agendamento</label>
                                <input type="datetime-local" name="data_hora_inicio" class="form-control" 
                                    id="inicio-agendamento" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="fim-agendamento">Fim do Agendamento</label>
                                <input type="datetime-local" name="data_hora_fim" class="form-control" 
                                    id="fim-agendamento" required>
                            </div>
                        </div>

                        <div id="campos-agendamento-fixo" style="display: none;">
                            <div class="mb-3">
                                <label class="form-label" for="dia-semana">Dia da semana</label>
                                <select name="dia_semana_recorrencia" class="form-select" id="dia-semana">
                                    <option value="1">Segunda-feira</option>
                                    <option value="2">Terça-feira</option>
                                    <option value="3">Quarta-feira</option>
                                    <option value="4">Quinta-feira</option>
                                    <option value="5">Sexta-feira</option>
                                </select>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="hora-inicio-recorrencia">Hora de Início</label>
                                    <input type="time" name="hora_inicio_recorrencia" class="form-control" 
                                        id="hora-inicio-recorrencia">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="hora-fim-recorrencia">Hora de Fim</label>
                                    <input type="time" name="hora_fim_recorrencia" class="form-control" 
                                        id="hora-fim-recorrencia">
                                </div>
                            </div>
                             <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="data-inicio-recorrencia">Início da recorrência</label>
                                    <input type="date" name="data_inicio_recorrencia" class="form-control" 
                                        id="data-inicio-recorrencia">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="data-fim-recorrencia">Fim da recorrência</label>
                                    <input type="date" name="data_fim_recorrencia" class="form-control" 
                                        id="data-fim-recorrencia">
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-acao w-100 mt-3">Solicitar Reserva</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<?php gerar_rodape(); // Função para gerar o rodapé ?>

<script>
    function atualizarCamposAgendamento() {
        const tipoSelecionado = document.querySelector('input[name="tipo_agendamento"]:checked').value;
        const camposUnico = document.getElementById('campos-agendamento-unico');
        const camposFixo = document.getElementById('campos-agendamento-fixo');
        
        const unicoInputs = camposUnico.querySelectorAll('input');
        const fixoInputs = camposFixo.querySelectorAll('input, select');

        if (tipoSelecionado === 'unico') {
            camposUnico.style.display = 'block';
            camposFixo.style.display = 'none';

            // Ativa e torna obrigatório os campos únicos
            unicoInputs.forEach(input => {
                input.required = true;
                input.disabled = false; // <-- MUDANÇA: Garante que está habilitado
            });
            // Desativa e remove obrigação dos campos fixos
            fixoInputs.forEach(input => {
                input.required = false;
                input.disabled = true; // <-- MUDANÇA: Desabilita para não enviar
            });
        } else {
            camposUnico.style.display = 'none';
            camposFixo.style.display = 'block';

            // Desativa e remove obrigação dos campos únicos
            unicoInputs.forEach(input => {
                input.required = false;
                input.disabled = true; // <-- MUDANÇA: Desabilita para não enviar
            });
            // Ativa e torna obrigatório os campos fixos
            fixoInputs.forEach(input => {
                input.required = true;
                input.disabled = false; // <-- MUDANÇA: Garante que está habilitado
            });
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const radios = document.querySelectorAll('input[name="tipo_agendamento"]');
        radios.forEach(radio => {
            radio.addEventListener('change', atualizarCamposAgendamento);
        });
        atualizarCamposAgendamento(); // Chama a função na carga inicial
    });
</script>

</body>
</html>
