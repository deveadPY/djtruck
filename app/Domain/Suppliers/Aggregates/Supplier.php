<?php

declare(strict_types=1);

namespace App\Domain\Suppliers\Aggregates;

use App\Domain\Suppliers\Exceptions\InvalidSupplierDataException;
use App\Domain\Suppliers\ValueObjects\SupplierId;

/**
 * Supplier Aggregate Root — proveedor.
 *
 * Invariantes:
 * - Razón social no vacía.
 * - Tipo válido: FABRICANTE | DISTRIBUIDOR | IMPORTADOR | SERVICIO | OTRO.
 * - Descuento pago anticipado en [0, 100] %.
 * - Días de crédito en [0, 365].
 * - Score actual en [0, 100].
 */
final class Supplier
{
    private const TIPOS_VALIDOS = ['FABRICANTE', 'DISTRIBUIDOR', 'IMPORTADOR', 'SERVICIO', 'OTRO'];

    private ?SupplierId $id;
    private ?string $rucRutNit;
    private string $razonSocial;
    private ?string $nombreFantasia;
    private string $pais;
    private string $tipo;
    private string $monedaPrincipal;
    private int $diasCredito;
    private float $descuentoPagoAnticipadoPct;
    private ?string $email;
    private ?string $telefono;
    private ?string $direccion;
    private ?string $ciudad;
    private ?string $sitioWeb;
    private ?string $contactoPrincipal;
    private ?string $banco;
    private ?string $cuentaBancaria;
    private float $scoreActual;
    private ?string $observaciones;
    private bool $activo;

    private function __construct(
        ?string $rucRutNit,
        string $razonSocial,
        ?string $nombreFantasia,
        string $pais,
        string $tipo,
        string $monedaPrincipal,
        int $diasCredito,
        float $descuentoPagoAnticipadoPct,
        ?string $email,
        ?string $telefono,
        ?string $direccion,
        ?string $ciudad,
        ?string $sitioWeb,
        ?string $contactoPrincipal,
        ?string $banco,
        ?string $cuentaBancaria,
        float $scoreActual,
        ?string $observaciones,
        bool $activo
    ) {
        $this->id = null;
        $this->rucRutNit = $rucRutNit;
        $this->razonSocial = $razonSocial;
        $this->nombreFantasia = $nombreFantasia;
        $this->pais = $pais;
        $this->tipo = $tipo;
        $this->monedaPrincipal = $monedaPrincipal;
        $this->diasCredito = $diasCredito;
        $this->descuentoPagoAnticipadoPct = $descuentoPagoAnticipadoPct;
        $this->email = $email;
        $this->telefono = $telefono;
        $this->direccion = $direccion;
        $this->ciudad = $ciudad;
        $this->sitioWeb = $sitioWeb;
        $this->contactoPrincipal = $contactoPrincipal;
        $this->banco = $banco;
        $this->cuentaBancaria = $cuentaBancaria;
        $this->scoreActual = $scoreActual;
        $this->observaciones = $observaciones;
        $this->activo = $activo;
    }

    public static function create(
        ?string $rucRutNit,
        string $razonSocial,
        ?string $nombreFantasia = null,
        string $pais = 'PY',
        string $tipo = 'DISTRIBUIDOR',
        string $monedaPrincipal = 'USD',
        int $diasCredito = 0,
        float $descuentoPagoAnticipadoPct = 0,
        ?string $email = null,
        ?string $telefono = null,
        ?string $direccion = null,
        ?string $ciudad = null,
        ?string $sitioWeb = null,
        ?string $contactoPrincipal = null,
        ?string $banco = null,
        ?string $cuentaBancaria = null,
        float $scoreActual = 0,
        ?string $observaciones = null,
        bool $activo = true
    ): self {
        if (trim($razonSocial) === '') {
            throw InvalidSupplierDataException::missingRazonSocial();
        }
        if (!in_array($tipo, self::TIPOS_VALIDOS, true)) {
            throw InvalidSupplierDataException::invalidTipo($tipo);
        }
        if ($descuentoPagoAnticipadoPct < 0 || $descuentoPagoAnticipadoPct > 100) {
            throw InvalidSupplierDataException::invalidDescuentoAnticipado($descuentoPagoAnticipadoPct);
        }
        if ($diasCredito < 0 || $diasCredito > 365) {
            throw InvalidSupplierDataException::invalidDiasCredito($diasCredito);
        }
        if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidSupplierDataException("Email inválido: {$email}");
        }
        if ($scoreActual < 0 || $scoreActual > 100) {
            throw new InvalidSupplierDataException("Score debe estar en [0, 100]: {$scoreActual}");
        }

