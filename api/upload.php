<?php
header('Content-Type: application/json; charset=utf-8');
include 'config.php';

// Cria a pasta de uploads automaticamente se ela não existir
$diretorioDestino = 'uploads/';
if (!is_dir($diretorioDestino)) {
    mkdir($diretorioDestino, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['comprovante'])) {
    $arquivo = $_FILES['comprovante'];
    
    // Extensões permitidas por segurança
    $extensoesPermitidas = ['pdf', 'png', 'jpg', 'jpeg'];
    $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));

    if (!in_array($extensao, $extensoesPermitidas)) {
        echo json_encode(['erro' => 'Formato inválido! Envie PDF ou Imagem.']);
        exit;
    }

    // Gera um nome único para o arquivo não sobrescrever outro
    $novoNome = uniqid('doc_', true) . '.' . $extensao;
    $caminhoCompleto = $diretorioDestino . $novoNome;

    if (move_uploaded_file($arquivo['tmp_name'], $caminhoCompleto)) {
        // Retorna o nome do arquivo para o JavaScript guardar
        echo json_encode(['sucesso' => true, 'nome_arquivo' => $novoNome]);
    } else {
        echo json_encode(['erro' => 'Erro ao salvar o arquivo no servidor.']);
    }
} else {
    echo json_encode(['erro' => 'Nenhum arquivo enviado.']);
}
?>
