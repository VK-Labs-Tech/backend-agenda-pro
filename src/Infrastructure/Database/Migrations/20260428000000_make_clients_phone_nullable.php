<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class MakeClientsPhoneNullable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('clients');
        $table->changeColumn('phone', 'string', [
            'limit' => 20,
            'null' => true,
            'default' => null,
        ])->update();
    }
}
