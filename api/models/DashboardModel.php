<?php

/**
 * Representa o modelo de dados para as métricas do Dashboard.
 *
 * Isola as consultas SQL complexas (KPIs e agregações) do controlador,
 * garantindo o princípio da responsabilidade única (SRP).
 */
class DashboardModel {
    /**
     * @var PDO Conexão com o banco de dados.
     */
    private $pdo;

    /**
     * Construtor da classe.
     *
     * @param PDO $pdo Instância PDO.
     */
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Obtém o total geral de alunos cadastrados.
     *
     * @return int
     */
    public function getTotalAlunos() {
        $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM alunos");
        return (int) $stmt->fetch()['total'];
    }

    /**
     * Obtém os totais de protocolos agrupados por status principal.
     *
     * @return array Array associativo com chaves 'pendentes', 'andamento', 'concluidos'.
     */
    public function getTotaisProtocolos() {
        // FIXME: Em bancos com muitos registros, rodar três COUNT() separados pode ser ineficiente.
        // Uma melhoria seria usar um único SELECT com CASE WHEN e agrupar.
        $stmtPendentes = $this->pdo->query("SELECT COUNT(*) as total FROM protocolos WHERE status = 'Pendente'");
        $pendentes = (int) $stmtPendentes->fetch()['total'];

        $stmtAndamento = $this->pdo->query("SELECT COUNT(*) as total FROM protocolos WHERE status = 'Em andamento'");
        $andamento = (int) $stmtAndamento->fetch()['total'];

        $stmtConcluidos = $this->pdo->query("SELECT COUNT(*) as total FROM protocolos WHERE status = 'Concluído'");
        $concluidos = (int) $stmtConcluidos->fetch()['total'];

        return [
            'pendentes' => $pendentes,
            'andamento' => $andamento,
            'concluidos' => $concluidos,
            'total' => $pendentes + $andamento + $concluidos
        ];
    }

    /**
     * Obtém o total de vínculos entre professores e disciplinas.
     *
     * @return int
     */
    public function getTotalDisciplinas() {
        $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM professores_disciplinas");
        return (int) $stmt->fetch()['total'];
    }

    /**
     * Retorna os dados agrupados por tipo de serviço para gráficos.
     *
     * @return array Arrays separados de 'labels' e 'dados' para o Frontend.
     */
    public function getDadosServicosParaGrafico() {
        $stmt = $this->pdo->query("SELECT tipo_servico, COUNT(*) as quantidade FROM protocolos GROUP BY tipo_servico");
        $servicosData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $labels = [];
        $dados = [];

        foreach ($servicosData as $row) {
            $labels[] = !empty($row['tipo_servico']) ? $row['tipo_servico'] : 'Outros';
            $dados[] = (int) $row['quantidade'];
        }

        return ['labels' => $labels, 'dados' => $dados];
    }
}
