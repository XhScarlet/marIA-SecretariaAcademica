<?php
class ListarDocumentosUseCase {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Executa a busca de documentos gerados no sistema.
     * Permite a filtragem por ID do Protocolo ou Nome do Aluno.
     * 
     * @param string $busca Termo de busca opcional (RA ou Nome)
     * @return array Retorna uma matriz associativa contendo os dados dos documentos
     */
    public function executar($busca = '') {
        $sql = "SELECT dg.id, dg.id_protocolo, dg.nome_arquivo, dg.gerado_em, dg.log_radar AS detalhes,
                       a.nome AS nome_aluno, p.tipo_servico, us.nome AS quem_gerou
                FROM documentos_gerados dg
                JOIN protocolos p ON dg.id_protocolo = p.id_protocolo
                JOIN alunos a ON p.ra_aluno = a.ra
                JOIN usuarios_secretaria us ON dg.id_usuario_gerador = us.id";

        $params = [];
        if (!empty($busca)) {
            $sql .= " WHERE dg.id_protocolo LIKE ? OR a.nome LIKE ?";
            $params = ["%$busca%", "%$busca%"];
        }

        $sql .= " ORDER BY dg.gerado_em DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
