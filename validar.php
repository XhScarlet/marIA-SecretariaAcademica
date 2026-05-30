<?php
/**
 * Script de Validação Pública de Documentos Acadêmicos.
 *
 * Atua como um Front Controller para a validação, capturando a requisição HTTP,
 * invocando a camada de caso de uso (Use Case) e retornando a renderização na View.
 * 
 * @category   Security
 * @package    MarIA_Virtual_Assistant
 * @subpackage Validators
 * @author     Gabriela Cardoso dos Santos (MarIA Architecture)
 */
require_once 'api/config.php';
require_once 'api/use_cases/ValidateDocumentUseCase.php';

$protocoloDigitado = $_POST['protocolo'] ?? '';
$resultado = null;
$buscou = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($protocoloDigitado)) {
    $buscou = true;
    $useCase = new ValidateDocumentUseCase($pdo);
    $resultado = $useCase->execute($protocoloDigitado);
}

// Renderiza a View
require_once 'api/views/validar_view.php';
?>
