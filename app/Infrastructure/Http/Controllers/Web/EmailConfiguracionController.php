<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers\Web;

use App\Infrastructure\Mail\EmailSenderService;
use App\Infrastructure\Persistence\Eloquent\Models\EmailConfiguracionModel;
use App\Infrastructure\Persistence\Eloquent\Models\EmailPlantillaModel;
use App\Infrastructure\Persistence\Eloquent\Models\NotificacionEnviadaModel;
use App\Infrastructure\Settings\EmailSettings;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EmailConfiguracionController extends Controller
{
    public function __construct(
        private readonly EmailSenderService $mailer
    ) {
    }

    // ════════════════════════════════════════════════════════════════════════
    // SMTP CONFIGURATION
    // ════════════════════════════════════════════════════════════════════════

    /**
     * GET /configuracion/email
     */
    public function index(): View
    {
        $config = EmailConfiguracionModel::first();
        $plantillas = EmailPlantillaModel::orderBy('tipo')->get();
        $logs = NotificacionEnviadaModel::orderByDesc('enviado_en')->limit(50)->get();

        return view('configuracion.email', compact('config', 'plantillas', 'logs'));
    }

    /**
     * POST /configuracion/email/smtp
     */
    public function updateSmtp(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'mailer' => 'required|in:smtp,log,sendmail',
            'host' => 'nullable|string|max:150',
            'port' => 'nullable|integer|between:1,65535',
            'encryption' => 'nullable|in:tls,ssl,starttls',
            'username' => 'nullable|string|max:150',
            'password' => 'nullable|string|max:500',
            'from_address' => 'nullable|email|max:150',
            'from_name' => 'nullable|string|max:150',
            'activo' => 'boolean',
        ]);

        $validated['activo'] = $request->boolean('activo');
        $validated['updated_by'] = Auth::id();

        $config = EmailConfiguracionModel::firstOrNew(['id' => 1]);

        // If password field was left blank, keep the existing encrypted one
        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $config->fill($validated)->save();
        EmailSettings::forget();

        return redirect()->route('config.email')
            ->with('success', 'Configuración SMTP guardada correctamente.');
    }

    /**
     * POST /configuracion/email/smtp/test
     */
    public function testSmtp(Request $request): RedirectResponse
    {
        $request->validate(['test_email' => 'required|email|max:150']);

        $now = date('d/m/Y H:i');
        $appName = config('app.name', 'ERP Camiones & Repuestos');

        $success = $this->mailer->sendRaw(
            tipo: 'TEST',
            toEmail: $request->test_email,
            toNombre: 'Prueba SMTP',
            asunto: 'Prueba de configuración SMTP exitosa',
            html: <<<HTML
<div style="font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;max-width:550px;margin:20px auto;padding:32px;background:#ffffff;border:1px solid #e5e7eb;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.05);">
  <div style="text-align:center;margin-bottom:24px;">
    <table align="center" border="0" cellpadding="0" cellspacing="0" style="margin:0 auto;">
      <tr>
        <td style="background-color:#dcfce7;border-radius:50%;padding:12px;">
          <svg style="width:32px;height:32px;color:#16a34a;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
          </svg>
        </td>
      </tr>
    </table>
  </div>
  <h2 style="color:#111827;grid-area:auto;margin:0 0 16px;text-align:center;font-size:1.35rem;font-weight:600;">Configuración Exitosa</h2>
  <p style="color:#4b5563;font-size:0.95rem;line-height:1.6;margin-bottom:12px;">Hola Administrador,</p>
  <p style="color:#4b5563;font-size:0.95rem;line-height:1.6;margin-bottom:12px;">Este es un mensaje de prueba de <strong>{$appName}</strong>.</p>
  <p style="color:#4b5563;font-size:0.95rem;line-height:1.6;margin-bottom:24px;">Si estás leyendo este correo, significa que la pasarela SMTP ha sido configurada correctamente y el sistema ya es capaz de enviar correos electrónicos.</p>
  <hr style="border:none;border-top:1px solid #f3f4f6;margin:24px 0" />
  <p style="font-size:0.75rem;color:#9ca3af;margin:0;text-align:center;">Email enviado automáticamente el: {$now}</p>
</div>
HTML,
            context: ['enviado_por' => Auth::id()]
        );

        return redirect()->route('config.email')
            ->with(
                $success ? 'success' : 'error',
                $success
                ? 'Email de prueba enviado a ' . $request->test_email . ' correctamente.'
                : 'Error al enviar. Verifique la configuración SMTP y los logs.'
            );
    }

    // ════════════════════════════════════════════════════════════════════════
    // EMAIL TEMPLATES
    // ════════════════════════════════════════════════════════════════════════

    /**
     * GET /configuracion/email/plantillas/{id}/edit
     */
    public function editPlantilla(int $id): View
    {
        $plantilla = EmailPlantillaModel::findOrFail($id);
        return view('configuracion.email-plantilla-edit', compact('plantilla'));
    }

    /**
     * PUT /configuracion/email/plantillas/{id}
     */
    public function updatePlantilla(Request $request, int $id): RedirectResponse
    {
        $plantilla = EmailPlantillaModel::findOrFail($id);

        $validated = $request->validate([
            'nombre' => 'required|string|max:150',
            'asunto' => 'required|string|max:250',
            'cuerpo_html' => 'required|string',
            'activo' => 'boolean',
        ]);

        $validated['activo'] = $request->boolean('activo');
        $validated['updated_by'] = Auth::id();

        $plantilla->fill($validated)->save();

        return redirect()->route('config.email')
            ->with('success', "Plantilla \"{$plantilla->nombre}\" actualizada correctamente.");
    }

    /**
     * GET /configuracion/email/plantillas/create
     */
    public function createPlantilla(): View
    {
        $plantilla = new EmailPlantillaModel();
        // Give some basic default structures
        $plantilla->cuerpo_html = "<div style=\"font-family: Arial, sans-serif; color: #333;\">\n  <h2>Título aquí</h2>\n  <p>Hola {{cliente_nombre}},</p>\n  <p>Cuerpo del mensaje...</p>\n</div>";
        $plantilla->variables_disponibles = '["cliente_nombre", "monto", "fecha_vencimiento", "numero_venta"]';

        return view('configuracion.email-plantilla-edit', compact('plantilla'));
    }

    /**
     * POST /configuracion/email/plantillas
     */
    public function storePlantilla(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tipo' => 'required|string|max:50|unique:email_plantillas,tipo',
            'nombre' => 'required|string|max:150',
            'asunto' => 'required|string|max:250',
            'cuerpo_html' => 'required|string',
            'variables_disponibles' => 'nullable|string',
            'activo' => 'boolean',
        ]);

        $validated['activo'] = $request->boolean('activo');
        $validated['updated_by'] = Auth::id();

        // Ensure variables_disponibles is valid JSON or array string
        if (empty($validated['variables_disponibles'])) {
            $validated['variables_disponibles'] = '[]';
        }

        $plantilla = EmailPlantillaModel::create($validated);

        return redirect()->route('config.email')
            ->with('success', "Plantilla \"{$plantilla->nombre}\" creada correctamente.");
    }

    /**
     * DELETE /configuracion/email/plantillas/{id}
     */
    public function destroyPlantilla(int $id): RedirectResponse
    {
        $plantilla = EmailPlantillaModel::findOrFail($id);

        // Prevent deletion of system defaults if needed, but since user requested full CRUD, we will allow it, or just protect standard ones.
        $protected = ['BIENVENIDA_VENTA', 'RECIBO_CUOTA', 'CUOTA_VENCIDA', 'RECORDATORIO_CUOTA', 'ESTADO_CUENTA', 'ESTADO_CUENTA_VENCIDO', 'FACTURA_AVISO', 'PRUEBA_SISTEMA'];

        if (in_array($plantilla->tipo, $protected)) {
            return redirect()->route('config.email')
                ->withErrors(['error' => "La plantilla del sistema \"{$plantilla->tipo}\" no puede ser eliminada, solo desactivada o editada."]);
        }

        $nombre = $plantilla->nombre;
        $plantilla->delete();

        return redirect()->route('config.email')
            ->with('success', "Plantilla \"{$nombre}\" eliminada correctamente.");
    }

    // ════════════════════════════════════════════════════════════════════════
    // MANUAL EMAIL SEND FROM PLAN DETAIL
    // ════════════════════════════════════════════════════════════════════════

    /**
     * POST /planes-cuotas/{planId}/enviar-email
     */
    public function enviarEmailPlan(Request $request, int $planId): RedirectResponse
    {
        $request->validate([
            'tipo' => 'required|in:CUOTA_VENCIDA,RECORDATORIO_CUOTA,ESTADO_CUENTA',
            'cuota_id' => 'nullable|integer|exists:cuotas,id',
        ]);

        $plan = DB::table('planes_cuotas')->where('id', $planId)->first();
        if (!$plan)
            return back()->withErrors(['error' => 'Plan no encontrado.']);

        $cliente = DB::table('clientes')->where('id', $plan->cliente_id)->first();
        if (!$cliente)
            return back()->withErrors(['error' => 'Cliente no encontrado.']);

        if (!$cliente->email) {
            return back()->withErrors(['error' => 'El cliente no tiene dirección de email registrada.']);
        }

        $venta = DB::table('ventas')->where('id', $plan->venta_id)->first();
        $cuota = null;

        if ($request->filled('cuota_id')) {
            $cuota = DB::table('cuotas')->where('id', $request->cuota_id)->first();
        }

        // Build the monto for display
        $monto = $cuota
            ? number_format((float) $cuota->capital + (float) $cuota->interes, 2, ',', '.')
            : '—';

        $vars = [
            'cliente_nombre' => $cliente->razon_social ?? $cliente->nombre_fantasia ?? 'Cliente',
            'numero_venta' => $venta->numero_venta ?? "#{$plan->venta_id}",
            'numero_cuota' => $cuota ? (string) $cuota->numero_cuota : '—',
            'total_cuotas' => $cuota ? (string) $cuota->total_cuotas : '—',
            'monto_cuota' => $monto,
            'moneda' => $cuota ? $cuota->moneda : ($plan->moneda ?? 'USD'),
            'fecha_vencimiento' => $cuota
                ? Carbon::parse($cuota->fecha_vencimiento)->format('d/m/Y')
                : '—',
        ];

        $success = $this->mailer->sendByTipo(
            tipo: $request->tipo,
            toEmail: $cliente->email,
            toNombre: $cliente->razon_social ?? $cliente->nombre_fantasia ?? 'Cliente',
            vars: $vars,
            context: [
                'cliente_id' => $plan->cliente_id,
                'venta_id' => $plan->venta_id,
                'cuota_id' => $request->cuota_id,
                'enviado_por' => Auth::id(),
            ]
        );

        return back()->with(
            $success ? 'success' : 'error',
            $success
            ? 'Email enviado correctamente a ' . $cliente->email . '.'
            : 'Error al enviar el email. Verifique la configuración SMTP en Config. Email.'
        );
    }
}
