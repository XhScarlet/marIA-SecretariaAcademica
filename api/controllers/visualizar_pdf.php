<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    die("Acesso negado.");
}

if (isset($_GET['arquivo'])) {
    $nome_arquivo = basename($_GET['arquivo']);
    $caminho_completo = __DIR__ . '/../../uploads/pdf/' . $nome_arquivo;

    if (file_exists($caminho_completo)) {
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $nome_arquivo . '"');
        header('Content-Transfer-Encoding: binary');
        header('Accept-Ranges: bytes');
        
        readfile($caminho_completo);
        exit;
    } else {
        echo "Erro: O arquivo PDF não foi encontrado no diretório local.";
    }
} else {
    echo "Erro: Nenhum arquivo especificado.";
}
?>
