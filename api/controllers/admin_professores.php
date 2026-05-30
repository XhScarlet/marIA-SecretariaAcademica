<?php
session_start();
if (!isset($_SESSION['admin_logado'])) { header('Location: ../login.php'); exit; }

include '../config.php';
require_once '../models/Professor.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
} catch (PDOException $e) { die("Erro na conexão: " . $e->getMessage()); }

$professorModel = new Professor($pdo);

// --- AÇÕES CRUD ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    if ($_POST['acao'] == 'salvar') {
        $id = $_POST['id'] ?? '';
        $materia = $_POST['nome_materia'];
        $prof = $_POST['professor'];
        $email = $_POST['email_prof'];
        $ementa = $_POST['ementa_url'];

        $professorModel->salvar($id, $materia, $prof, $email, $ementa);
        header("Location: admin_professores.php");
        exit;
    }
}

// Excluir
if (isset($_GET['del'])) {
    $professorModel->excluir($_GET['del']);
    header("Location: admin_professores.php");
    exit;
}

// Lógica de Filtro
$busca = $_GET['busca'] ?? '';
$professores = $professorModel->listar($busca);

// Se for editar, pega os dados
$editData = null;
if (isset($_GET['edit'])) {
    $editData = $professorModel->buscarPorId($_GET['edit']);
}

require '../views/admin_professores_view.php';
