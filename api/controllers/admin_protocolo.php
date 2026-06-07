<?php
session_start();
if (!isset($_SESSION['admin_logado'])) { header('Location: ../login.php'); exit; }

include '../config.php';
require_once '../models/Protocolo.php';



$protocoloModel = new Protocolo($pdo);

// --- AÇÕES CRUD ---
// Atualizar Status
if (isset($_POST['novo_status']) && isset($_POST['id'])) {
    $protocoloModel->atualizarStatus($_POST['id'], $_POST['novo_status']);
    header('Location: admin_protocolo.php');
    exit;
}

// Deletar Protocolo
if (isset($_GET['del'])) {
    $protocoloModel->excluir($_GET['del']);
    header('Location: admin_protocolo.php');
    exit;
}

// Lógica de Filtro
$busca = $_GET['busca'] ?? '';
$protocolos = $protocoloModel->listar($busca);

require '../views/admin_protocolo_view.php';
