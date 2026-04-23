<?php

namespace Domain\ServicePackage\Entities;

class ServicePackageSessionEntity
{
    public int $id;
    public int $service_package_id;
    public ?int $agendamento_id;
    public string $data;
    public string $status;
    public string $created_at;
    public string $updated_at;
}
