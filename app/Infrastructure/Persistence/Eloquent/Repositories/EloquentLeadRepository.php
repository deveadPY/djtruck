<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Leads\Aggregates\Lead;
use App\Domain\Leads\Repositories\LeadRepositoryInterface;
use App\Domain\Leads\ValueObjects\LeadId;
use App\Infrastructure\Persistence\Eloquent\Models\LeadModel;

final class EloquentLeadRepository implements LeadRepositoryInterface
{
    public function save(Lead $lead): Lead
    {
        $data = $lead->toArray();
        unset($data['id']);

        $model = LeadModel::create($data);
        return $lead->withId(LeadId::fromInt($model->id));
    }

    public function update(int $id, Lead $lead): Lead
    {
        $data = $lead->toArray();
        unset($data['id']);

        LeadModel::where('id', $id)->update($data);
        return $lead->withId(LeadId::fromInt($id));
    }

    public function findById(int $id): ?Lead
    {
        $model = LeadModel::find($id);
        if (!$model) {
            return null;
        }

        $lead = Lead::capture(
            vehiculoId: $model->vehiculo_id,
            nombre:     $model->nombre,
            telefono:   $model->telefono,
            email:      $model->email,
            canal:      $model->canal ?? 'Formulario',
            mensaje:    $model->mensaje,
        );

        // Reconstruir estado actual del agregado
        $reflection = new \ReflectionClass($lead);

        $estadoProp = $reflection->getProperty('estado');
        $estadoProp->setValue($lead, \App\Domain\Leads\ValueObjects\LeadStatus::from($model->estado ?? 'nuevo'));

        if ($model->asignado_a) {
            $asignadoProp = $reflection->getProperty('asignadoA');
            $asignadoProp->setValue($lead, $model->asignado_a);
        }
        if ($model->venta_id) {
            $ventaProp = $reflection->getProperty('ventaId');
            $ventaProp->setValue($lead, $model->venta_id);
        }
        if ($model->motivo_perdido) {
            $motivoProp = $reflection->getProperty('motivoPerdido');
            $motivoProp->setValue($lead, $model->motivo_perdido);
        }

        return $lead->withId(LeadId::fromInt($model->id));
    }
}
