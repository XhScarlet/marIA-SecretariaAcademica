<?php
session_start();
include '../config.php';
require_once '../use_cases/ListarDocumentosUseCase.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../index.php");
    exit;
}

$busca = $_GET['busca'] ?? '';

$useCase = new ListarDocumentosUseCase($pdo);
$documentos = $useCase->executar($busca);

include '../views/historico_documentos_view.php';
?>
