<?php

declare(strict_types=1);

namespace App\Infrastructure\SIFEN;

use App\Infrastructure\Persistence\Eloquent\Models\SaleModel;
use App\Infrastructure\SIFEN\Schema\SifenXmlBuilder;
use App\Infrastructure\SIFEN\Schema\CdcGenerator;
use App\Infrastructure\SIFEN\Signature\XmlDsigSigner;
use App\Infrastructure\SIFEN\Validators\SifenResponseValidator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

final class ElectronicInvoicingService
{
    public function __construct(
        private readonly SifenXmlBuilder        $xmlBuilder,
        private readonly CdcGenerator           $cdcGenerator,
        private readonly XmlDsigSigner          $signer,
        private readonly SifenResponseValidator $validator,
    ) {}

    public function emitirFactura(SaleModel $venta, string $tipo = '01'): array
    {
        $venta->loadMissing(['cliente', 'vehiculo', 'detallesPago']);

        $cdc = $this->cdcGenerator->generate(
            tipoDocumento:     $tipo,
            establecimiento:   config('sifen.establecimiento', '001'),
            punto:             config('sifen.punto_expedicion', '001'),
            numero:            $venta->id,
            contribuyenteRuc:  config('sifen.ruc_emisor', '80000000-1'),
            tipoContribuyente: config('sifen.tipo_contribuyente', '2'),
            fecha:             now()->format('Ymd'),
            tipoEmision:       '1',
        );

        $xmlSinFirma = $this->xmlBuilder->buildFactura([
            'cdc'    => $cdc,
            'venta'  => $venta,
            'emisor' => $this->emisorData(),
            'tipo'   => $tipo,
        ]);

        $xmlFirmado = $this->signer->sign(
            $xmlSinFirma,
            config('sifen.cert_path', storage_path('certs/certificado.p12')),
            config('sifen.cert_passphrase', ''),
        );

        $qrUrl = $this->buildQrUrl($cdc, $venta);

        // Enviar a SIFEN si no es sandbox
        $estado = 'APROBADO'; 
        $protocolo = 'PR-' . strtoupper(uniqid());

        if (config('sifen.ambiente') === 'produccion') {
            try {
                $response = $this->enviarAlWebService($xmlFirmado, $cdc);
                $estado = $response['estado'] ?? 'APROBADO';
                $protocolo = $response['numero_protocolo'] ?? $protocolo;
            } catch (\Throwable $e) {
                $estado = 'RECHAZADO';
                throw $e;
            }
        }

        // --- GESTIÓN DE ARCHIVOS ---
        
        // 1. Guardar XML
        $xmlRelativePath = "sifen/xml/de_{$cdc}.xml";
        Storage::disk('public')->put($xmlRelativePath, $xmlFirmado);

        // 2. Generar KuDE (PDF) Real si está APROBADO
        $kudeRelativePath = null;
        if ($estado === 'APROBADO') {
            $kudeRelativePath = $this->generateKude($venta, $cdc, $qrUrl, $tipo, $protocolo);
        }

        $venta->update([
            'cdc_sifen'                 => $cdc,
            'numero_timbrado'           => config('sifen.numero_timbrado'),
            'fecha_emision_fe'          => now(),
            'tiene_factura_electronica' => ($estado === 'APROBADO'),
            'estado_sifen'              => $estado,
            'sifen_xml_path'            => $xmlRelativePath,
            'sifen_kude_path'           => $kudeRelativePath,
            'sifen_numero_lote'         => $protocolo,
            'tipo_comprobante_sifen'    => $tipo,
            'sifen_error'               => null,
            'updated_by'                => auth()->id() ?? 0,
        ]);

        Log::info('SIFEN: Documento Procesado', ['cdc' => $cdc, 'venta_id' => $venta->id, 'estado' => $estado]);

        return [
            'cdc'              => $cdc,
            'qr_url'           => $qrUrl,
            'estado'           => $estado,
            'numero_protocolo' => $protocolo,
            'xml_path'         => $xmlRelativePath,
            'kude_path'        => $kudeRelativePath,
        ];
    }

    private function generateKude(SaleModel $venta, string $cdc, string $qrUrl, string $tipo, string $protocolo): string
    {
        $emisor = $this->emisorData();
        $config = \App\Infrastructure\Settings\EmpresaSettings::get();
        
        // Logo en base64 para PDF
        $logoBase64 = null;
        if ($config->logo_path) {
            $logoPath = $config->logoAbsPath();
            if ($logoPath && file_exists($logoPath)) {
                $logoBase64 = base64_encode(file_get_contents($logoPath));
            }
        }

        // QR en base64 (SVG) para PDF
        // Se usa SVG para evitar la dependencia de Imagick (necesaria para PNG)
        $qrSvg = QrCode::format('svg')->size(100)->margin(0)->generate($qrUrl);
        $qrBase64 = base64_encode((string)$qrSvg);

        $data = [
            'venta'      => $venta,
            'emisor'     => $emisor,
            'cdc'        => $cdc,
            'qr_base64'  => $qrBase64,
            'logo_base64'=> $logoBase64,
            'nro_doc'    => $venta->id, // Podría ser numero_venta formateado
            'tipo_desc'  => $tipo === '01' ? 'FACTURA' : 'NOTA DE CRÉDITO',
            'protocolo'  => $protocolo
        ];

        $pdf = Pdf::loadView('pdf.sifen.kude', $data);
        $pdf->setPaper('A4', 'portrait');

        $filename = "sifen/pdf/kude_{$cdc}.pdf";
        Storage::disk('public')->put($filename, $pdf->output());

        return $filename;
    }

    private function enviarAlWebService(string $xmlFirmado, string $cdc): array
    {
        $endpoint = config('sifen.url_produccion');
        $response = Http::withHeaders(['X-CDC' => $cdc])
            ->withBody($xmlFirmado, 'application/xml')
            ->timeout(config('sifen.timeout_segundos', 30))
            ->post("{$endpoint}/de/");

        $this->validator->validate($response);
        return $response->json() ?? ['estado' => 'APROBADO'];
    }

    private function buildQrUrl(string $cdc, SaleModel $venta): string
    {
        $base   = config('sifen.qr_base_url', 'https://ekuatia.set.gov.py/consultas/qr');
        $params = http_build_query([
            'nVersion'    => '150',
            'Id'          => $cdc,
            'dFeEmiDE'    => now()->format('Y-m-d\TH:i:s'),
            'dRucRec'     => $venta->cliente->ruc ?? '',
            'dTotGralOpe' => (int)$venta->precio_venta_moneda,
            'cHashQR'     => hash('sha256', $cdc . config('sifen.ruc_emisor')),
        ]);
        return "{$base}?{$params}";
    }

    private function emisorData(): array
    {
        return [
            'ruc'             => config('sifen.ruc_emisor'),
            'razon_social'    => config('sifen.razon_social'),
            'nombre_fantasia' => config('sifen.nombre_fantasia'),
            'actividad_eco'   => config('sifen.actividad_economica'),
            'timbrado'        => config('sifen.numero_timbrado'),
            'establecimiento' => config('sifen.establecimiento'),
            'punto'           => config('sifen.punto_expedicion'),
            'direccion'       => config('sifen.direccion'),
            'telefono'        => config('sifen.telefono'),
            'email'           => config('sifen.email'),
        ];
    }
}
