<?php
// Inicia sessão segura e carrega funções utilitárias como render_flash()
require_once __DIR__ . '/../includes/session_sec.php';
require_once __DIR__ . '/../includes/bib.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Conta - Resolva Aqui IFPI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="global.css">
</head>
<body>

    <main class="conteudo-principal">
        <div class="container-login">
            <div class="secao-info">
                <img src="../assets/logos/logo-ifpi-transp1.png" alt="Logo IFPI" class="logo-imagem">
                <p class="slogan">
                    A plataforma para gestão e resolução <br> de demandas do Instituto Federal do Piauí.
                </p>
                <div class="container-ilustracao">
                    <img src="../assets/ilustracao.png" alt="Ilustração" class="ilustracao">
                </div>
            </div>

            <div class="container-formulario">
                <img src="../assets/logos/logo-ifpi-transp1.png" alt="Logo IFPI" class="logo-movel">
                
                <div class="formulario-login">
                    <h2>Criar Conta</h2>
                    <p class="subtitulo-form">Preencha seus dados institucionais</p>
                    
                    <?php
                        // Exibe mensagens centralizadas
                        render_flash();
                    ?>
                    
                    <form action="scripts/processa_registro.php" method="post" autocomplete="off">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="grupo-input">
                                    <label for="id_full_name" class="rotulo-campo">Nome Completo</label>
                                    <input type="text" name="full_name" class="campo-input" id="id_full_name" required>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="grupo-input">
                                    <label for="id_registration" class="rotulo-campo">Matrícula SUAP</label>
                                    <input type="text" name="registration" class="campo-input" id="id_registration" required>
                                </div>
                            </div>
                        </div>

                        <div class="grupo-input mb-3">
                            <label for="id_email" class="rotulo-campo">Email Institucional</label>
                            <input type="email" name="email" class="campo-input" id="id_email" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="grupo-input">
                                    <label for="id_password1" class="rotulo-campo">Senha</label>
                                    <input type="password" name="password" class="campo-input" id="id_password1" required>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="grupo-input">
                                    <label for="id_password2" class="rotulo-campo">Confirmação da senha</label>
                                    <input type="password" name="password_confirm" class="campo-input" id="id_password2" required>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="botao-acessar">Registrar</button>
                    </form>
                    
                    <a href="login.php" class="link-alternativo">Já tem conta? Faça login</a>
                </div>
            </div>
        </div>
    </main>

    <footer class="rodape-pagina">
        <span><i class="far fa-copyright"></i> 2025 Resolva Aqui - Desenvolvido por VeredaTech</span>
        <span><i class="fas fa-university"></i> Instituto Federal do Piauí</span>
    </footer>

</body>
</html>