<?php
class Usuario {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function listar($busca = '') {
        $sql = "SELECT id, nome, usuario, criado_em FROM usuarios_secretaria 
                WHERE nome LIKE ? OR usuario LIKE ? 
                ORDER BY nome ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["%$busca%", "%$busca%"]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function criar($nome, $usuario, $senha, $assinatura_img) {
        $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

        $stmt = $this->pdo->prepare("INSERT INTO usuarios_secretaria (id, nome, usuario, senha, assinatura_img) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([$uuid, $nome, $usuario, $senhaHash, $assinatura_img]);
    }

    public function excluir($id, $logged_in_id) {
        if ($id !== $logged_in_id) {
            try {
                $stmt = $this->pdo->prepare("DELETE FROM usuarios_secretaria WHERE id=?");
                return $stmt->execute([$id]);
            } catch (PDOException $e) {
                // Captura erro de Foreign Key constraint violation
                if ($e->getCode() == '23000') {
                    throw new Exception("Erro de Integridade: Este usuário não pode ser excluído, pois existem delegações de assinatura ou outros registros vinculados a ele.");
                }
                throw $e;
            }
        }
        return false;
    }
}
