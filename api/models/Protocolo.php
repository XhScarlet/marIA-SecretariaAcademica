<?php
class Protocolo {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function listar($busca = '') {
        $sql = "SELECT p.*, a.nome FROM protocolos p 
                JOIN alunos a ON p.ra_aluno = a.ra 
                WHERE p.id_protocolo LIKE :busca OR p.ra_aluno LIKE :busca 
                ORDER BY p.data_abertura DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['busca' => "%$busca%"]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function atualizarStatus($id, $novo_status) {
        $stmt = $this->pdo->prepare("UPDATE protocolos SET status = ? WHERE id_protocolo = ?");
        return $stmt->execute([$novo_status, $id]);
    }

    public function excluir($id) {
        $stmt = $this->pdo->prepare("DELETE FROM protocolos WHERE id_protocolo = ?");
        return $stmt->execute([$id]);
    }
}
