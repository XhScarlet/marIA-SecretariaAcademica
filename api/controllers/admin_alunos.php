<?php
session_start();
if (!isset($_SESSION['admin_logado'])) { header('Location: ../login.php'); exit; }

include '../config.php';
require_once '../models/Aluno.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
} catch (PDOException $e) { die("Erro na conexão: " . $e->getMessage()); }

$alunoModel = new Aluno($pdo);

// --- AÇÕES CRUD ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    if ($_POST['acao'] == 'salvar') {
        $ra_original = $_POST['ra_original'] ?? '';
        $ra = $_POST['ra'];
        $nome = $_POST['nome'];
        $curso = $_POST['curso'];
        $semestre = $_POST['semestre'];
        $turno = $_POST['turno'];
        $status = $_POST['status'];

        $alunoModel->salvar($ra_original, $ra, $nome, $curso, $semestre, $turno, $status);
        header("Location: admin_alunos.php");
        exit;
    }
}

// Excluir
if (isset($_GET['del'])) {
    $alunoModel->excluir($_GET['del']);
    header("Location: admin_alunos.php");
    exit;
}

// Lógica de Filtro
$busca = $_GET['busca'] ?? '';
$alunos = $alunoModel->listar($busca);

// Se for editar, pega os dados
$editData = null;
if (isset($_GET['edit'])) {
    $editData = $alunoModel->buscarPorRa($_GET['edit']);
}

require '../views/admin_alunos_view.php';
