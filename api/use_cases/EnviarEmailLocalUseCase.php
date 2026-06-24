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
            $this->mail->isHTML(true);
            $this->mail->Subject = "📄 Seu documento oficial foi emitido pela Secretaria";
            $this->mail->Body    = "
            <div style='font-family: sans-serif; padding: 20px; color: #333;'>
                <h2 style='color: #0f172a;'>Olá, {$nome_aluno}!</h2>
                <p>O seu requerimento foi processado e deferido pela inteligência assistencial da <strong>MarIA</strong>.</p>
                <p>O documento oficial, devidamente assinado via delegação eletrônica segura, foi gerado e está <strong>anexado a este e-mail</strong>.</p>
                <br>
                <hr style='border: 0; border-top: 1px solid #eee;'>
                <small style='color: #999;'>Sistema de Gestão MarIA - FATEC Zona Sul (Ambiente de Testes / FETEPS)</small>
            </div>";

            $this->mail->send();
            return true;
        } catch (Exception $e) {
            // Em cenários locais, a exceção é relançada para debug e visibilidade em tela (ou log da Controller).
            throw new Exception("Falha de comunicação SMTP ou erro de anexo no UseCase: {$this->mail->ErrorInfo} | Erro original: " . $e->getMessage());
        }
    }
}
