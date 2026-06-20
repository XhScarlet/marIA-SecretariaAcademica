<?php
session_start();
if (!isset($_SESSION['admin_logado'])) { header('Location: ../login.php'); exit; }

include '../config.php';
require_once '../models/Aluno.php';

$alunoModel = new Aluno($pdo);

// --- AÇÕES CRUD ---
// Concentramos as operações de estado dentro deste bloco para evitar 
// reprocessamento acidental durante reloads (PRG Pattern).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    if ($_POST['acao'] === 'salvar') {
        $ra_original = $_POST['ra_original'] ?? '';
        
        // Aplicação de validação e sanitização básica (Defense in Depth)
        $ra = filter_var($_POST['ra'], FILTER_SANITIZE_STRING);
        $nome = filter_var($_POST['nome'], FILTER_SANITIZE_STRING);
        $curso = filter_var($_POST['curso'], FILTER_SANITIZE_STRING);
        $semestre = filter_var($_POST['semestre'], FILTER_SANITIZE_NUMBER_INT);
        $turno = filter_var($_POST['turno'], FILTER_SANITIZE_STRING);
        $status = filter_var($_POST['status'], FILTER_SANITIZE_STRING);
        $email = isset($_POST['email']) ? filter_var($_POST['email'], FILTER_SANITIZE_EMAIL) : null;

        if ($ra && $nome && $curso && $semestre && $turno && $status) {
            $alunoModel->salvar($ra_original, $ra, $nome, $curso, $semestre, $turno, $status, $email);
        }
        
        header("Location: admin_alunos.php");
        exit;
    } elseif ($_POST['acao'] === 'excluir') {
        // Exclusão agora é feita via POST, mitigando risco de CSRF via tags <img> ou pre-fetching de browser.
        $ra_exclusao = filter_var($_POST['ra_exclusao'], FILTER_SANITIZE_STRING);
        if ($ra_exclusao) {
            $alunoModel->excluir($ra_exclusao);
        }
        
        header("Location: admin_alunos.php");
        exit;
    }
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
