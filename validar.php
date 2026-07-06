<?php
/**
 * Arquivo: validar.php
 *
 * @author Engenheiro de Software Sênior
 * @version 1.0.0
 * @description Ponto de entrada público para validação de autenticidade de documentos gerados via QR Code.
 * Delega o roteamento e a busca no banco de dados, mantendo a camada de apresentação isolada.
 */

// Requer o arquivo de configuração e conexão (injetando a instância do PDO na variável global $pdo)
require_once __DIR__ . '/api/config.php';

// ==============================================================
// REGRA DE NEGÓCIO: RECUPERAÇÃO E VALIDAÇÃO DE AUTENTICIDADE
// ==============================================================
$protocolo_encontrado = false;
$dados_documento = null;
$chave_autenticidade = null;

if (isset($_GET['protocolo']) && !empty(trim($_GET['protocolo']))) {
    // Sanitização básica da entrada
    $id_protocolo = trim($_GET['protocolo']);

    /**
     * Busca as informações consolidadas do documento, unindo dados estruturais
     * do protocolo e do aluno (Denormalização sob demanda via JOIN).
     * 
     * FIXME: Tratamento de exceções (try/catch) - Caso haja instabilidade na conexão
     * com o banco de dados durante a execução dessa query, a tela irá expor um erro fatal. 
     * É recomendado o encapsulamento dentro de um try/catch para renderizar uma página 500 amigável.
     */
    $sql = "SELECT dg.*, p.tipo_servico, p.status, a.nome AS nome_aluno, a.ra 
            FROM documentos_gerados dg
            JOIN protocolos p ON dg.id_protocolo = p.id_protocolo
            JOIN alunos a ON p.ra_aluno = a.ra
            WHERE dg.id_protocolo = :id LIMIT 1";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id_protocolo]);
    $dados_documento = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($dados_documento) {
        $protocolo_encontrado = true;
        
        // Recálculo da chave de autenticidade (usando a mesma lógica aplicada no gerador PDF).
        // Isso permite a verificação visual da chave pelo leitor e pelo fiscal.
        $chave_autenticidade = md5($dados_documento['id_protocolo'] . $dados_documento['ra']);
    }
}

/**
 * Delegação da renderização HTML para a View.
 * A View irá utilizar o estado das variáveis declaradas acima ($protocolo_encontrado, $dados_documento, $chave_autenticidade)
 * para desenhar a interface dinamicamente.
 */
require_once __DIR__ . '/api/views/validar_view.php';
