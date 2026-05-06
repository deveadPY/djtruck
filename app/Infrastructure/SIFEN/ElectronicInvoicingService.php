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

final class ElectronicInvoicingService
{
    public function __construct(
        private readonly SifenXmlBuilder        $xmlBuilder,
        private readonly CdcGenerator           $cdcGenerator,
        private readonly XmlDsigSigner          $signer,
        private readonly SifenResponseValidator $validator,
    ) {}

    public function emitirFactura(SaleModel $venta): array
    {
        $venta->loadMissing(['cliente', 'vehiculo', 'detallesPago']);

        $cdc = $this->cdcGenerator->generate(
            tipoDocumento:     '01',
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
        ]);

        $xmlFirmado = $this->signer->sign(
            $xmlSinFirma,
            config('sifen.cert_path', storage_path('certs/certificado.p12')),
            config('sifen.cert_passphrase', ''),
        );

        $qrUrl = $this->buildQrUrl($cdc, $venta);

        // Guardar XML localmente (para auditoría)
        $xmlPath = storage_path("app/sifen/de_{$cdc}.xml");
        @mkdir(dirname($xmlPath), 0755, true);
        file_put_contents($xmlPath, $xmlFirmado);

        // Enviar a SIFEN si no es sandbox o si está configurado
        $sifenResponse = ['estado' => 'SANDBOX', 'numero_protocolo' => null];
        if (config('sifen.ambiente') === 'produccion') {
            $sifenResponse = $this->enviarAlWebService($xmlFirmado, $cdc);
        }

        $venta->update([
            'cdc_sifen'            => $cdc,
            'numero_timbrado'      => config('sifen.numero_timbrado'),
            'fecha_emision_fe'     => now(),
            'tiene_factura_electronica' => true,
            'updated_by'           => 0,
        ]);

        Log::info('SIFEN: DE emitido', ['cdc' => $cdc, 'venta_id' => $venta->id]);

        return [
            'cdc'              => $cdc,
            'qr_url'           => $qrUrl,
            'estado'           => $sifenResponse['estado'],
            'numero_protocolo' => $sifenResponse['numero_protocolo'],
            'xml_path'         => "sifen/de_{$cdc}.xml",
        ];
    }

    private function enviarAlWebService(string $xmlFirmado, string $cdc): array
    {
        $endpoint = config('sifen.url_produccion');
        $response = Http::withHeaders(['Content-Type' => 'application/xml', 'X-CDC' => $cdc])
            ->timeout(config('sifen.timeout_segundos', 30))
            ->post("{$endpoint}/de/", $xmlFirmado);

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
            'dTotGralOpe' => $venta->precio_venta_moneda,
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
