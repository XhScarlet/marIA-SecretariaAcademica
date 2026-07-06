<?php
/**
 * Script de Conexão com o Banco de Dados (Singleton Pattern e Detecção de Ambiente)
 *
 * @author Engenheiro de Software Sênior
 * @version 1.0.0
 * @description Centraliza a conexão PDO com detecção automática do ambiente (Dev/Prod).
 * Evita repetição de código (DRY) e expõe um ponto único de falha tratado para a infraestrutura de dados.
 */

// Verifica em qual domínio a aplicação está rodando.
// $_SERVER['HTTP_HOST'] contém o header 'Host' da requisição atual.
// Atualizado para contemplar acessos via IP de rede local (ex: celulares na mesma Wi-Fi escaneando o QR Code).
$httpHost = $_SERVER['HTTP_HOST'] ?? '';
$isLocalhost = (
    strpos($httpHost, 'localhost') !== false || 
    strpos($httpHost, '127.0.0.1') !== false || 
    strpos($httpHost, '192.168.') === 0 || // Rede local padrão (Roteadores)
    strpos($httpHost, '10.') === 0 ||      // Rede local classe A
    preg_match('/^172\.(1[6-9]|2[0-9]|3[0-1])\./', $httpHost) // Rede local classe B
);

if ($isLocalhost) {
    // Credenciais do ambiente de Desenvolvimento (Local)
    $host   = 'localhost';
    $db     = 'fatec_secretaria';
    $user   = 'root';
    $pass   = '';
} else {
    // Credenciais do ambiente de Produção (InfinityFree)
    // TODO: Mover credenciais de produção para variáveis de ambiente (.env) para evitar hardcoding em repositórios.
    $host   = 'sql300.infinityfree.com'; // Altere para o host fornecido no painel do InfinityFree (ex: sql312.infinityfree.com)
    $db     = 'if0_41727183_fatec_secretaria'; // O nome do banco geralmente leva o prefixo do usuário
    $user   = 'if0_41727183'; // Seu usuário no painel do InfinityFree
    $pass   = 'DchPIYPNPSg'; // Substitua pela senha correta do banco
}

/**
 * Define o DSN (Data Source Name) para a conexão PDO.
 * O charset utf8mb4 é forçado para garantir suporte completo a caracteres Unicode (como emojis).
 */
$dsn = "mysql:host={$host};dbname={$db};charset=utf8mb4";

/**
 * Configurações avançadas do PDO para resiliência e estabilidade.
 * 
 * ATTR_ERRMODE: Lança exceções (PDOException) em caso de erro, facilitando o rastreamento via Try/Catch.
 * ATTR_DEFAULT_FETCH_MODE: Define o retorno padrão como array associativo, otimizando o consumo de memória.
 * ATTR_EMULATE_PREPARES: Desativa a emulação de prepared statements, transferindo a responsabilidade para o SGDB (maior segurança).
 */
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // Instancia o objeto PDO com as configurações definidas.
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // Em ambientes de produção, NUNCA expomos a stack trace completa ou o erro exato do banco de dados para os usuários finais.
    // O erro real ($e->getMessage()) deve ser registrado em um arquivo de log do servidor para auditoria e debug.
    
    // FIXME: Implementar sistema de log (ex: Monolog) para salvar o erro real ($e->getMessage()) no servidor de forma segura.
    
    // Verifica se a requisição veio da API (Fetch/AJAX pedindo JSON ou enviando JSON)
    $isJsonRequest = (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) || 
                     (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false);

    if ($isJsonRequest) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            "sucesso" => false, 
            "erro" => "Falha na conexão com o banco de dados.",
            "respostas" => ["Poxa, colega! Estou sem conexão com o banco de dados no momento. Tente novamente mais tarde."]
        ]);
        exit;
    }
    
    // Exibe uma mensagem amigável e interrompe a execução do script para páginas web normais.
    die("<h3>Ops! Ocorreu um problema de comunicação com nosso banco de dados. Nossa equipe técnica já foi notificada. Tente novamente mais tarde.</h3>");
}
?>
