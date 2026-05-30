<?php
session_start();
require '../../vendor/autoload.php';
include '../config.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$id_protocolo = $_POST['id_protocolo'] ?? '';
$titulo = $_POST['titulo_pdf'] ?? 'DOCUMENTO OFICIAL';
$texto = $_POST['corpo_texto'] ?? '';
$ra = $_POST['ra'] ?? '';
$dataAtual = date('d/m/Y');

// Recebe as credenciais do Modal
$login_assinatura = $_POST['login_assinatura'] ?? '';
$senha_assinatura = $_POST['senha_assinatura'] ?? '';

// 1. VALIDAÇÃO DE SEGURANÇA NO BANCO DE DADOS
$stmtAdmin = $pdo->prepare("SELECT nome, senha, assinatura_img FROM usuarios_secretaria WHERE usuario = ?");
$stmtAdmin->execute([$login_assinatura]);
$usuarioBD = $stmtAdmin->fetch();

// 2. VERIFICA A SENHA COM BCRYPT
if (!$usuarioBD || !password_verify($senha_assinatura, $usuarioBD['senha'])) {
    die("<div style='font-family: Arial; text-align: center; margin-top: 50px; color: #dc3545;'>
            <h1>❌ Acesso Negado</h1>
            <p>Login ou senha incorretos. O documento não foi autorizado e não foi gerado.</p>
         </div>");
}

$nome_funcionario = $usuarioBD['nome'];
$arquivo_assinatura = $usuarioBD['assinatura_img'];

if (empty($arquivo_assinatura)) {
    die("<div style='font-family: Arial; text-align: center; margin-top: 50px;'>
            <h1>⚠️ Falta de Assinatura</h1>
            <p>O usuário <b>{$nome_funcionario}</b> autenticou com sucesso, mas não possui uma foto de assinatura cadastrada no banco de dados.</p>
         </div>");
}

$assinaturaHtml = "";
$assinaturaPath = '../../avatar/' . $arquivo_assinatura;

function base64_image($path) {
    if (file_exists($path)) {
        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);
        return 'data:image/' . $type . ';base64,' . base64_encode($data);
    }
    return '';
}

$logoBase64 = base64_image('../../avatar/logo_fatec.png');
$assinaturaBase64 = base64_image($assinaturaPath);

if ($assinaturaBase64) {
    $assinaturaHtml = "<img src='{$assinaturaBase64}' alt='Assinatura Digital' style='max-width: 200px; max-height: 100px; margin-bottom: 5px;'>";
} else {
    $assinaturaHtml = "<p style='font-style: italic; color: #999;'>(Assinatura Digital - Arquivo não encontrado)</p>";
}

$logoHtml = "";
if ($logoBase64) {
    $logoHtml = "<img src='{$logoBase64}' width='150'>";
}

$htmlDoDocumento = "
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; line-height: 1.6; color: #333; }
        .cabecalho { text-align: center; margin-bottom: 40px; border-bottom: 2px solid #b30000; padding-bottom: 20px; }
        .titulo { text-decoration: underline; font-size: 18px; margin-bottom: 30px; text-align: center; font-weight: bold;}
        .texto { font-size: 16px; text-align: justify; margin-bottom: 60px; line-height: 2; padding: 0 30px; }
        .assinatura { text-align: center; margin-top: 80px; }
        .linha-assinatura { border-top: 1px solid #000; width: 60%; margin: 0 auto; margin-top: 5px; padding-top: 5px; }
        .footer { position: absolute; bottom: 30px; width: 100%; text-align: center; font-size: 10px; color: #777; border-top: 1px solid #ccc; padding-top: 10px; }
    </style>
</head>
<body>
    <div class='cabecalho'>
        {$logoHtml}
    </div>
    
    <div class='titulo'>
        {$titulo}
    </div>

    <div class='texto'>
        " . nl2br($texto) . "
        <br><br>
        <p style='text-align: right;'>São Paulo, {$dataAtual}.</p>
    </div>

    <div class='assinatura'>
        {$assinaturaHtml}
        <div class='linha-assinatura'>
            <strong>{$nome_funcionario}</strong><br>
            Secretaria Acadêmica<br>
            FATEC Zona Sul<br>
            <em>Documento emitido eletronicamente via Protocolo {$id_protocolo}</em>
        </div>
    </div>
    
    <div class='footer'>
        Documento gerado automaticamente pelo sistema de Gestão - MarIA. Verificação de autenticidade disponível na secretaria da unidade.
    </div>
</body>
</html>
";

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($htmlDoDocumento);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Marca o protocolo como Concluído se $id_protocolo existir
if ($id_protocolo) {
    $stmtUpd = $pdo->prepare("UPDATE protocolos SET status = 'Concluído' WHERE id_protocolo = ?");
    $stmtUpd->execute([$id_protocolo]);
}

$nomeArquivo = str_replace([' ', '/', '\\'], '_', $titulo) . '_' . $ra . ".pdf";
$dompdf->stream($nomeArquivo, array("Attachment" => false));
?>
