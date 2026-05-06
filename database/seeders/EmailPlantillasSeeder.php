<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Infrastructure\Persistence\Eloquent\Models\EmailPlantillaModel;

class EmailPlantillasSeeder extends Seeder
{
    public function run(): void
    {
        $plantillas = [

            // ════════════════════════════════════════════════════════════════
            // 1. BIENVENIDA_VENTA — Confirmación de compra
            // ════════════════════════════════════════════════════════════════
            [
                'tipo'   => 'BIENVENIDA_VENTA',
                'nombre' => 'Bienvenida y Confirmación de Venta',
                'asunto' => '¡Confirmación de compra! — Venta {{numero_venta}}',
                'variables_disponibles' => json_encode([
                    'cliente_nombre', 'numero_venta', 'vehiculo_marca',
                    'vehiculo_modelo', 'vehiculo_anio', 'total_usd', 'fecha_venta',
                ]),
                'cuerpo_html' => <<<'HTML'
<div style="font-family:Arial,Helvetica,sans-serif;max-width:600px;margin:0 auto;background:#ffffff;">
  <!-- Header -->
  <div style="background:#6c63ff;padding:28px 32px;text-align:center;border-radius:8px 8px 0 0;">
    <h1 style="color:#ffffff;margin:0;font-size:22px;font-weight:700;">¡Bienvenido a nuestra familia!</h1>
    <p style="color:rgba(255,255,255,.8);margin:8px 0 0;font-size:14px;">Confirmación de su compra</p>
  </div>
  <!-- Body -->
  <div style="padding:32px;background:#ffffff;border:1px solid #e8e8e8;border-top:none;">
    <p style="font-size:15px;color:#333;margin:0 0 16px;">Estimado/a <strong>{{cliente_nombre}}</strong>,</p>
    <p style="font-size:14px;color:#555;line-height:1.6;margin:0 0 20px;">
      Es un placer darle la bienvenida. Confirmamos la adquisición exitosa de su vehículo.
      A continuación el resumen de su compra:
    </p>
    <table style="width:100%;border-collapse:collapse;margin:0 0 20px;font-size:13px;">
      <tr style="background:#f8f7ff;">
        <td style="padding:11px 14px;border:1px solid #ddd;font-weight:600;width:40%;">N° de Venta</td>
        <td style="padding:11px 14px;border:1px solid #ddd;font-weight:700;color:#6c63ff;">{{numero_venta}}</td>
      </tr>
      <tr>
        <td style="padding:11px 14px;border:1px solid #ddd;font-weight:600;">Vehículo</td>
        <td style="padding:11px 14px;border:1px solid #ddd;">{{vehiculo_marca}} {{vehiculo_modelo}} {{vehiculo_anio}}</td>
      </tr>
      <tr style="background:#f8f7ff;">
        <td style="padding:11px 14px;border:1px solid #ddd;font-weight:600;">Monto Total</td>
        <td style="padding:11px 14px;border:1px solid #ddd;font-weight:700;color:#6c63ff;font-size:15px;">USD {{total_usd}}</td>
      </tr>
      <tr>
        <td style="padding:11px 14px;border:1px solid #ddd;font-weight:600;">Fecha de Venta</td>
        <td style="padding:11px 14px;border:1px solid #ddd;">{{fecha_venta}}</td>
      </tr>
    </table>
    <p style="font-size:13px;color:#555;line-height:1.6;">
      Gracias por su confianza. Nuestro equipo está a su disposición para cualquier consulta o asistencia que necesite.
    </p>
  </div>
  <!-- Footer -->
  <div style="background:#f5f5f5;padding:16px 32px;text-align:center;border-radius:0 0 8px 8px;border:1px solid #e8e8e8;border-top:none;">
    <p style="font-size:11px;color:#999;margin:0;">Este es un correo automático. Por favor no responda a este mensaje.</p>
  </div>
</div>
HTML,
                'activo' => true,
            ],

            // ════════════════════════════════════════════════════════════════
            // 2. RECIBO_CUOTA — Confirmación de pago de cuota
            // ════════════════════════════════════════════════════════════════
            [
                'tipo'   => 'RECIBO_CUOTA',
                'nombre' => 'Recibo de Pago de Cuota',
                'asunto' => '✔ Pago recibido — Cuota {{numero_cuota}}/{{total_cuotas}} — {{numero_venta}}',
                'variables_disponibles' => json_encode([
                    'cliente_nombre', 'numero_venta', 'numero_cuota',
                    'total_cuotas', 'monto_pagado', 'moneda', 'fecha_pago', 'fecha_vencimiento',
                ]),
                'cuerpo_html' => <<<'HTML'
<div style="font-family:Arial,Helvetica,sans-serif;max-width:600px;margin:0 auto;background:#ffffff;">
  <!-- Header -->
  <div style="background:#22c55e;padding:28px 32px;text-align:center;border-radius:8px 8px 0 0;">
    <h1 style="color:#ffffff;margin:0;font-size:22px;font-weight:700;">✔ Pago Recibido</h1>
    <p style="color:rgba(255,255,255,.85);margin:8px 0 0;font-size:14px;">Gracias por su pago puntual</p>
  </div>
  <!-- Body -->
  <div style="padding:32px;background:#ffffff;border:1px solid #e8e8e8;border-top:none;">
    <p style="font-size:15px;color:#333;margin:0 0 16px;">Estimado/a <strong>{{cliente_nombre}}</strong>,</p>
    <p style="font-size:14px;color:#555;line-height:1.6;margin:0 0 20px;">
      Confirmamos la recepción de su pago. A continuación el detalle del recibo:
    </p>
    <table style="width:100%;border-collapse:collapse;margin:0 0 20px;font-size:13px;">
      <tr style="background:#f0fdf4;">
        <td style="padding:11px 14px;border:1px solid #bbf7d0;font-weight:600;width:40%;">N° de Venta</td>
        <td style="padding:11px 14px;border:1px solid #bbf7d0;">{{numero_venta}}</td>
      </tr>
      <tr>
        <td style="padding:11px 14px;border:1px solid #bbf7d0;font-weight:600;">Cuota N°</td>
        <td style="padding:11px 14px;border:1px solid #bbf7d0;">{{numero_cuota}} de {{total_cuotas}}</td>
      </tr>
      <tr style="background:#f0fdf4;">
        <td style="padding:11px 14px;border:1px solid #bbf7d0;font-weight:600;">Monto Pagado</td>
        <td style="padding:11px 14px;border:1px solid #bbf7d0;font-weight:700;color:#16a34a;font-size:16px;">{{moneda}} {{monto_pagado}}</td>
      </tr>
      <tr>
        <td style="padding:11px 14px;border:1px solid #bbf7d0;font-weight:600;">Fecha de Pago</td>
        <td style="padding:11px 14px;border:1px solid #bbf7d0;">{{fecha_pago}}</td>
      </tr>
    </table>
    <p style="font-size:13px;color:#555;line-height:1.6;">
      Gracias por mantenerse al día con sus pagos. Esto es un recibo automático de confirmación.
    </p>
  </div>
  <!-- Footer -->
  <div style="background:#f5f5f5;padding:16px 32px;text-align:center;border-radius:0 0 8px 8px;border:1px solid #e8e8e8;border-top:none;">
    <p style="font-size:11px;color:#999;margin:0;">Correo automático — No responder.</p>
  </div>
</div>
HTML,
                'activo' => true,
            ],

            // ════════════════════════════════════════════════════════════════
            // 3. CUOTA_VENCIDA — Cuota vencida, acción requerida
            // ════════════════════════════════════════════════════════════════
            [
                'tipo'   => 'CUOTA_VENCIDA',
                'nombre' => 'Notificación de Cuota Vencida',
                'asunto' => '⚠ Cuota vencida — {{numero_venta}} — Por favor regularice su situación',
                'variables_disponibles' => json_encode([
                    'cliente_nombre', 'numero_venta', 'numero_cuota',
                    'total_cuotas', 'monto_cuota', 'moneda', 'fecha_vencimiento',
                ]),
                'cuerpo_html' => <<<'HTML'
<div style="font-family:Arial,Helvetica,sans-serif;max-width:600px;margin:0 auto;background:#ffffff;">
  <!-- Header -->
  <div style="background:#ef4444;padding:28px 32px;text-align:center;border-radius:8px 8px 0 0;">
    <h1 style="color:#ffffff;margin:0;font-size:22px;font-weight:700;">⚠ Cuota Vencida</h1>
    <p style="color:rgba(255,255,255,.85);margin:8px 0 0;font-size:14px;">Se requiere acción inmediata</p>
  </div>
  <!-- Body -->
  <div style="padding:32px;background:#ffffff;border:1px solid #e8e8e8;border-top:none;">
    <p style="font-size:15px;color:#333;margin:0 0 16px;">Estimado/a <strong>{{cliente_nombre}}</strong>,</p>
    <p style="font-size:14px;color:#555;line-height:1.6;margin:0 0 20px;">
      Le informamos que tiene una cuota <strong style="color:#ef4444;">vencida</strong> que requiere atención inmediata
      para evitar cargos adicionales por mora:
    </p>
    <table style="width:100%;border-collapse:collapse;margin:0 0 20px;font-size:13px;">
      <tr style="background:#fef2f2;">
        <td style="padding:11px 14px;border:1px solid #fecaca;font-weight:600;width:40%;">N° de Venta</td>
        <td style="padding:11px 14px;border:1px solid #fecaca;">{{numero_venta}}</td>
      </tr>
      <tr>
        <td style="padding:11px 14px;border:1px solid #fecaca;font-weight:600;">Cuota N°</td>
        <td style="padding:11px 14px;border:1px solid #fecaca;">{{numero_cuota}} de {{total_cuotas}}</td>
      </tr>
      <tr style="background:#fef2f2;">
        <td style="padding:11px 14px;border:1px solid #fecaca;font-weight:600;">Monto Pendiente</td>
        <td style="padding:11px 14px;border:1px solid #fecaca;font-weight:700;color:#dc2626;font-size:16px;">{{moneda}} {{monto_cuota}}</td>
      </tr>
      <tr>
        <td style="padding:11px 14px;border:1px solid #fecaca;font-weight:600;">Fecha Vencida</td>
        <td style="padding:11px 14px;border:1px solid #fecaca;font-weight:700;color:#dc2626;">{{fecha_vencimiento}}</td>
      </tr>
    </table>
    <p style="font-size:13px;color:#555;line-height:1.6;">
      Por favor comuníquese con nosotros a la brevedad para regularizar su situación
      y evitar cargos adicionales por intereses de mora.
    </p>
  </div>
  <!-- Footer -->
  <div style="background:#f5f5f5;padding:16px 32px;text-align:center;border-radius:0 0 8px 8px;border:1px solid #e8e8e8;border-top:none;">
    <p style="font-size:11px;color:#999;margin:0;">Correo automático — No responder.</p>
  </div>
</div>
HTML,
                'activo' => true,
            ],

            // ════════════════════════════════════════════════════════════════
            // 4. RECORDATORIO_CUOTA — Aviso previo al vencimiento
            // ════════════════════════════════════════════════════════════════
            [
                'tipo'   => 'RECORDATORIO_CUOTA',
                'nombre' => 'Recordatorio de Cuota Próxima a Vencer',
                'asunto' => '📅 Recordatorio — Cuota {{numero_cuota}} vence el {{fecha_vencimiento}}',
                'variables_disponibles' => json_encode([
                    'cliente_nombre', 'numero_venta', 'numero_cuota',
                    'total_cuotas', 'monto_cuota', 'moneda', 'fecha_vencimiento',
                ]),
                'cuerpo_html' => <<<'HTML'
<div style="font-family:Arial,Helvetica,sans-serif;max-width:600px;margin:0 auto;background:#ffffff;">
  <!-- Header -->
  <div style="background:#f59e0b;padding:28px 32px;text-align:center;border-radius:8px 8px 0 0;">
    <h1 style="color:#ffffff;margin:0;font-size:22px;font-weight:700;">📅 Recordatorio de Pago</h1>
    <p style="color:rgba(255,255,255,.9);margin:8px 0 0;font-size:14px;">Su próxima cuota se acerca</p>
  </div>
  <!-- Body -->
  <div style="padding:32px;background:#ffffff;border:1px solid #e8e8e8;border-top:none;">
    <p style="font-size:15px;color:#333;margin:0 0 16px;">Estimado/a <strong>{{cliente_nombre}}</strong>,</p>
    <p style="font-size:14px;color:#555;line-height:1.6;margin:0 0 20px;">
      Le recordamos que tiene una cuota próxima a vencer. Por favor prepárese para realizar el pago a tiempo:
    </p>
    <table style="width:100%;border-collapse:collapse;margin:0 0 20px;font-size:13px;">
      <tr style="background:#fffbeb;">
        <td style="padding:11px 14px;border:1px solid #fde68a;font-weight:600;width:40%;">N° de Venta</td>
        <td style="padding:11px 14px;border:1px solid #fde68a;">{{numero_venta}}</td>
      </tr>
      <tr>
        <td style="padding:11px 14px;border:1px solid #fde68a;font-weight:600;">Cuota N°</td>
        <td style="padding:11px 14px;border:1px solid #fde68a;">{{numero_cuota}} de {{total_cuotas}}</td>
      </tr>
      <tr style="background:#fffbeb;">
        <td style="padding:11px 14px;border:1px solid #fde68a;font-weight:600;">Monto a Pagar</td>
        <td style="padding:11px 14px;border:1px solid #fde68a;font-weight:700;color:#d97706;font-size:16px;">{{moneda}} {{monto_cuota}}</td>
      </tr>
      <tr>
        <td style="padding:11px 14px;border:1px solid #fde68a;font-weight:600;">Fecha de Vencimiento</td>
        <td style="padding:11px 14px;border:1px solid #fde68a;font-weight:700;color:#d97706;">{{fecha_vencimiento}}</td>
      </tr>
    </table>
    <p style="font-size:13px;color:#555;line-height:1.6;">
      Realice su pago antes de la fecha indicada para evitar cargos por intereses de mora.
      ¡Gracias por su compromiso y confianza!
    </p>
  </div>
  <!-- Footer -->
  <div style="background:#f5f5f5;padding:16px 32px;text-align:center;border-radius:0 0 8px 8px;border:1px solid #e8e8e8;border-top:none;">
    <p style="font-size:11px;color:#999;margin:0;">Correo automático — No responder.</p>
  </div>
</div>
HTML,
                'activo' => true,
            ],

            // ════════════════════════════════════════════════════════════════
            // 5. ESTADO_CUENTA — Resumen de cuenta del cliente
            // ════════════════════════════════════════════════════════════════
            [
                'tipo'   => 'ESTADO_CUENTA',
                'nombre' => 'Estado de Cuenta del Cliente',
                'asunto' => '📋 Estado de cuenta — {{cliente_nombre}} — {{numero_venta}}',
                'variables_disponibles' => json_encode([
                    'cliente_nombre', 'numero_venta', 'numero_cuota',
                    'total_cuotas', 'monto_cuota', 'moneda', 'fecha_vencimiento',
                ]),
                'cuerpo_html' => <<<'HTML'
<div style="font-family:Arial,Helvetica,sans-serif;max-width:600px;margin:0 auto;background:#ffffff;">
  <!-- Header -->
  <div style="background:#6c63ff;padding:28px 32px;text-align:center;border-radius:8px 8px 0 0;">
    <h1 style="color:#ffffff;margin:0;font-size:22px;font-weight:700;">📋 Estado de Cuenta</h1>
    <p style="color:rgba(255,255,255,.85);margin:8px 0 0;font-size:14px;">Resumen de su cuenta corriente</p>
  </div>
  <!-- Body -->
  <div style="padding:32px;background:#ffffff;border:1px solid #e8e8e8;border-top:none;">
    <p style="font-size:15px;color:#333;margin:0 0 16px;">Estimado/a <strong>{{cliente_nombre}}</strong>,</p>
    <p style="font-size:14px;color:#555;line-height:1.6;margin:0 0 20px;">
      A continuación le presentamos el resumen actual de su cuenta:
    </p>
    <table style="width:100%;border-collapse:collapse;margin:0 0 20px;font-size:13px;">
      <tr style="background:#f8f7ff;">
        <td style="padding:11px 14px;border:1px solid #ddd;font-weight:600;width:40%;">N° de Venta</td>
        <td style="padding:11px 14px;border:1px solid #ddd;">{{numero_venta}}</td>
      </tr>
      <tr>
        <td style="padding:11px 14px;border:1px solid #ddd;font-weight:600;">Próxima Cuota N°</td>
        <td style="padding:11px 14px;border:1px solid #ddd;">{{numero_cuota}} de {{total_cuotas}}</td>
      </tr>
      <tr style="background:#f8f7ff;">
        <td style="padding:11px 14px;border:1px solid #ddd;font-weight:600;">Monto</td>
        <td style="padding:11px 14px;border:1px solid #ddd;font-weight:700;font-size:15px;">{{moneda}} {{monto_cuota}}</td>
      </tr>
      <tr>
        <td style="padding:11px 14px;border:1px solid #ddd;font-weight:600;">Fecha de Vencimiento</td>
        <td style="padding:11px 14px;border:1px solid #ddd;">{{fecha_vencimiento}}</td>
      </tr>
    </table>
    <p style="font-size:13px;color:#555;line-height:1.6;">
      Para ver el detalle completo de su plan de cuotas, comuníquese con nuestra oficina.
      Estamos disponibles para atenderle.
    </p>
  </div>
  <!-- Footer -->
  <div style="background:#f5f5f5;padding:16px 32px;text-align:center;border-radius:0 0 8px 8px;border:1px solid #e8e8e8;border-top:none;">
    <p style="font-size:11px;color:#999;margin:0;">Correo automático — No responder.</p>
  </div>
</div>
HTML,
                'activo' => true,
            ],

        ]; // end $plantillas

        foreach ($plantillas as $data) {
            EmailPlantillaModel::firstOrCreate(
                ['tipo' => $data['tipo']],
                $data
            );
        }

        $this->command->info('  ✔ Plantillas de email creadas/verificadas.');
    }
}
