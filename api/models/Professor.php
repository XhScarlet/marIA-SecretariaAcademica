<?php
class Professor {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function listar($busca = '') {
        $sql = "SELECT * FROM professores_disciplinas WHERE nome_materia LIKE ? OR professor LIKE ? ORDER BY nome_materia ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["%$busca%", "%$busca%"]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarPorId($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM professores_disciplinas WHERE id=?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function salvar($id, $materia, $prof, $email, $ementa) {
        if ($id) {
            $stmt = $this->pdo->prepare("UPDATE professores_disciplinas SET nome_materia=?, professor=?, email_prof=?, ementa_url=? WHERE id=?");
            return $stmt->execute([$materia, $prof, $email, $ementa, $id]);
        } else {
            $stmt = $this->pdo->prepare("INSERT INTO professores_disciplinas (nome_materia, professor, email_prof, ementa_url) VALUES (?, ?, ?, ?)");
            return $stmt->execute([$materia, $prof, $email, $ementa]);
        }
    }

    public function excluir($id) {
        $stmt = $this->pdo->prepare("DELETE FROM professores_disciplinas WHERE id=?");
        return $stmt->execute([$id]);
    }
}
