<?php

declare(strict_types=1);

namespace App\Domain\Parts\Aggregates;

use App\Domain\Parts\Exceptions\InsufficientPartStockException;
use App\Domain\Parts\Exceptions\InvalidPartCodeException;
use App\Domain\Parts\ValueObjects\PartCode;
use App\Domain\Parts\ValueObjects\PartId;
use App\Domain\Parts\ValueObjects\StockLevel;

/**
 * Part Aggregate Root — repuesto.
 *
 * Invariantes:
 * - Código único, alfanumérico válido.
 * - Stock actual ≥ 0.
 * - Stock comprometido ≤ stock actual.
 * - Costo promedio ≥ 0.
 * - Precio venta ≥ costo promedio (o nulo).
 */
final class Part
{
    private ?PartId $id;
    private PartCode $codigo;
    private string $descripcion;
    private ?string $codigoBarras;
    private ?string $marcaCompatible;
    private ?int $categoriaId;
    private ?int $ubicacionId;
    private string $unidadMedida;
    private StockLevel $stock;
    private float $costoPromedioUsd;
    private ?float $precioVentaUsd;
    private ?int $proveedorId;
    private bool $activo;

    private function __construct(
        PartCode $codigo,
        string $descripcion,
        ?string $codigoBarras,
        ?string $marcaCompatible,
        ?int $categoriaId,
        ?int $ubicacionId,
        string $unidadMedida,
        StockLevel $stock,
        float $costoPromedioUsd,
        ?float $precioVentaUsd,
        ?int $proveedorId,
        bool $activo
    ) {
        $this->id = null;
        $this->codigo = $codigo;
        $this->descripcion = $descripcion;
        $this->codigoBarras = $codigoBarras;
        $this->marcaCompatible = $marcaCompatible;
        $this->categoriaId = $categoriaId;
        $this->ubicacionId = $ubicacionId;
        $this->unidadMedida = $unidadMedida;
        $this->stock = $stock;
        $this->costoPromedioUsd = $costoPromedioUsd;
        $this->precioVentaUsd = $precioVentaUsd;
        $this->proveedorId = $proveedorId;
        $this->activo = $activo;
    }

    public static function create(
        PartCode $codigo,
        string $descripcion,
        string $unidadMedida = 'UND',
        ?string $codigoBarras = null,
        ?string $marcaCompatible = null,
        ?int $categoriaId = null,
        ?int $ubicacionId = null,
        float $stockInicial = 0,
        float $stockMinimo = 0,
        float $costoPromedioUsd = 0,
        ?float $precioVentaUsd = null,
        ?int $proveedorId = null,
        bool $activo = true,
    ): self {
        if (trim($descripcion) === '') {
            throw new InvalidPartCodeException('La descripción del repuesto es obligatoria.');
        }
        if ($costoPromedioUsd < 0) {
            throw new \InvalidArgumentException("Costo promedio no puede ser negativo: {$costoPromedioUsd}");
        }
        if ($precioVentaUsd !== null && $precioVentaUsd < $costoPromedioUsd) {
            throw new \InvalidArgumentException(
                "Precio venta ({$precioVentaUsd}) no puede ser menor al costo promedio ({$costoPromedioUsd})."
            );
        }

        return new self(
            $codigo,
            trim($descripcion),
            $codigoBarras ? trim($codigoBarras) : null,
            $marcaCompatible ? trim($marcaCompatible) : null,
            $categoriaId,
            $ubicacionId,
            strtoupper($unidadMedida),
            StockLevel::of($stockInicial, 0, $stockMinimo),
            $costoPromedioUsd,
            $precioVentaUsd,
            $proveedorId,
            $activo
        );
    }

    public function withId(PartId $id): self
    {
        $clone = clone $this;
        $clone->id = $id;
        return $clone;
    }

    /**
     * Ingreso de stock (compra) — actualiza costo promedio ponderado.
     * Fórmula: nuevoCosto = ((stockActual * costoActual) + (cantidad * costoIngreso)) / (stockActual + cantidad)
     */
    public function recibirStock(float $cantidad, float $costoUnitarioUsd): void
    {
        if ($cantidad <= 0) {
            throw new \InvalidArgumentException("Cantidad debe ser positiva: {$cantidad}");
        }

        $stockTotalAnterior = $this->stock->actual * $this->costoPromedioUsd;
        $stockTotalNuevo = $cantidad * $costoUnitarioUsd;
        $stockActualNuevo = $this->stock->actual + $cantidad;

        $this->costoPromedioUsd = $stockActualNuevo > 0
            ? round(($stockTotalAnterior + $stockTotalNuevo) / $stockActualNuevo, 4)
            : 0;

        $this->stock = StockLevel::of(
            $stockActualNuevo,
            $this->stock->comprometido,
            $this->stock->minimo
        );
    }

