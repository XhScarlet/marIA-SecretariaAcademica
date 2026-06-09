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
    if ($_POST['acao'] == 'salvar') {
        $ra_original = $_POST['ra_original'] ?? '';
        $ra = $_POST['ra'];
        $nome = $_POST['nome'];
        $curso = $_POST['curso'];
        $semestre = $_POST['semestre'];
        $turno = $_POST['turno'];
        $status = $_POST['status'];
        
        // Operador null coalescing (??) é usado para garantir que o envio parcial
        // não quebre a instrução, injetando null se a chave não estiver no POST.
        $email = $_POST['email'] ?? null;

        // TODO: Faltam validações na camada de Controle antes de acionar o Model. 
        // Atualmente confia-se cegamente no input do HTML, o que fere o princípio de 
        // segurança de "Defense in Depth". Os dados deveriam ser validados e sanitizados aqui.
        $alunoModel->salvar($ra_original, $ra, $nome, $curso, $semestre, $turno, $status, $email);
        
        header("Location: admin_alunos.php");
        exit;
    }
}

// Excluir
// A exclusão está sendo feita via método GET, o que é um risco de segurança.
// FIXME: A exclusão deve ser disparada via POST ou DELETE (usando fetch/ajax)
// para evitar exclusões acidentais via pre-fetching de navegadores ou CSRF.
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
