<?php
// Inicia sessão segura e prepara CSRF token
require_once __DIR__ . '/includes/session_sec.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Novo Agendamento - Resolva Aqui IFPI</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/main.min.css' rel='stylesheet' />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700;800&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="global.css">
</head>
<body>

<header>
    <div id="barra-acessibilidade">
        <div class="container">
            <ul id="links-acessibilidade">
                <li><a href="#conteudo-principal">Ir para o conteúdo <span>1</span></a></li>
                <li><a href="#navegacao-servicos">Ir para o menu <span>2</span></a></li>
                <li><a href="#TextoBuscavel">Ir para a busca <span>3</span></a></li>
                <li><a href="#rodape-principal">Ir para o rodapé <span>4</span></a></li>
            </ul>
        </div>
    </div>
    <div id="cabecalho-principal">
        <div class="container">
            <div id="container-logo">
                <p class="marca-subtitulo m-0">Resolva Aqui</p>
                <h1 class="marca-titulo">IFPI</h1>
                <p class="marca-descricao m-0">MINISTÉRIO DA EDUCAÇÃO</p>
            </div>
            <div id="container-busca-social">
                <div id="caixa-busca-portal">
                    <form action="#">
                        <label class="estrutura-oculta" for="TextoBuscavel">Buscar no portal</label>
                        <input name="TextoBuscavel" type="text" title="Buscar no portal" placeholder="Buscar no portal" class="campo-busca" id="TextoBuscavel">
                        <button class="botao-busca" type="submit" aria-label="Buscar"><i class="fa fa-search"></i></button>
                    </form>
                </div>
                <div id="icones-sociais">
                    <ul>
                        <li><a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a></li>
                        <li><a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a></li>
                        <li><a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a></li>
                        <li><a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <nav id="barra-servicos" aria-label="Menu de Serviços">
        <div class="container">
            <ul id="navegacao-servicos">
                <li><a href="painel_agendamentos.php" class="btn-nav btn-nav-principal">Início</a></li>
                <li><a href="/registro/login.php" class="btn-nav btn-nav-sair">Sair</a></li>
            </ul>
        </div>
    </nav>
</header>

<main id="conteudo-principal" class="container mt-4 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-7">
            <div class="card shadow-lg border-0 mt-4">
                <div class="card-header">
                    <h4 class="mb-0" id="textob">Solicitar Agendamento</h4>
                </div>
                <div class="card-body p-4">
                    <form method="post" action="scripts/processa_agendamento.php" autocomplete="off">
                        <?php if (isset($_SESSION['csrf_token'])): ?>
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label class="form-label" for="recurso">Recurso</label>
                            <select name="resource" class="form-select" id="recurso" required>
                                <option selected disabled value="">Selecione um recurso...</option>
                                <?php
                                require_once 'includes/db_conexao.php';
                                $stmt = $pdo->query("SELECT id, nome FROM recursos WHERE ativo = 1 ORDER BY nome");
                                while ($recurso = $stmt->fetch()) {
                                    echo "<option value=\"{$recurso['id']}\">{$recurso['nome']}</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="motivo">Motivo</label>
                            <textarea name="purpose" rows="3" class="form-control" id="motivo" 
                                placeholder="Ex: Aula prática da disciplina X" required></textarea>
                        </div>

                        <div class="seletor-tipo-agendamento">
                            <label class="form-label d-block">Tipo de Agendamento</label>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="tipo-agendamento" 
                                    id="tipo-unico" value="unico" checked>
                                <label class="form-check-label" for="tipo-unico">Agendamento Único</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="tipo-agendamento" 
                                    id="tipo-fixo" value="fixo">
                                <label class="form-check-label" for="tipo-fixo">Agendamento Fixo (Recorrente)</label>
                            </div>
                        </div>

                        <div id="campos-agendamento-unico">
                            <div class="mb-3">
                                <label class="form-label" for="inicio-agendamento">Início do Agendamento</label>
                                <input type="datetime-local" name="start_time" class="form-control" 
                                    id="inicio-agendamento" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="fim-agendamento">Fim do Agendamento</label>
                                <input type="datetime-local" name="end_time" class="form-control" 
                                    id="fim-agendamento" required>
                            </div>
                        </div>

                        <div id="campos-agendamento-fixo" style="display: none;">
                            <div class="mb-3">
                                <label class="form-label" for="dia-semana">Dia da semana</label>
                                <select name="recurrence_weekday" class="form-select" id="dia-semana">
                                    <option value="0">Segunda-feira</option>
                                    <option value="1">Terça-feira</option>
                                    <option value="2">Quarta-feira</option>
                                    <option value="3">Quinta-feira</option>
                                    <option value="4">Sexta-feira</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="hora-inicio-recorrencia">Hora de Início</label>
                                <input type="time" name="recurrence_start_time" class="form-control" 
                                    id="hora-inicio-recorrencia">
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="hora-fim-recorrencia">Hora de Fim</label>
                                <input type="time" name="recurrence_end_time" class="form-control" 
                                    id="hora-fim-recorrencia">
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="data-inicio-recorrencia">Data de início da recorrência</label>
                                <input type="date" name="recurrence_start" class="form-control" 
                                    id="data-inicio-recorrencia">
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="data-fim-recorrencia">Data de fim da recorrência</label>
                                <input type="date" name="recurrence_end" class="form-control" 
                                    id="data-fim-recorrencia">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-acao w-100 mt-3">Solicitar Reserva</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<footer id="rodape-principal" class="rodape">
    <div class="container">
        <span>© 2025 Resolva Aqui IFPI - Instituto Federal do Piauí</span>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function atualizarCamposAgendamento() {
        const tipoSelecionado = document.querySelector('input[name="tipo-agendamento"]:checked').value;
        const camposUnico = document.getElementById('campos-agendamento-unico');
        const camposFixo = document.getElementById('campos-agendamento-fixo');
        
        if (tipoSelecionado === 'unico') {
            camposUnico.style.display = 'block';
            camposFixo.style.display = 'none';
            // Torna os campos únicos obrigatórios e os recorrentes opcionais
            document.getElementById('inicio-agendamento').required = true;
            document.getElementById('fim-agendamento').required = true;
            document.getElementById('hora-inicio-recorrencia').required = false;
            document.getElementById('hora-fim-recorrencia').required = false;
            document.getElementById('data-inicio-recorrencia').required = false;
            document.getElementById('data-fim-recorrencia').required = false;
        } else {
            camposUnico.style.display = 'none';
            camposFixo.style.display = 'block';
            // Torna os campos recorrentes obrigatórios e os únicos opcionais
            document.getElementById('inicio-agendamento').required = false;
            document.getElementById('fim-agendamento').required = false;
            document.getElementById('hora-inicio-recorrencia').required = true;
            document.getElementById('hora-fim-recorrencia').required = true;
            document.getElementById('data-inicio-recorrencia').required = true;
            document.getElementById('data-fim-recorrencia').required = true;
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const radios = document.querySelectorAll('input[name="tipo-agendamento"]');
        radios.forEach(radio => {
            radio.addEventListener('change', atualizarCamposAgendamento);
        });
        atualizarCamposAgendamento();
    });
</script>

</body>
</html>