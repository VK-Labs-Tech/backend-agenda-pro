<?php

namespace App\Domain\ServicePackage\Entities;

class ServicePackageSessionEntity
{
    public int $id;
    public int $service_package_id;
    public ?int $agendamento_id = null;
    public string $data;
    public string $status;
    public string $created_at;
    public string $updated_at;
}
