<?php

declare(strict_types=1);

namespace App\Application\Vehicle;

use App\Infrastructure\Http\Requests\StoreVehicleRequest;

final readonly class CreateVehicleDTO
{
    public function __construct(
        public string  $numeroChasis,
        public string  $marca,
        public string  $modelo,
        public int     $anio,
        public string  $tipoVehiculo,
        public string  $estado,
        public string  $monedaCosto,
        public float   $costoOrigenUsd,
        public float   $costoOrigenMoneda,
        public ?string $numeroMotor,
        public ?string $color,
        public int     $kilometraje,
        public ?int    $proveedorId,
        public ?float  $tasaCambioCompra,
        public ?float  $precioContadoUsd,
        public ?float  $preciosCuotasUsd,
        public ?string $observaciones,
        public array   $imagenes,
        // Catálogo web
        public ?string $motorDescripcion,
        public ?int    $potenciaHp,
        public ?int    $parNm,
        public ?string $tipoTraccion,
        public ?string $tipoTransmision,
        public ?string $cabina,
        public ?string $normaEuro,
        public ?float  $pesoBrutoT,
        public ?int    $depositoLitros,
        public ?string $neumaticos,
        public ?string $descripcionPublica,
        public ?array  $equipamiento,
        public bool    $publicarEnWeb,
        public bool    $mostrarPrecio,
    ) {}

    public static function fromRequest(StoreVehicleRequest $request): self
    {
        return new self(
            numeroChasis:       $request->validated('numero_chasis'),
            marca:              $request->validated('marca'),
            modelo:             $request->validated('modelo'),
            anio:               (int) $request->validated('anio'),
            tipoVehiculo:       $request->validated('tipo_vehiculo'),
            estado:             $request->validated('estado'),
            monedaCosto:        $request->validated('moneda_costo'),
            costoOrigenUsd:     (float) $request->validated('costo_origen_usd'),
            costoOrigenMoneda:  (float) $request->validated('costo_origen_moneda'),
            numeroMotor:        $request->validated('numero_motor'),
            color:              $request->validated('color'),
            kilometraje:        (int) ($request->validated('kilometraje') ?? 0),
            proveedorId:        $request->validated('proveedor_id') ? (int) $request->validated('proveedor_id') : null,
            tasaCambioCompra:   $request->validated('tasa_cambio_compra') ? (float) $request->validated('tasa_cambio_compra') : null,
            precioContadoUsd:   $request->validated('precio_contado_usd') ? (float) $request->validated('precio_contado_usd') : null,
            preciosCuotasUsd:   $request->validated('precio_cuotas_usd') ? (float) $request->validated('precio_cuotas_usd') : null,
            observaciones:      $request->validated('observaciones'),
            imagenes:           $request->file('imagenes') ?? [],
            // Catálogo web
            motorDescripcion:   $request->validated('motor_descripcion'),
            potenciaHp:         $request->validated('potencia_hp') !== null ? (int) $request->validated('potencia_hp') : null,
            parNm:              $request->validated('par_nm') !== null ? (int) $request->validated('par_nm') : null,
            tipoTraccion:       $request->validated('tipo_traccion'),
            tipoTransmision:    $request->validated('tipo_transmision'),
            cabina:             $request->validated('cabina'),
            normaEuro:          $request->validated('norma_euro'),
            pesoBrutoT:         $request->validated('peso_bruto_t') !== null ? (float) $request->validated('peso_bruto_t') : null,
            depositoLitros:     $request->validated('deposito_litros') !== null ? (int) $request->validated('deposito_litros') : null,
            neumaticos:         $request->validated('neumaticos'),
            descripcionPublica: $request->validated('descripcion_publica'),
            equipamiento:       $request->validated('equipamiento'),
            publicarEnWeb:      (bool) $request->validated('publicar_en_web', false),
            mostrarPrecio:      (bool) $request->validated('mostrar_precio', true),
        );
    }

    public function toArray(): array
    {
        $data = array_filter([
            'numero_chasis'             => $this->numeroChasis,
            'marca'                     => $this->marca,
            'modelo'                    => $this->modelo,
            'anio'                      => $this->anio,
            'tipo_vehiculo'             => $this->tipoVehiculo,
            'estado'                    => $this->estado,
            'moneda_costo'              => $this->monedaCosto,
            'costo_origen_usd'          => $this->costoOrigenUsd,
            'costo_origen_moneda'       => $this->costoOrigenMoneda,
            'numero_motor'              => $this->numeroMotor,
            'color'                     => $this->color,
            'kilometraje'               => $this->kilometraje,
            'proveedor_id'              => $this->proveedorId,
            'tasa_cambio_compra'        => $this->tasaCambioCompra,
            'precio_venta_sugerido_usd' => $this->precioContadoUsd,
            'observaciones'             => $this->observaciones,
            // Catálogo web — nullable opcionales
            'motor_descripcion'         => $this->motorDescripcion,
            'potencia_hp'               => $this->potenciaHp,
            'par_nm'                    => $this->parNm,
            'tipo_traccion'             => $this->tipoTraccion,
            'tipo_transmision'          => $this->tipoTransmision,
            'cabina'                    => $this->cabina,
            'norma_euro'                => $this->normaEuro,
            'peso_bruto_t'              => $this->pesoBrutoT,
            'deposito_litros'           => $this->depositoLitros,
            'neumaticos'                => $this->neumaticos,
            'descripcion_publica'       => $this->descripcionPublica,
            'equipamiento'              => $this->equipamiento ?: null,
        ], fn($v) => $v !== null);

        // Booleanos siempre presentes (no filtrar con array_filter)
        $data['publicar_en_web'] = $this->publicarEnWeb;
        $data['mostrar_precio']  = $this->mostrarPrecio;

        return $data;
    }
}
