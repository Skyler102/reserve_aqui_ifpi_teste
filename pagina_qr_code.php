<?php
require_once __DIR__ . '/includes/session_sec.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR CODE - Resolva Aqui IFPI</title>

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
                <li><a href="painel_admin.php" class="btn-nav btn-nav-principal">Início</a></li>
                <li><a href="/registro/login.php" class="btn-nav btn-nav-sair">Sair</a></li>
            </ul>
        </div>
    </nav>
</header>

<main id="conteudo-principal" class="container mt-4 mb-5">
    <div class="row justify-content-center">
      <div class="col-md-6 col-lg-4">
        <div class="card shadow-lg border-0 mt-4 text-center">
          <div class="card-header">
            <h4 class="mb-0" id="textob">QR Code do Laboratório</h4>
          </div>
          <div class="card-body p-4">
            <?php
            if (isset($_GET['id']) && isset($_GET['nome'])) {
                $id = htmlspecialchars($_GET['id']);
                $nome = htmlspecialchars($_GET['nome']);
                $qr_data = "https://" . $_SERVER['HTTP_HOST'] . "/check_lab.php?id=" . $id;
            ?>
                <h5 class="mb-3">Laboratório: <span class="fw-bold"><?php echo $nome; ?></span></h5>
                <div class="mb-3">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?data=<?php echo urlencode($qr_data); ?>&size=220x220" 
                         alt="QR Code do Laboratório <?php echo $nome; ?>" 
                         class="img-fluid">
                </div>
            <?php } else { ?>
                <div class="alert alert-warning">
                    Dados do laboratório não fornecidos.
                </div>
            <?php } ?>
            <a href="painel_admin.php" class="btn btn-acao-outline w-100">Voltar ao Painel</a>
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
</body>
</html>