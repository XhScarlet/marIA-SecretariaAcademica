<?php
session_start();
require '../../vendor/autoload.php';
include '../config.php';

use Dompdf\Dompdf;
use Dompdf\Options;
use chillerlan\QRCode\{QRCode, QROptions};

$id_protocolo = $_POST['id_protocolo'] ?? '';
$titulo = $_POST['titulo_pdf'] ?? 'DOCUMENTO OFICIAL';
$texto = $_POST['corpo_texto'] ?? '';
$ra = $_POST['ra'] ?? '';
$dataAtual = date('d/m/Y');

// Recebe as credenciais do Modal
$login_assinatura = $_POST['login_assinatura'] ?? '';
$senha_assinatura = $_POST['senha_assinatura'] ?? '';

// 1. VALIDAÇÃO DE SEGURANÇA NO BANCO DE DADOS
$stmtAdmin = $pdo->prepare("SELECT id, nome, senha, assinatura_img FROM usuarios_secretaria WHERE usuario = ?");
$stmtAdmin->execute([$login_assinatura]);
$usuarioBD = $stmtAdmin->fetch();

$autenticado_normal = false;
$autenticado_delegado = false;
$dados_assinatura = [];

if ($usuarioBD) {
    // 2. VERIFICA A SENHA PADRÃO COM BCRYPT
    if (password_verify($senha_assinatura, $usuarioBD['senha'])) {
        $autenticado_normal = true;
        $dados_assinatura = [
            'nome' => $usuarioBD['nome'],
            'assinatura_img' => $usuarioBD['assinatura_img'],
            'mensagem_delegacao' => ''
        ];
    } else {
        // 3. SE A SENHA PADRÃO FALHOU, TENTA AUTENTICAR VIA DELEGAÇÃO DE ASSINATURA
        require_once '../models/DelegacaoModel.php';
        require_once '../use_cases/AssinarDocumentoUseCase.php';
        
        $delegacaoModel = new DelegacaoModel($pdo);
        $assinarUseCase = new AssinarDocumentoUseCase($delegacaoModel);
        
        try {
            $dados_delegacao = $assinarUseCase->execute($usuarioBD['id'], $senha_assinatura);
            $autenticado_delegado = true;
            $dados_assinatura = [
                'nome' => $dados_delegacao['nome_origem'],
                'assinatura_img' => $dados_delegacao['assinatura_img'],
                'mensagem_delegacao' => "<br><span style='font-size: 0.8em; color: #666;'>(Assinado via Delegação de Poderes por: " . $usuarioBD['nome'] . ")</span>"
            ];
        } catch (Exception $e) {
            // A senha não é a padrão e também não bateu com nenhum token de delegação
            $erro_delegacao = $e->getMessage();
        }
    }
}

