<?php
// Script de Diagnóstico para o InfinityFree
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🩺 Diagnóstico do Sistema MarIA</h2>";

echo "<h3>1. Checando Arquivos de Conexão:</h3>";
if (!file_exists('api/conexao.php')) {
    echo "<b style='color:red'>❌ ERRO: O arquivo 'api/conexao.php' NÃO EXISTE no servidor!</b><br>Você esqueceu de subir ele para a pasta api/.";
} else {
    echo "<b style='color:green'>✅ Arquivo 'api/conexao.php' encontrado.</b><br>";
    
    // Tenta conectar
    try {
        require_once 'api/conexao.php';
        if (isset($pdo) && $pdo instanceof PDO) {
            echo "<b style='color:green'>✅ Conexão com o banco de dados realizada com SUCESSO!</b><br>";
        } else {
            echo "<b style='color:orange'>⚠️ Conexão falhou silenciosamente. Verifique a senha no conexao.php.</b><br>";
        }
    } catch (Exception $e) {
        echo "<b style='color:red'>❌ ERRO NO BANCO: " . $e->getMessage() . "</b><br>";
    }
}

echo "<h3>2. Checando Suporte a IA (cURL):</h3>";
if (function_exists('curl_init')) {
    echo "<b style='color:green'>✅ O servidor suporta chamadas externas (cURL ativado). A IA pode funcionar.</b><br>";
} else {
    echo "<b style='color:red'>❌ ERRO: A função cURL está DESATIVADA neste servidor. A IA não conseguirá se comunicar com a OpenRouter.</b><br>";
}

echo "<h3>3. Checando Configuração de Diretório:</h3>";
echo "O diretório atual de execução é: <b>" . __DIR__ . "</b><br>";
?>
