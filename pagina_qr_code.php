<?php
require_once __DIR__ . '/includes/session_sec.php';
require_once __DIR__ . '/includes/bib.php';
verificar_admin();
gerar_cabecalho('QR Code do Recurso');
?>

<main id="conteudo-principal" class="container mt-4 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
            <div class="card shadow-lg border-0 mt-4 text-center">
                <div class="card-header">
                    <h4 class="mb-0">QR Code do Horário</h4>
                </div>
                <div class="card-body p-4">
                    <?php if (isset($_GET['id']) && isset($_GET['nome'])): ?>
                        <?php
                            $id = htmlspecialchars($_GET['id']);
                            $nome_recurso = htmlspecialchars($_GET['nome']);
                            
                            // CORREÇÃO: Aponta o QR Code para o script que gera o PDF do horário semanal
                            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
                            $host = $_SERVER['HTTP_HOST'];
                            $base_url = get_base_url(); // Usar a função get_base_url() do bib.php
                            $qr_data = $protocol . $host . $base_url . "/gerar_horario_semanal.php?id=" . $id;
                        ?>
                        <h5 class="mb-3">Recurso: <span class="fw-bold"><?= $nome_recurso ?></span></h5>
                        <div class="mb-3">
                            <p class="text-muted small">Aponte a câmera para o QR Code para ver o horário semanal deste recurso em PDF.</p>
                            <img src="https://api.qrserver.com/v1/create-qr-code/?data=<?= urlencode($qr_data) ?>&size=220x220&qzone=1"
                                 alt="QR Code do Horário para <?= $nome_recurso ?>" 
                                 class="img-fluid">
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">Dados do recurso não fornecidos.</div>
                    <?php endif; ?>
                    <a href="painel_admin.php" class="btn btn-acao-outline w-100 mt-3">Voltar ao Painel</a>
                </div>
            </div>
        </div>
    </div>
</main>

<?php gerar_rodape(); ?>