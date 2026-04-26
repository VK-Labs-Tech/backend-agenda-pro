<?php

namespace App\Domain\ServicePackage\Entities;

class ServicePackageEntity
{
    public int $id;
    public int $client_id;
    public int $company_id;
    public int $service_id;
    public int $quantidade_sessoes;
    public string $frequencia;
    public string $dia_semana;
    public string $horario;
    public string $data_inicio;
    public ?string $data_fim;
    public string $status;
    public string $created_at;
    public string $updated_at;
}
