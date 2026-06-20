<?php
/**
 * Model Responsável pela Tabela 'delegacoes_assinatura'.
 *
 * Aplica os conceitos de Clean Architecture isolando as regras de 
 * acesso a dados (Infraestrutura/Database) das regras de negócio (Use Cases).
 *
 * @category Model
 */
class DelegacaoModel {
    /**
     * @var PDO Conexão com o banco de dados.
     */
    private $pdo;

    /**
     * Construtor do Model com injeção de dependência.
     *
     * @param PDO $pdo Instância da conexão.
     */
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Cria uma nova delegação de assinatura no banco.
     *
     * @param string $id_origem ID de quem está delegando.
     * @param string $id_delegado ID de quem receberá a permissão.
     * @param string $senha_hash Hash seguro da senha (BCRYPT).
     * @param string $data_expiracao Data limite de uso (Y-m-d H:i:s).
     * @return bool Retorna true se a inserção for bem sucedida.
     * 
     * FIXME: Futuramente, seria ideal retornar o ID da delegação inserida
     * caso o sistema exija envio de notificação via mensageria/filas (RabbitMQ).
     */
    public function criar(string $id_origem, string $id_delegado, string $senha_hash, string $data_expiracao): bool {
        $sql = "INSERT INTO delegacoes_assinatura 
                (id_usuario_origem, id_usuario_delegado, senha_temporaria, data_expiracao, status) 
                VALUES (:origem, :delegado, :senha, :expiracao, 'Ativo')";
                
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':origem'    => $id_origem,
            ':delegado'  => $id_delegado,
            ':senha'     => $senha_hash,
            ':expiracao' => $data_expiracao
        ]);
    }

    /**
     * Lista as delegações blindando o escopo (Multi-tenancy por usuário logado).
     * 
     * A regra (OR) garante que o usuário só veja o que ele próprio criou, 
     * ou o que delegaram a ele (desde que ainda esteja 'Ativo' e dentro do prazo).
     *
     * @param string $id_usuario_logado O ID do usuário na sessão atual.
     * @return array Retorna a lista de registros.
     */
    public function listarPorUsuario(string $id_usuario_logado): array {
        $sql = "SELECT d.id, d.token_criado_em, d.data_expiracao, d.status,
                       u_origem.nome AS quem_delegou, 
                       u_delegado.nome AS quem_recebeu
                FROM delegacoes_assinatura d
                JOIN usuarios_secretaria u_origem ON d.id_usuario_origem = u_origem.id
                JOIN usuarios_secretaria u_delegado ON d.id_usuario_delegado = u_delegado.id
                WHERE 
                    -- O usuário logado é o criador da delegação
                    (d.id_usuario_origem = :id_logado_1)
                    OR 
                    -- O usuário logado recebeu a delegação, está Ativa e não expirou
                    (d.id_usuario_delegado = :id_logado_2 AND d.status = 'Ativo' AND d.data_expiracao > NOW())
                ORDER BY d.token_criado_em DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id_logado_1' => $id_usuario_logado,
            ':id_logado_2' => $id_usuario_logado
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Revoga manualmente uma delegação de assinatura.
     *
     * @param int $id_delegacao O ID do registro na tabela delegacoes_assinatura.
     * @param string $id_origem O ID de quem gerou a delegação para garantir segurança.
     * @return bool
     */
    public function revogar(int $id_delegacao, string $id_origem): bool {
        $sql = "UPDATE delegacoes_assinatura SET status = 'Revogado' 
                WHERE id = :id AND id_usuario_origem = :origem";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id' => $id_delegacao,
            ':origem' => $id_origem
        ]);
    }

    /**
     * Busca a senha temporária (criptografada) garantindo que pertence ao delegado.
     *
     * @param int $id_delegacao
     * @param string $id_delegado
     * @return string|null Retorna a string criptografada ou null se não encontrada/não autorizada.
     */
    public function buscarSenhaCriptografada(int $id_delegacao, string $id_delegado): ?string {
        $sql = "SELECT senha_temporaria FROM delegacoes_assinatura 
                WHERE id = :id AND id_usuario_delegado = :delegado AND status = 'Ativo' AND data_expiracao > NOW()";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $id_delegacao,
            ':delegado' => $id_delegado
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['senha_temporaria'] : null;
    }

    /**
     * Verifica as credenciais corporativas do usuário.
     * Necessário para a feature de Visualizar Senha do Delegado.
     *
     * @param string $usuario Login (ex: admin)
     * @param string $senha_limpa Senha digitada no modal
     * @return string|null Retorna o ID do usuário se as credenciais baterem, ou null caso falhe.
     */
    public function verificarCredenciaisUsuario(string $usuario, string $senha_limpa): ?string {
        $stmt = $this->pdo->prepare("SELECT id, senha FROM usuarios_secretaria WHERE usuario = ?");
        $stmt->execute([$usuario]);
        $usuarioBD = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuarioBD && password_verify($senha_limpa, $usuarioBD['senha'])) {
            return $usuarioBD['id'];
        }
        return null;
    }

    /**
     * Busca todas as delegações ativas de um usuário que está operando como delegado.
     * Retorna a senha temporária e os dados do usuário que autorizou a delegação.
     *
     * @param string $id_delegado O ID do funcionário que vai realizar a assinatura
     * @return array Lista contendo id_origem, nome_origem, assinatura_img e senha_temporaria
     */
    public function buscarDelegacoesAtivasPorDelegado(string $id_delegado): array {
        $sql = "SELECT d.senha_temporaria, d.id_usuario_origem, 
                       u_origem.nome AS nome_origem, u_origem.assinatura_img 
                FROM delegacoes_assinatura d
                JOIN usuarios_secretaria u_origem ON d.id_usuario_origem = u_origem.id
                WHERE d.id_usuario_delegado = :delegado 
                  AND d.status = 'Ativo' 
                  AND d.data_expiracao > NOW()";
                  
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':delegado' => $id_delegado]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