        return new self(
            $rucRutNit ? trim($rucRutNit) : null,
            trim($razonSocial),
            $nombreFantasia ? trim($nombreFantasia) : null,
            strtoupper(substr($pais, 0, 2)),
            $tipo,
            strtoupper($monedaPrincipal),
            $diasCredito,
            $descuentoPagoAnticipadoPct,
            $email ? strtolower(trim($email)) : null,
            $telefono ? trim($telefono) : null,
            $direccion ? trim($direccion) : null,
            $ciudad ? trim($ciudad) : null,
            $sitioWeb ? trim($sitioWeb) : null,
            $contactoPrincipal ? trim($contactoPrincipal) : null,
            $banco ? trim($banco) : null,
            $cuentaBancaria ? trim($cuentaBancaria) : null,
            $scoreActual,
            $observaciones,
            $activo
        );
    }

    public function withId(SupplierId $id): self
    {
        $clone = clone $this;
        $clone->id = $id;
        return $clone;
    }

    public function updateScore(float $score): void
    {
        if ($score < 0 || $score > 100) {
            throw new InvalidSupplierDataException("Score debe estar en [0, 100]: {$score}");
        }
        $this->scoreActual = round($score, 2);
    }

    public function ensureCanBuy(): void
    {
        if (!$this->activo) {
            throw new InvalidSupplierDataException("El proveedor está inactivo y no puede operarse con él.");
        }
        if ($this->scoreActual > 0 && $this->scoreActual < 30) {
            throw new InvalidSupplierDataException(
                "El proveedor tiene score crítico ({$this->scoreActual}/100). Requiere aprobación gerencial."
            );
        }
    }

    public function deactivate(): void { $this->activo = false; }
    public function activate(): void   { $this->activo = true; }

    public function toArray(): array
    {
        return [
            'id'                            => $this->id?->value(),
            'ruc_rut_nit'                   => $this->rucRutNit,
            'razon_social'                  => $this->razonSocial,
            'nombre_fantasia'               => $this->nombreFantasia,
            'pais'                          => $this->pais,
            'tipo'                          => $this->tipo,
            'moneda_principal'              => $this->monedaPrincipal,
            'dias_credito'                  => $this->diasCredito,
            'descuento_pago_anticipado_pct' => $this->descuentoPagoAnticipadoPct,
            'email'                         => $this->email,
            'telefono'                      => $this->telefono,
            'direccion'                     => $this->direccion,
            'ciudad'                        => $this->ciudad,
            'sitio_web'                     => $this->sitioWeb,
            'contacto_principal'            => $this->contactoPrincipal,
            'banco'                         => $this->banco,
            'cuenta_bancaria'               => $this->cuentaBancaria,
            'score_actual'                  => $this->scoreActual,
            'observaciones'                 => $this->observaciones,
            'activo'                        => $this->activo,
        ];
    }

    public function getId(): ?SupplierId { return $this->id; }
    public function getRazonSocial(): string { return $this->razonSocial; }
    public function getRucRutNit(): ?string { return $this->rucRutNit; }
    public function getDiasCredito(): int { return $this->diasCredito; }
    public function isActivo(): bool { return $this->activo; }
    public function getScoreActual(): float { return $this->scoreActual; }
}
