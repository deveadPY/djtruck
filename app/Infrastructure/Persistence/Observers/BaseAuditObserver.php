<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Observers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Observer base que registra cambios en audit_logs.
 * Las subclases definen el entityType y opcionalmente campos sensibles a omitir.
 */
abstract class BaseAuditObserver
{
    /**
     * Identificador de tipo en audit_logs (ej: 'venta', 'vehiculo', 'cliente').
     */
    abstract protected function entityType(): string;

    /**
     * Campos que NO se registran en el log (passwords, tokens, etc.).
     */
    protected function hiddenFields(): array
    {
        return ['password', 'remember_token', 'api_token'];
    }

    public function created(Model $model): void
    {
        $this->log('CREATE', $model, null, $this->filterAttributes($model->getAttributes()));
    }

    public function updated(Model $model): void
    {
        $changes = $model->getChanges();
        if (empty($changes) || (count($changes) === 1 && isset($changes['updated_at']))) {
            return;
        }

        $original = array_intersect_key($model->getOriginal(), $changes);
        $this->log(
            'UPDATE',
            $model,
            $this->filterAttributes($original),
            $this->filterAttributes($changes)
        );
    }

    public function deleted(Model $model): void
    {
        $action = method_exists($model, 'trashed') && $model->trashed() ? 'SOFT_DELETE' : 'DELETE';
        $this->log($action, $model, $this->filterAttributes($model->getAttributes()), null);
    }

    public function restored(Model $model): void
    {
        $this->log('RESTORE', $model, null, $this->filterAttributes($model->getAttributes()));
    }

    private function log(string $action, Model $model, ?array $oldValues, ?array $newValues): void
    {
        try {
            DB::table('audit_logs')->insert([
                'user_id'     => Auth::id(),
                'action'      => $action,
                'entity_type' => $this->entityType(),
                'entity_id'   => (int) $model->getKey(),
                'old_values'  => $oldValues ? json_encode($oldValues) : null,
                'new_values'  => $newValues ? json_encode($newValues) : null,
                'metadata'    => null,
                'ip_address'  => request()?->ip(),
                'created_at'  => now(),
            ]);
        } catch (\Throwable) {
            // Auditoría nunca debe bloquear la operación principal
        }
    }

    private function filterAttributes(array $attributes): array
    {
        return array_diff_key($attributes, array_flip($this->hiddenFields()));
    }
}
