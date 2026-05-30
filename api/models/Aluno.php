<?php
class Aluno {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function listar($busca = '') {
        $sql = "SELECT * FROM alunos WHERE nome LIKE :busca OR ra LIKE :busca ORDER BY nome ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['busca' => "%$busca%"]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarPorRa($ra) {
        $stmt = $this->pdo->prepare("SELECT * FROM alunos WHERE ra=?");
        $stmt->execute([$ra]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function salvar($ra_original, $ra, $nome, $curso, $semestre, $turno, $status) {
        if ($ra_original) {
            $stmt = $this->pdo->prepare("UPDATE alunos SET ra=?, nome=?, curso=?, semestre=?, turno=?, status=? WHERE ra=?");
            return $stmt->execute([$ra, $nome, $curso, $semestre, $turno, $status, $ra_original]);
        } else {
            $stmt = $this->pdo->prepare("INSERT INTO alunos (ra, nome, curso, semestre, turno, status) VALUES (?, ?, ?, ?, ?, ?)");
            return $stmt->execute([$ra, $nome, $curso, $semestre, $turno, $status]);
        }
    }

    public function excluir($ra) {
        $stmt = $this->pdo->prepare("DELETE FROM alunos WHERE ra=?");
        return $stmt->execute([$ra]);
    }
}
