<?php
// 1. Conexão com o Banco
$host = 'localhost';
$db = 'fatec_secretaria';
$user = 'root';
$pass = ''; // Senha do banco em produção
$charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC];

$pdo = null;
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) { 
    // Continua sem banco de dados, não quebra o JSON
}

// 2. Variáveis de Configuração e Segredos
// CUIDADO: Crie um arquivo api/config.php real na sua máquina baseado neste.
// NUNCA comite o config.php com senhas reais!

define('OPENROUTER_API_KEY', 'COLOQUE_SUA_CHAVE_AQUI');
define('MODELO_IA_PRINCIPAL', 'nvidia/nemotron-3-nano-omni-30b-a3b-reasoning:free');
define('SECRET_HASH_KEY', 'SUA_CHAVE_SECRETA_AQUI');

?>
