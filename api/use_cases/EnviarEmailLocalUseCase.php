<?php
/**
 * Caso de Uso: Envio de E-mail Transacional no Ambiente Local (Mailtrap)
 *
 * @author Engenheiro de Software Sênior
 * @version 1.0.0
 * @description Orquestra o envio de e-mails locais para simular notificações 
 * da secretaria ao aluno, anexando o documento gerado em PDF, viabilizando testes locais e apresentações fim a fim sem disparo real.
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EnviarEmailLocalUseCase {
    
    /**
     * Instância do disparador de e-mails (PHPMailer).
     * 
     * // FIXME: Inversão de Dependência (DIP) - A instância do PHPMailer deveria ser injetada
     * pelo construtor (Dependency Injection) em vez de ser instanciada diretamente no método.
     * Isso facilitaria a criação de Mocks em testes unitários.
     * 
     * @var PHPMailer
     */
    private $mail;

    public function __construct() {
        $this->mail = new PHPMailer(true);
    }

    /**
     * Executa o fluxo de preparação, configuração de SMTP e disparo de e-mail ao discente.
     * 
     * @param string $email_aluno Endereço de e-mail de destino.
     * @param string $nome_aluno Nome completo ou de tratamento do destinatário.
     * @param string $caminho_completo_pdf Caminho absoluto no disco local (HD) onde o PDF assinado foi salvo.
     * 
     * @return bool Retorna verdadeiro se o servidor SMTP aceitar o payload do e-mail.
     * @throws Exception Dispara uma exceção caso a leitura do arquivo PDF falhe ou haja timeout no provedor SMTP.
     */
    public function executar(string $email_aluno, string $nome_aluno, string $caminho_completo_pdf): bool {
        try {
            // ==========================================
            // CONFIGURAÇÃO DO SERVIDOR DE PRODUÇÃO (GMAIL)
            // ==========================================
            require_once __DIR__ . '/../config.php';

            $this->mail->isSMTP();
            $this->mail->Host       = SMTP_HOST;
            $this->mail->SMTPAuth   = true;
            $this->mail->Username   = SMTP_USER;
            $this->mail->Password   = SMTP_PASS;
            // Configuração rigorosa de SSL exigida pelo Gmail para evitar bloqueios de segurança
            $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $this->mail->Port       = SMTP_PORT;
            $this->mail->CharSet    = 'UTF-8';

            $this->mail->setFrom('secretaria.fateczs@maria.gov', 'MarIA - FATEC Zona Sul');
            $this->mail->addAddress($email_aluno, $nome_aluno);

            // A leitura do arquivo físico é necessária porque o DomPDF o persiste no sistema de arquivos
            // antes de sua inserção no e-mail, exigindo sincronia entre a escrita do PDF e o disparo.
            if (file_exists($caminho_completo_pdf)) {
                $this->mail->addAttachment($caminho_completo_pdf, 'Documento_Oficial.pdf');
            } else {
                throw new Exception("Inconsistência de I/O: Arquivo PDF alvo do anexo não foi encontrado no path: {$caminho_completo_pdf}.");
            }

            // O markup do corpo do e-mail foi renderizado in-line por simplicidade do MVP.
            // TODO: Extrair este template HTML para um arquivo de View isolado (ex: templates/emails/notificacao.php).
            // Incorporar imagem da Logo FATEC 'assada' (background sólido) como anexo Inline (CID). 
            // O uso de uma imagem sem fundo transparente é o ÚNICO método 100% à prova de falhas contra a agressiva inversão do Modo Escuro do Gmail
            $this->mail->addEmbeddedImage(__DIR__ . '/../../avatar/logo_fatec_email.png', 'logo_fatec_cid');

            $this->mail->isHTML(true);
            $this->mail->Subject = "📄 Seu documento oficial da FATEC foi emitido! - MarIA";
            
            // =================================================================
            // TEMPLATE HTML PREMIUM DE NOTIFICAÇÃO (FATEC ZONA SUL / MarIA)
            // =================================================================
            $this->mail->Body = "
<!DOCTYPE html>
<html lang='pt-BR'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <meta name='color-scheme' content='light dark'>
    <meta name='supported-color-schemes' content='light dark'>
    <style>
        /* Previne inversão de imagens em clientes padrão (Apple Mail) */
        :root { color-scheme: light dark; supported-color-schemes: light dark; }
    </style>
</head>
<body style='margin: 0; padding: 0; background-color: #f1f5f9;'>
    <div style='background-color: #f1f5f9; padding: 30px 10px; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif;'>
        <table align='center' border='0' cellpadding='0' cellspacing='0' width='100%' style='max-width: 600px; background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 12px rgba(15, 23, 42, 0.05); overflow: hidden; border-collapse: collapse;'>
            
            <!-- CABEÇALHO (Azul Escuro Corporativo) -->
            <tr>
                <td style='background-color: #0f172a; padding: 40px 30px; text-align: center;'>
                    
                    <!-- Estrutura nativa de Tabela para Grid perfeito em qualquer cliente de e-mail (Outlook/Gmail) -->
                    <table align='center' border='0' cellpadding='0' cellspacing='0' style='margin: 0 auto;'>
                        <tr>
                            <td style='vertical-align: middle; padding-right: 15px;'>
                                <h1 style='color: #ffffff; margin: 0; font-size: 28px; font-weight: 700; letter-spacing: 0.5px;'>
                                    MarIA
                                </h1>
                            </td>
                            <td style='vertical-align: middle; background-color: #0f172a;'>
                                <!-- Logo FATEC renderizada sem bordas transparentes para evitar inversão do Gmail -->
                                <img src='cid:logo_fatec_cid' alt='Logo FATEC ZS' style='height: 26px; display: block; border: none; outline: none;'>
                            </td>
                        </tr>
                    </table>

                    <p style='color: #94a3b8; margin: 15px 0 0 0; font-size: 14px;'>Assistente Inteligente de Gestão Acadêmica</p>
                </td>
            </tr>

            <!-- CORPO DO E-MAIL -->
            <tr>
                <td style='padding: 40px 30px;'>
                    <h2 style='color: #1e293b; margin: 0 0 20px 0; font-size: 20px; font-weight: 600;'>Olá, {$nome_aluno}!</h2>
                    
                    <p style='color: #475569; font-size: 15px; line-height: 24px; margin: 0 0 16px 0;'>
                        Temos ótimas notícias! O seu requerimento acadêmico foi processado e deferido com sucesso através da secretaria eletrônica.
                    </p>
                    
                    <p style='color: #475569; font-size: 15px; line-height: 24px; margin: 0 0 30px 0;'>
                        O documento oficial foi devidamente gerado e assinado de forma digital segura via <strong>delegação eletrônica criptografada</strong>, garantindo sua total autenticidade jurídica interna.
                    </p>

                    <!-- CAIXA DE DETALHES DO DOCUMENTO -->
                    <table width='100%' style='background-color: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 30px;'>
                        <tr>
                            <td style='padding: 20px;'>
                                <table width='100%' style='font-size: 14px; color: #475569;'>
                                    <tr>
                                        <td style='padding-bottom: 8px; font-weight: 600; color: #1e293b; width: 120px;'>Estudante:</td>
                                        <td style='padding-bottom: 8px;'>{$nome_aluno}</td>
                                    </tr>
                                    <tr>
                                        <td style='padding-bottom: 8px; font-weight: 600; color: #1e293b;'>Instituição:</td>
                                        <td style='padding-bottom: 8px;'>FATEC Zona Sul Dom Paulo Evaristo Arns</td>
                                    </tr>
                                    <tr>
                                        <td style='font-weight: 600; color: #1e293b;'>Status:</td>
                                        <td><span style='background-color: #dcfce7; color: #15803d; padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 600;'>Emitido & Assinado</span></td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>

                    <p style='color: #475569; font-size: 14px; line-height: 22px; margin: 0;'>
                        📌 <strong>O arquivo PDF oficial já encontra-se anexado a este e-mail.</strong> Você também pode validar a integridade dele apontando a câmera do celular para o QR Code impresso no rodapé do documento.
                    </p>
                </td>
            </tr>

            <!-- RODAPÉ (FOOTER) -->
            <tr>
                <td style='background-color: #f8fafc; padding: 24px 30px; text-align: center; border-top: 1px solid #e2e8f0;'>
                    <p style='color: #94a3b8; font-size: 12px; margin: 0 0 6px 0;'>
                        Este é um disparo automático gerado pelo ecossistema de software MarIA.
                    </p>
                    <p style='color: #cbd5e1; font-size: 11px; margin: 0;'>
                        FATEC Zona Sul • Centro Paula Souza • Governo do Estado de São Paulo
                    </p>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>";

            $this->mail->send();
            return true;
        } catch (Exception $e) {
            // Em cenários locais, a exceção é relançada para debug e visibilidade em tela (ou log da Controller).
            throw new Exception("Falha de comunicação SMTP ou erro de anexo no UseCase: {$this->mail->ErrorInfo} | Erro original: " . $e->getMessage());
        }
    }
}
