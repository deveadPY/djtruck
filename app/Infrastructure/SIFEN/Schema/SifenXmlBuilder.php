<?php

declare(strict_types=1);

namespace App\Infrastructure\SIFEN\Schema;

use App\Infrastructure\Persistence\Eloquent\Models\SaleModel;

/**
 * SifenXmlBuilder — Construye el XML del Documento Electrónico (DE)
 * según esquema SET Paraguay versión 150.
 *
 * Documentación: https://ekuatia.set.gov.py/portal/ekuatia/detail
 */
class SifenXmlBuilder
{
    public function buildFactura(array $data): string
    {
        $cdc   = $data['cdc'];
        $venta = $data['venta'];
        $emisor= $data['emisor'];

        $fechaHora = now()->format('Y-m-d\TH:i:s');
        $rucReceptor = $venta->cliente->ruc ?? '0000000-0';
        $razonReceptor = $venta->cliente->razon_social ?? 'CONSUMIDOR FINAL';
        $totalGs = number_format($venta->precio_venta_moneda, 0, '.', '');

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rDE xmlns="http://ekuatia.set.gov.py/sifen/xsd"
     xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
     xsi:schemaLocation="http://ekuatia.set.gov.py/sifen/xsd siRecepDE_v150.xsd">
  <DE Id="{$cdc}">
    <gOpeDE>
      <iTipEmi>1</iTipEmi>
      <dDesTipEmi>Normal</dDesTipEmi>
      <dKGruCF></dKGruCF>
    </gOpeDE>
    <gTimb>
      <iTiDE>1</iTiDE>
      <dDesTiDE>Factura electrónica</dDesTiDE>
      <dNumTim>{$emisor['timbrado']}</dNumTim>
      <dEst>{$emisor['establecimiento']}</dEst>
      <dPunExp>{$emisor['punto']}</dPunExp>
      <dNumDoc>{$venta->id}</dNumDoc>
      <dSerieNum></dSerieNum>
      <dFeIniVig>2024-01-01</dFeIniVig>
      <dFecFirma>{$fechaHora}</dFecFirma>
      <dSisFact>2</dSisFact>
    </gTimb>
    <gDatGralOpe>
      <dFeEmiDE>{$fechaHora}</dFeEmiDE>
      <gOpeCom>
        <iTipTra>1</iTipTra>
        <dDesTipTra>Venta de mercadería</dDesTipTra>
        <iTImp>1</iTImp>
        <dDesTImp>IVA</dDesTImp>
        <cMoneOpe>PYG</cMoneOpe>
        <dDesMoneOpe>Guaraní</dDesMoneOpe>
        <dCondTipCam>1</dCondTipCam>
      </gOpeCom>
      <gEmis>
        <dRucEm>{$emisor['ruc']}</dRucEm>
        <dDVEmi>1</dDVEmi>
        <iTipCont>2</iTipCont>
        <dNomEmi>{$emisor['razon_social']}</dNomEmi>
        <dNomFanEmi>{$emisor['nombre_fantasia']}</dNomFanEmi>
        <dDirEmi>{$emisor['direccion']}</dDirEmi>
        <dTelEmi>{$emisor['telefono']}</dTelEmi>
        <dEmailE>{$emisor['email']}</dEmailE>
        <gActEco>
          <cActEco>{$emisor['actividad_eco']}</cActEco>
          <dDesActEco>Venta de vehículos automotores</dDesActEco>
        </gActEco>
      </gEmis>
      <gDatRec>
        <iNatRec>1</iNatRec>
        <iTiOpe>1</iTiOpe>
        <cPaisRec>PRY</cPaisRec>
        <dDesPaisRe>Paraguay</dDesPaisRe>
        <iTiContRec>2</iTiContRec>
        <dRucRec>{$rucReceptor}</dRucRec>
        <dDVRec>0</dDVRec>
        <dNomRec>{$razonReceptor}</dNomRec>
      </gDatRec>
    </gDatGralOpe>
    <gDtipDE>
      <gCamFE>
        <iIndPres>1</iIndPres>
        <dDesIndPres>Operación presencial</dDesIndPres>
      </gCamFE>
    </gDtipDE>
    <gTotSub>
      <dSubExe>0</dSubExe>
      <dSubExo>0</dSubExo>
      <dSub5>0</dSub5>
      <dSub10>{$totalGs}</dSub10>
      <dTotOpe>{$totalGs}</dTotOpe>
      <dTotDesc>0</dTotDesc>
      <dTotDescGlotem>0</dTotDescGlotem>
      <dTotAntItem>0</dTotAntItem>
      <dTotAnt>0</dTotAnt>
      <dPorcDescTotal>0</dPorcDescTotal>
      <dDescTotal>0</dDescTotal>
      <dAnticipo>0</dAnticipo>
      <dRedon>0</dRedon>
      <dTotGralOpe>{$totalGs}</dTotGralOpe>
      <dIVA5>0</dIVA5>
      <dIVA10>{$this->calcIva10($venta->precio_venta_moneda)}</dIVA10>
      <dTotIVA>{$this->calcIva10($venta->precio_venta_moneda)}</dTotIVA>
      <dBaseGrav5>0</dBaseGrav5>
      <dBaseGrav10>{$totalGs}</dBaseGrav10>
      <dTBaseGrav>{$totalGs}</dTBaseGrav>
    </gTotSub>
  </DE>
</rDE>
XML;
    }

    private function calcIva10(float $totalConIva): float
    {
        return round($totalConIva / 11, 0);
    }
}

// ─────────────────────────────────────────────────────────────
// CdcGenerator — Genera el Código de Control (CDC) de 44 dígitos
// Estructura: 2(tipoDoc) + 6(RUC sin DV) + 1(DV) + 3(establecimiento) +
//             3(punto) + 7(número) + 1(tipoContrib) + 8(fecha YYYYMMDD) +
//             1(tipoEmisión) + 9(código seguridad) + 1(DV CDC) = 44
// ─────────────────────────────────────────────────────────────
class CdcGenerator
{
    public function generate(
        string $tipoDocumento,
        string $establecimiento,
        string $punto,
        int    $numero,
        string $contribuyenteRuc,
        string $tipoContribuyente,
        string $fecha,
        string $tipoEmision,
    ): string {
        [$rucSinDv, $dv] = $this->splitRuc($contribuyenteRuc);

        $cdc = sprintf(
            '%02d%07d%d%03d%03d%07d%d%08d%d%09d',
            (int) $tipoDocumento,
            (int) $rucSinDv,
            (int) $dv,
            (int) $establecimiento,
            (int) $punto,
            $numero,
            (int) $tipoContribuyente,
            (int) $fecha,
            (int) $tipoEmision,
            random_int(100000000, 999999999), // código de seguridad
        );

        return $cdc . $this->calcularDv($cdc);
    }

    private function splitRuc(string $ruc): array
    {
        $parts = explode('-', $ruc);
        return count($parts) === 2 ? $parts : [$ruc, '0'];
    }

    private function calcularDv(string $cdc): int
    {
        // Algoritmo módulo 11 base 2
        $suma   = 0;
        $factor = 2;
        for ($i = strlen($cdc) - 1; $i >= 0; $i--) {
            $suma   += (int)$cdc[$i] * $factor;
            $factor  = $factor >= 9 ? 2 : $factor + 1;
        }
        $resto = $suma % 11;
        return $resto < 2 ? 0 : 11 - $resto;
    }
}
