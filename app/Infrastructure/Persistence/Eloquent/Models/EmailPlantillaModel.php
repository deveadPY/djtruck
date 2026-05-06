<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class EmailPlantillaModel extends Model
{
    protected $table = 'email_plantillas';

    protected $fillable = [
        'tipo',
        'nombre',
        'asunto',
        'cuerpo_html',
        'variables_disponibles',
        'activo',
        'updated_by',
    ];

    protected $casts = [
        'activo'                => 'boolean',
        'variables_disponibles' => 'array',   // auto JSON decode/encode
    ];

    // ── Render asunto replacing {{variable}} placeholders ────────────────────
    public function renderAsunto(array $vars): string
    {
        return $this->renderTemplate($this->asunto, $vars);
    }

    // ── Render HTML body replacing {{variable}} placeholders ─────────────────
    public function renderCuerpo(array $vars): string
    {
        return $this->renderTemplate($this->cuerpo_html, $vars);
    }

    // ── Core template rendering logic ─────────────────────────────────────────
    private function renderTemplate(string $template, array $vars): string
    {
        $search  = array_map(fn($k) => '{{' . $k . '}}', array_keys($vars));
        $replace = array_map(fn($v) => (string) $v, array_values($vars));

        return str_replace($search, $replace, $template);
    }
}
