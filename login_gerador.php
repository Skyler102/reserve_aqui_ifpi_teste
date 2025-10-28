<?php
session_start();
require_once __DIR__ . '/includes/bib.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Gerador - Resolva Aqui IFPI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="registro/global.css"> 
</head>
<body>
    <main class="conteudo-principal">
        <div class="container-formulario" id="container-gerador">
            
            <img src="assets/logos/logo-ifpi-transp1.png" alt="Logo IFPI" id="logo-gerador">
            
            <div class="formulario-login" id="form-gerador">
                <h2>Acesso ao Gerador</h2>
                <p class="subtitulo-form">Entre com suas credenciais</p>
                
                <?php
                    render_flash();
                ?>
                
                <form action="scripts/auth_gerador.php" method="post">
                    <div class="grupo-input mb-4">
                        <label for="usuario" class="rotulo-campo">Usuário</label>
                        <input type="text" class="campo-input" id="usuario" name="usuario" required>
                    </div>
                    
                    <div class="grupo-input mb-4">
                        <label for="senha" class="rotulo-campo">Senha</label>
                        <input type="password" class="campo-input" id="senha" name="senha" required>
                    </div>
                    
                    <button type="submit" class="botao-acessar">
                        Entrar
                    </button>
                </form>
            </div>
        </div>
    </main>
</body>
</html>