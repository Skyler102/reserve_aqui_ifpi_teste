<?php
// Inicia sessão segura, prepara CSRF token e funções utilitárias
require_once __DIR__ . '/../includes/session_sec.php';
require_once __DIR__ . '/../includes/bib.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Resolva Aqui IFPI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="global.css"> </head>
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
                    <h2>Acesso ao Sistema</h2>
                    <p class="subtitulo-form">Professores e Gestores</p>
                    
                    <?php
                        // Mensagens centralizadas
                        render_flash();
                    ?>
                    
                    <form action="scripts/processa_login.php" method="post" autocomplete="off">
                        <div class="grupo-input mb-3">
                            <label for="id_email" class="rotulo-campo">Email Institucional</label>
                            <input type="email" name="email" class="campo-input" id="id_email" required>
                        </div>
                        <div class="grupo-input mb-3">
                            <label for="id_password" class="rotulo-campo">Senha</label>
                            <input type="password" name="password" class="campo-input" id="id_password" required>
                        </div>
                        
                        <button type="submit" class="botao-acessar">Entrar</button>
                    </form>
                    
                    <a href="registro.php" class="link-alternativo">Não tem conta? Cadastre-se</a>
                </div>
            </div>
        </div>
    </main>

    <footer class="rodape-pagina">
        <span><i class="far fa-copyright"></i> 2025 Resolva Aqui - Desenvolvido por VeredaTech</span>
        <span><a href="../login_gerador.php" id="link-instituto" style="color:inherit; text-decoration:none;"><i class="fas fa-university"></i> Instituto Federal do Piauí</a></span>
    </footer>

</body>
</html>