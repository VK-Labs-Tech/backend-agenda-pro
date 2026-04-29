<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddClientFirstLastNameToAppointments extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('appointments');

        if (!$table->hasColumn('client_first_name')) {
            $table->addColumn('client_first_name', 'string', [
                'limit' => 80,
                'null' => true,
                'after' => 'client_id',
            ]);
        }
        if (!$table->hasColumn('client_last_name')) {
            $table->addColumn('client_last_name', 'string', [
                'limit' => 80,
                'null' => true,
                'after' => 'client_first_name',
            ]);
        }

        $table->update();
    }
}