// Se não conseguiu autenticar de nenhuma das duas formas
if (!$autenticado_normal && !$autenticado_delegado) {
    die("<div style='font-family: Arial; text-align: center; margin-top: 50px; color: #dc3545;'>
            <h1>❌ Acesso Negado</h1>
            <p>Login ou senha incorretos. O documento não foi autorizado e não foi gerado.</p>
         </div>");
}

$nome_funcionario = $dados_assinatura['nome'];
$arquivo_assinatura = $dados_assinatura['assinatura_img'];
$mensagem_delegacao = $dados_assinatura['mensagem_delegacao'];

if (empty($arquivo_assinatura)) {
    die("<div style='font-family: Arial; text-align: center; margin-top: 50px;'>
            <h1>⚠️ Falta de Assinatura</h1>
            <p>A autenticação foi um sucesso, mas a conta original de <b>{$nome_funcionario}</b> não possui uma foto de assinatura cadastrada no banco de dados.</p>
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

$ip_local = gethostbyname(gethostname()); 
// O path local foi ajustado para refletir o diretório do projeto
$url_validacao = "http://" . $ip_local . "/MarIA_v5.3/validar.php?protocolo=" . urlencode($id_protocolo);

use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\Common\EccLevel;

/**
 * Configuração de Geração do QR Code.
 * Passamos o array diretamente para o construtor do QRCode para evitar falsos positivos
 * de tipagem em algumas extensões de IDE (ex: Intelephense) que não lidam bem com Union Types do PHP 8.
 */
$qrcode = new QRCode([
    'outputInterface' => QRMarkupSVG::class,
    'eccLevel'        => EccLevel::L,
]);
$qrcode_image_data = $qrcode->render($url_validacao);

/**
 * Cria um hash único da validação para o usuário ler.
 * // TODO: Usar um campo como UUID ou chave encriptada forte (ex: Sodium) para representar a chave de autenticidade, em vez de um MD5 simples.
 */
$chave_autenticidade = md5($id_protocolo . $ra);

$htmlDoDocumento = "
<!DOCTYPE html>
<html>
<head>
    <style>
        @page {
            margin: 50px 50px 120px 50px; /* Define margem inferior generosa para caber o QR Code */
        }
        body { font-family: Helvetica, Arial, sans-serif; line-height: 1.6; color: #333; }
        .cabecalho { text-align: center; margin-bottom: 40px; border-bottom: 2px solid #b30000; padding-bottom: 20px; }
        .titulo { text-decoration: underline; font-size: 18px; margin-bottom: 30px; text-align: center; font-weight: bold;}
        .texto { font-size: 16px; text-align: justify; margin-bottom: 60px; line-height: 2; padding: 0 30px; }
        .assinatura { text-align: center; margin-top: 80px; }
        .linha-assinatura { border-top: 1px solid #000; width: 60%; margin: 0 auto; margin-top: 5px; padding-top: 5px; }
        
        /* Estilo fixo para o rodapé em PDF */
        .footer-validacao { 
            position: fixed; 
            bottom: -90px; /* Joga a div para dentro da margem inferior do @page */
            left: 0;
            right: 0;
            width: 100%; 
            border-top: 1px solid #cbd5e1; 
            padding-top: 15px;
        }
        .tabela-rodape { width: 100%; border-collapse: collapse; }
        .qr-code-img { width: 85px; height: 85px; }
        .texto-validacao { font-size: 11px; color: #64748b; padding-left: 15px; vertical-align: middle; }
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
            {$mensagem_delegacao}
        </div>
    </div>
    
    <div class='footer-validacao'>
        <table class='tabela-rodape'>
            <tr>
                <td style='width: 90px; vertical-align: middle;'>
                    <img src='{$qrcode_image_data}' class='qr-code-img' />
                </td>
                <td class='texto-validacao'>
                    <strong>Chave de Autenticidade Eletrônica:</strong><br>
                    <code>{$chave_autenticidade}</code><br><br>
                    A autenticidade deste documento pode ser verificada gratuitamente apontando a câmera do seu celular para o QR Code ao lado ou acessando o link de validação do ecossistema MarIA.
                </td>
            </tr>
        </table>
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

// 4. Salvar PDF fisicamente no servidor
$nomeArquivo = str_replace([' ', '/', '\\'], '_', $titulo) . '_' . $ra . '_' . time() . '.pdf';
$diretorio_alvo = __DIR__ . '/../../uploads/pdf/'; 

if (!file_exists($diretorio_alvo)) {
    mkdir($diretorio_alvo, 0777, true);
}

$caminho_completo_local = $diretorio_alvo . $nomeArquivo;

$output = $dompdf->output();
file_put_contents($caminho_completo_local, $output);

// 5. Gravar histórico no banco
if ($id_protocolo) {
    try {
        // ==========================================
        // TOQUE DE MESTRE: ESTRUTURAÇÃO DO LOG DO RADAR
        // ==========================================
        
        // Captura o IP local de forma segura
        $ip_origem = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        if ($ip_origem === '::1') { $ip_origem = '127.0.0.1'; } 

        /**
         * Log de Auditoria JSON (Radar)
         */
        $log_auditoria = [
            "evento" => $autenticado_delegado ? "assinatura_delegada" : "assinatura_propria",
            "documento_protocolo" => $id_protocolo,
            "autorizador_id" => $autenticado_delegado ? $dados_delegacao['id_origem'] : $usuarioBD['id'],
            "operador_logado_id" => $usuarioBD['id'],
            "timestamp" => date('Y-m-d H:i:s'),
            "ip_origem" => $ip_origem
        ];

        $json_radar = json_encode($log_auditoria, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        // Grava o PDF e o Log de Auditoria associado à ele no repositório de histórico!
        $sqlInsert = "INSERT INTO documentos_gerados (id_protocolo, nome_arquivo, caminho_local, id_usuario_gerador, log_radar) 
                      VALUES (:id_protocolo, :nome_arquivo, :caminho_local, :id_usuario, :json_radar)";
        $stmtInsert = $pdo->prepare($sqlInsert);
        $stmtInsert->execute([
            ':id_protocolo' => $id_protocolo,
            ':nome_arquivo' => $nomeArquivo,
            ':caminho_local' => realpath($diretorio_alvo),
            ':id_usuario' => $usuarioBD['id'],
            ':json_radar' => $json_radar
        ]);
        
        // Marca o protocolo como Concluído
        $stmtUpd = $pdo->prepare("UPDATE protocolos SET status = 'Concluído' WHERE id_protocolo = ?");
        $stmtUpd->execute([$id_protocolo]);

        /**
         * Disparo Assíncrono de E-mail (Notificação via Mailtrap)
         * Recupera o e-mail do aluno associado ao RA para notificação.
         * 
         * // FIXME: [Design] A responsabilidade de enviar e-mail não deveria estar fortemente acoplada
         * na Controller. O ideal seria disparar um Evento (ex: DocumentoGeradoEvent) e ter um Listener
         * assíncrono (RabbitMQ/Redis) cuidando do envio.
         */
        $stmtAluno = $pdo->prepare("SELECT nome, email FROM alunos WHERE ra = ?");
        $stmtAluno->execute([$ra]);
        $dadosAluno = $stmtAluno->fetch();

        // Mitigação de erro: Apenas dispara se o aluno possuir e-mail cadastrado
        if ($dadosAluno && !empty($dadosAluno['email'])) {
            try {
                require_once __DIR__ . '/../use_cases/EnviarEmailLocalUseCase.php';
                $enviarEmail = new EnviarEmailLocalUseCase();
                
                $enviarEmail->executar($dadosAluno['email'], $dadosAluno['nome'], $caminho_completo_local);
            } catch (Exception $e) {
                // Tratamento passivo: Se o SMTP/Mailtrap cair em ambiente local, não travamos o download
                // do documento. Logamos o incidente para análise posterior.
                error_log("Falha não-bloqueante no envio de notificação (Mailtrap): " . $e->getMessage()); 
            }
        }

    } catch (PDOException $e) {
        // Log ou ignore se a tabela não estiver criada
    }
}

// 6. Exibir no navegador
$dompdf->stream($nomeArquivo, array("Attachment" => false));
?>
