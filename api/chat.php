<?php
/**
 * Controller Principal do Assistente Virtual MarIA (Chatbot).
 *
 * Responsável por atuar como Front Controller, inicializando as configurações globais
 * e delegando a requisição para a camada de controle correta (ChatController).
 *
 * @category   Controller
 * @package    MarIA_Virtual_Assistant
 * @author     Gabriela Cardoso dos Santos (MarIA Architecture)
 */
set_time_limit(60);
// Define que este arquivo vai responder em formato JSON para o Frontend
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(E_ALL);

ob_clean();

// 1. Inicializa o banco de dados e segredos da aplicação
require_once 'config.php';

// 2. Importa o controlador encapsulado
require_once 'controllers/ChatController.php';

// 3. Injeta as dependências e dispara a execução principal
$chatController = new ChatController($pdo);
$chatController->handleRequest();
?>