<?php
require_once __DIR__ . '/../models/DelegacaoModel.php';

/**
 * Use Case: Revogar Delegação de Assinatura
 * 
 * Orquestra a revogação de uma senha temporária ativa, garantindo
 * que apenas o dono (autor) possa realizar essa ação.
 * 
 * @category UseCase
 */
class RevogarDelegacaoUseCase {
    /**
     * @var DelegacaoModel
     */
    private $delegacaoModel;

    public function __construct(DelegacaoModel $delegacaoModel) {
        $this->delegacaoModel = $delegacaoModel;
    }

    /**
     * Executa a revogação de uma delegação.
     *
     * @param int $id_delegacao ID da delegação na tabela.
     * @param string $id_origem ID do usuário que está tentando revogar (para segurança).
     * @return bool True se revogado com sucesso.
     * @throws Exception Se não for possível revogar.
     */
    public function execute(int $id_delegacao, string $id_origem): bool {
        $sucesso = $this->delegacaoModel->revogar($id_delegacao, $id_origem);
        
        if (!$sucesso) {
            throw new Exception("Não foi possível revogar a delegação. Você não tem permissão ou ela não existe.");
        }
        
        return true;
    }
}