    /**
     * Salida de stock (venta) — valida disponibilidad.
     */
    public function despacharStock(float $cantidad): void
    {
        if ($cantidad <= 0) {
            throw new \InvalidArgumentException("Cantidad debe ser positiva: {$cantidad}");
        }

        if (!$this->stock->alcanzaPara($cantidad)) {
            throw new InsufficientPartStockException(
                $this->id?->value() ?? 0,
                $this->codigo->value(),
                $cantidad,
                $this->stock->disponible()
            );
        }

        $this->stock = StockLevel::of(
            $this->stock->actual - $cantidad,
            $this->stock->comprometido,
            $this->stock->minimo
        );
    }

    /**
     * Reserva stock (presupuesto activo).
     */
    public function reservar(float $cantidad): void
    {
        if (!$this->stock->alcanzaPara($cantidad)) {
            throw new InsufficientPartStockException(
                $this->id?->value() ?? 0,
                $this->codigo->value(),
                $cantidad,
                $this->stock->disponible()
            );
        }

        $this->stock = StockLevel::of(
            $this->stock->actual,
            $this->stock->comprometido + $cantidad,
            $this->stock->minimo
        );
    }

    public function liberarReserva(float $cantidad): void
    {
        $nuevoComprometido = max(0, $this->stock->comprometido - $cantidad);
        $this->stock = StockLevel::of(
            $this->stock->actual,
            $nuevoComprometido,
            $this->stock->minimo
        );
    }

    /**
     * Ajuste manual con motivo (merma, robo, inventario, etc.).
     */
    public function ajustarStock(float $nuevaCantidad): void
    {
        if ($nuevaCantidad < 0) {
            throw new \InvalidArgumentException("Stock no puede ser negativo: {$nuevaCantidad}");
        }
        if ($nuevaCantidad < $this->stock->comprometido) {
            throw new \InvalidArgumentException(
                "No se puede ajustar a {$nuevaCantidad} (hay {$this->stock->comprometido} comprometidos)."
            );
        }

        $this->stock = StockLevel::of(
            $nuevaCantidad,
            $this->stock->comprometido,
            $this->stock->minimo
        );
    }

    public function actualizarPrecioVenta(float $precioVentaUsd): void
    {
        if ($precioVentaUsd < 0) {
            throw new \InvalidArgumentException("Precio venta no puede ser negativo: {$precioVentaUsd}");
        }
        $this->precioVentaUsd = $precioVentaUsd;
    }

    public function desactivar(): void { $this->activo = false; }
    public function activar(): void    { $this->activo = true; }

    public function toArray(): array
    {
        return [
            'id'                 => $this->id?->value(),
            'codigo'             => $this->codigo->value(),
            'descripcion'        => $this->descripcion,
            'codigo_barras'      => $this->codigoBarras,
            'marca_compatible'   => $this->marcaCompatible,
            'categoria_id'       => $this->categoriaId,
            'ubicacion_id'       => $this->ubicacionId,
            'unidad_medida'      => $this->unidadMedida,
            'stock_actual'       => $this->stock->actual,
            'stock_comprometido' => $this->stock->comprometido,
            'stock_minimo'       => $this->stock->minimo,
            'costo_promedio_usd' => $this->costoPromedioUsd,
            'precio_venta_usd'   => $this->precioVentaUsd,
            'proveedor_id'       => $this->proveedorId,
            'activo'             => $this->activo,
        ];
    }

    // Getters
    public function getId(): ?PartId { return $this->id; }
    public function getCodigo(): PartCode { return $this->codigo; }
    public function getStock(): StockLevel { return $this->stock; }
    public function getCostoPromedioUsd(): float { return $this->costoPromedioUsd; }
    public function getPrecioVentaUsd(): ?float { return $this->precioVentaUsd; }
    public function isActivo(): bool { return $this->activo; }
}
