<?php

/**
 * Representa o modelo de dados para a entidade Aluno.
 *
 * Encapsula as operações de banco de dados relacionadas aos alunos,
 * garantindo que a regra de negócio da aplicação esteja isolada da camada de controle.
 */
class Aluno {
    /**
     * @var PDO Conexão ativa com o banco de dados.
     */
    private $pdo;

    /**
     * Construtor da classe Aluno.
     *
     * @param PDO $pdo Instância de conexão PDO.
     */
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Lista alunos no banco de dados, com opção de filtro por nome ou RA.
     *
     * Utilizamos a busca com operador LIKE para permitir pesquisas parciais,
     * o que melhora a experiência do usuário ao não exigir dados exatos no momento do filtro.
     *
     * @param string $busca Termo de busca opcional.
     * @return array Lista associativa contendo os dados dos alunos encontrados.
     */
    public function listar($busca = '') {
        // FIXME: Paginação ausente. Para tabelas volumosas, carregar todos os registros de uma vez 
        // causa alto consumo de memória e latência excessiva. Sugere-se implementar LIMIT e OFFSET.
        $sql = "SELECT * FROM alunos WHERE nome LIKE ? OR ra LIKE ? ORDER BY nome ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["%$busca%", "%$busca%"]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca os dados de um aluno específico através do seu Registro Acadêmico (RA).
     *
     * @param string $ra Registro Acadêmico do aluno (Chave Primária).
     * @return array|false Retorna o array associativo do aluno ou false caso não encontre.
     */
    public function buscarPorRa($ra) {
        $stmt = $this->pdo->prepare("SELECT * FROM alunos WHERE ra=?");
        $stmt->execute([$ra]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Insere um novo aluno ou atualiza um registro existente.
     *
     * A presença do $ra_original atua como um discriminador (flag) de operação. 
     * Se existir, é um UPDATE (pois o RA pode ter sido alterado no form), senão, é INSERT.
     *
     * @param string $ra_original O RA original do aluno antes da edição (vazio se for novo cadastro).
     * @param string $ra Novo (ou mesmo) Registro Acadêmico.
     * @param string $nome Nome completo do aluno.
     * @param string $curso Sigla ou nome do curso.
     * @param int|string $semestre Semestre atual.
     * @param string $turno Turno das aulas.
     * @param string $status Status de matrícula (Ativo/Inativo).
     * @param string|null $email Endereço de e-mail do aluno (opcional no banco, permite nulo).
     * @return bool Retorna true em caso de sucesso ou false em caso de falha.
     */
    public function salvar($ra_original, $ra, $nome, $curso, $semestre, $turno, $status, $email = null) {
        // TODO: Tratamento de exceções (try/catch). Atualmente, violações de constraint (ex: RA duplicado)
        // irão gerar um Fatal Error na camada de visualização se não forem tratadas.
        if ($ra_original) {
            // FIXME: A alteração de Chaves Primárias (RA) pode quebrar a integridade referencial 
            // se o banco não estiver configurado com "ON UPDATE CASCADE" para as Foreign Keys.
            $stmt = $this->pdo->prepare("UPDATE alunos SET ra=?, nome=?, curso=?, semestre=?, turno=?, status=?, email=? WHERE ra=?");
            return $stmt->execute([$ra, $nome, $curso, $semestre, $turno, $status, $email, $ra_original]);
        } else {
            $stmt = $this->pdo->prepare("INSERT INTO alunos (ra, nome, curso, semestre, turno, status, email) VALUES (?, ?, ?, ?, ?, ?, ?)");
            return $stmt->execute([$ra, $nome, $curso, $semestre, $turno, $status, $email]);
        }
    }

    /**
     * Remove o registro de um aluno do banco de dados de forma definitiva.
     *
     * @param string $ra Registro Acadêmico do aluno a ser excluído.
     * @return bool Retorna true em caso de sucesso ou false em caso de falha.
     */
    public function excluir($ra) {
        // FIXME: Hard Delete (DELETE FROM). Em sistemas corporativos ou acadêmicos, 
        // recomenda-se o uso de Soft Delete (ex: update status = 'excluido') para manter 
        // o histórico e evitar quebra de relatórios acadêmicos.
        $stmt = $this->pdo->prepare("DELETE FROM alunos WHERE ra=?");
        return $stmt->execute([$ra]);
    }
}
