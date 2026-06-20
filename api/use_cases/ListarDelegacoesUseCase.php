<?php
require_once __DIR__ . '/../models/DelegacaoModel.php';

/**
 * Use Case: Listar Delegações de Assinatura
 * 
 * Orquestra a requisição de listagem blindada de delegações,
 * podendo aplicar transformações nos dados (formatação de data/hora) 
 * antes de enviar para a View.
 * 
 * @category UseCase
 */
class ListarDelegacoesUseCase {
    /**
     * @var DelegacaoModel
     */
    private $delegacaoModel;

    /**
     * @param DelegacaoModel $delegacaoModel
     */
    public function __construct(DelegacaoModel $delegacaoModel) {
        $this->delegacaoModel = $delegacaoModel;
    }

    /**
     * Executa a regra de listagem isolada por ID.
     *
     * @param string $id_usuario_logado
     * @return array Lista tratada para a UI.
     */
    public function execute(string $id_usuario_logado): array {
        $registros = $this->delegacaoModel->listarPorUsuario($id_usuario_logado);
        
        // Aplica transformações se necessário (Clean Architecture)
        // Por exemplo, formatar a data ou checar a diferença de horas.
        foreach ($registros as &$reg) {
            $dataExp = strtotime($reg['data_expiracao']);
            $agora = time();
            
            // Se expirou (time > dataExp) mas o banco ainda acusa 'Ativo'
            if ($agora > $dataExp && $reg['status'] === 'Ativo') {
                $reg['status_calculado'] = 'Expirado';
                $reg['status_cor'] = '#dc2626'; // Vermelho
            } elseif ($reg['status'] === 'Revogado') {
                $reg['status_calculado'] = 'Revogado';
                $reg['status_cor'] = '#94a3b8'; // Cinza
            } else {
                $reg['status_calculado'] = 'Ativo';
                $reg['status_cor'] = '#10b981'; // Verde
            }
            
            $reg['data_expiracao_formatada'] = date('d/m/Y H:i', $dataExp);
            $reg['criado_em_formatada'] = date('d/m/Y H:i', strtotime($reg['token_criado_em']));
        }
        
        return $registros;
    }
}
