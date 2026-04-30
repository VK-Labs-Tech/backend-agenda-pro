<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAppointmentServicesTable extends AbstractMigration
{
    public function up(): void
    {
        $this->table('appointment_services', [
            'id' => true,
            'engine' => 'InnoDB',
            'encoding' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ])
            ->addColumn('appointment_id', 'integer', ['null' => false])
            ->addColumn('service_id', 'integer', ['null' => false])
            ->addColumn('sort_order', 'integer', ['null' => false, 'default' => 0])
            ->addIndex(['appointment_id'])
            ->addIndex(['service_id'])
            ->create();

        $this->query(<<<'SQL'
INSERT INTO appointment_services (appointment_id, service_id, sort_order)
SELECT id, service_id, 0 FROM appointments
WHERE service_id IS NOT NULL AND service_id > 0
SQL);
    }

    public function down(): void
    {
        $this->table('appointment_services')->drop()->save();
    }
}
