<?php
// Migration para criar a tabela de pacotes de serviço
use Phinx\Migration\AbstractMigration;

class CreateServicePackagesTable extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('service_packages');
        $table
            ->addColumn('client_id', 'integer')
            ->addColumn('company_id', 'integer')
            ->addColumn('service_id', 'integer')
            ->addColumn('quantidade_sessoes', 'integer')
            ->addColumn('frequencia', 'string', ['limit' => 20]) // semanal, mensal, etc
            ->addColumn('dia_semana', 'string', ['limit' => 10]) // ex: quinta
            ->addColumn('horario', 'time')
            ->addColumn('data_inicio', 'date')
            ->addColumn('data_fim', 'date', ['null' => true])
            ->addColumn('status', 'string', ['limit' => 20, 'default' => 'ativo'])
            ->addTimestamps()
            ->create();
    }
}
