<?php
// Migration para criar a tabela de sessões dos pacotes de serviço
use Phinx\Migration\AbstractMigration;

class CreateServicePackageSessionsTable extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('service_package_sessions');
        $table
            ->addColumn('service_package_id', 'integer')
            ->addColumn('agendamento_id', 'integer', ['null' => true])
            ->addColumn('data', 'datetime')
            ->addColumn('status', 'string', ['limit' => 20, 'default' => 'agendado']) // agendado, realizado, cancelado
            ->addTimestamps()
            ->create();
    }
}
