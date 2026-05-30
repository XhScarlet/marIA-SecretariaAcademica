<?php
/**
 * ValidateDocumentUseCase
 *
 * Caso de uso responsável por isolar a regra de negócio da validação de autenticidade 
 * de um protocolo acadêmico contra o banco de dados.
 */
class ValidateDocumentUseCase {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Executa a validação do protocolo.
     *
     * @param string $protocolo O código verificador a ser validado.
     * @return array|false Retorna os dados do documento se autêntico, ou false caso contrário.
     */
    public function execute($protocolo) {
        if (empty($protocolo) || !$this->pdo) {
            return false;
        }

        $stmt = $this->pdo->prepare("
            SELECT p.*, a.nome, a.curso 
            FROM protocolos p 
            JOIN alunos a ON p.ra_aluno = a.ra 
            WHERE p.id_protocolo = ?
        ");
        
        $stmt->execute([trim($protocolo)]);
        return $stmt->fetch();
    }
}
?>
