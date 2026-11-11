# Plan de mejoras del flujo de trabajo — DJ Trucks ERP

Fecha: 2026-11-11 · Base: `main` = producción = GitHub (`92aefb9`)

## Diagnóstico (hechos verificados)

| # | Problema | Causa raíz | Evidencia |
|---|----------|-----------|-----------|
| 1 | Eliminar imagen del camión → **error 500** | La tabla `vehiculo_imagenes` en producción no tiene `deleted_by` ni `updated_by`, pero el repositorio las escribe | `EloquentVehicleImageRepository.php:51`; log de prod: 14 errores `Unknown column 'deleted_by'` (último hoy) |
| 2 | Más 500 latentes del mismo tipo | Modelos declaran columnas que no existen en prod: `clientes` (updated_by, deleted_by), `documentos` (updated_by, deleted_by), `cajas` (updated_by), `compras` (updated_by), `movimientos_caja` (tasa_cambio, referencia, venta_id, cuota_id, updated_by) | Comparación modelo vs esquema real de prod |
| 3 | Gasto del camión sin comprobante | El form no es `multipart` ni tiene campo archivo; el controlador inserta con `DB::table` y validación inline (sin FormRequest); además **no guarda `tasa_cambio`** | `gastos/create.blade.php:27`, `GastoWebController.php:21-48` |
| 4 | Toma de vehículo **no suma stock** | La pantalla de ventas no da de alta el vehículo recibido: solo cambia a `TOMA` un vehículo ya existente. `TOMA` está excluido del KPI "disponibles" y de la venta, y no hay transición `TOMA → DISPONIBLE`. El registrador correcto (`TradeInVehicleRegistrar`) existe pero solo lo usa una API que ninguna pantalla llama | `PaymentProcessor.php:60-67`, `DashboardController.php:16`, `VehicleStateValidator.php:14` |
| 5 | Cancelar venta con toma | El canje vuelve siempre a `DISPONIBLE` (no al estado previo); un vehículo creado por la toma quedaría en stock | `CancelSaleUseCase.php:68-79` |
| 6 | Venta lenta | 4 pasos + modal; precio y descuento duplicados (moneda + USD); pagos solo en USD; selector de caja ignorado; todos los clientes cargados con N+1 de deuda; si falla la validación se pierde el carrito | `ventas/create.blade.php:383-405`, `VentaWebController.php:101-113` |
| 7 | KPIs no clicables | Ninguna tarjeta es enlace (aunque `.stat-card` muestra cursor de mano); 4 estilos distintos de tarjeta, sin componente | `dashboard`, `finance`, `reportes`, `notificaciones`, `cotizaciones` |
| 8 | Tema no global | ~25 vistas con `text-white`/`bg-white/N` (invisibles en modo claro); variables usadas pero no definidas (`--success`, `--danger`, `--warning`, `--bg-card`); `ventas/create` usa paleta propia (372 clases slate/gray) | ver anexo |
| 9 | Emojis en UI | 19 archivos (planes_cuotas, ventas, finance/caja, facturas, gastos, 2fa…) en un sistema que usa Heroicons SVG | ver anexo |
| 10 | Entorno local roto para tests | `composer install` falla (lock incompatible con PHP local) y MySQL local apagado → no se pueden correr tests | — |

## Plan por fases (cada fase = rama + PR + deploy independiente)

### Fase 0 — Entorno de pruebas (prerrequisito, ~0.5 día)
- Arreglar `composer install` local (versión de PHP / `composer update --lock` controlado) y correr la suite Pest sobre SQLite (ya configurada en `phpunit.xml`).
- Criterio: `vendor/bin/pest` corre en verde en local.

### Fase 1 — Errores 500 (urgente, ~0.5 día) · rama `fix/columnas-faltantes-prod`
- Una migración idempotente (`Schema::hasColumn`) que agrega las columnas faltantes del punto 1 y 2, con `down()`.
- Test: eliminar imagen de vehículo responde 302 y deja `deleted_by`.
- Deploy: backup (`deployment/production-backup.sh`) → `migrate --force`.
- Criterio: eliminar imagen funciona en prod; el chequeo modelo-vs-esquema sale vacío.

### Fase 2 — Comprobante en gastos (~1 día) · rama `feat/gasto-comprobante`
- Reutilizar la tabla polimórfica `documentos` (no columna nueva): `documentable_type = gastos_vehiculo`.
- Form `multipart` con campo opcional `comprobante` (`mimes:pdf,jpg,jpeg,png|max:4096`, igual que compras).
- Mover validación a `StoreVehicleExpenseRequest`; guardar también `tasa_cambio`.
- Mostrar ícono/enlace del comprobante en el historial de gastos del detalle del vehículo.
- Tests: con y sin comprobante, archivo inválido rechazado.

### Fase 3 — Toma de vehículo correcta (~2 días) · rama `fix/toma-vehiculo-stock`
- En el pago "Vehículo en canje", permitir **alta rápida** del vehículo recibido (marca, modelo, año, chasis, valor de toma) además de elegir uno existente.
- El alta usa `TradeInVehicleRegistrar` (ya existe): crea el vehículo con `costo_origen_usd = valor_toma`, `venta_canje_origen_id`, y estado **`DISPONIBLE`** (o `EN_PREPARACION` si se decide revisar antes; decisión del negocio).
- Selector de canje: excluir vehículos propios en venta.
- Cancelación: si el vehículo fue creado por la toma → baja lógica; si existía → volver a su estado previo (guardarlo en el pago).
- Corregir `valor_libro_usd` → `valor_libro` en `ProcessHybridSaleUseCase.php:138`.
- Tests: venta con toma suma 1 al stock disponible; cancelar lo revierte.

### Fase 4 — Venta rápida (~2-3 días) · rama `feat/venta-rapida`
- Pagos en la moneda de la venta (PYG/USD/BRL) con conversión a USD en servidor y **selector de caja** (hoy ignorado).
- Precio/descuento: un solo campo en la moneda elegida; USD se calcula (no se tipea dos veces).
- Búsqueda de clientes con autocompletado por AJAX (sin cargar todos ni N+1).
- Contado simple en 2 pasos (vehículo+cliente → cobro), cuotas mantiene el asistente.
- Conservar el carrito y el cliente si falla la validación (`old()`).
- Mensajes de error de negocio claros (no la excepción cruda).

### Fase 5 — KPIs navegables (~1 día) · rama `feat/kpi-navegables`
- Componente Blade `<x-kpi-card :href="..." />` que reemplaza los 4 estilos de tarjeta.
- Mapear cada KPI a su módulo (dashboard → ventas/reportes/vehículos/notificaciones; finanzas → caja con `tipo`; reportes → ventas/notificaciones).
- `notificaciones` abre la pestaña según `?tab=` / `#hash`; `vehicles.index` acepta filtro `estado`.

### Fase 6 — Tema global y sin emojis (~2 días) · rama `refactor/tema-global`
- Definir en el layout las variables faltantes (`--success`, `--danger`, `--warning`, `--bg-card`).
- Reemplazar `text-white`, `bg-white/N`, `border-white/N` y hex sueltos por variables/clases del sistema, vista por vista (prioridad: reportes, ventas/show, configuracion/email, usuarios, facturas, compras, proveedores, notificaciones, planes_cuotas).
- Unificar `layouts/auth` y `login` con las variables del layout principal.
- Emojis → Heroicons SVG (o texto en `<option>`).
- Verificación visual en modo claro y oscuro de cada módulo.

### Mejoras adicionales detectadas (incluidas en las fases)
- Chequeo automático modelo-vs-esquema como test, para que no vuelvan los 500 por columnas.
- Tailwind por CDN en producción: evaluar compilarlo (rendimiento y estabilidad) — opcional, después de la Fase 6.

## Orden y flujo de trabajo
`main` = producción. Cada fase: rama → tests → PR → merge → backup → deploy (`git pull` + `migrate --force` + `optimize`). Fases 1-3 son correcciones de negocio (prioridad); 4-6 son experiencia de uso.

## Decisiones pendientes del negocio
1. Vehículo recibido en toma: ¿entra como `DISPONIBLE` directo o `EN_PREPARACION` (revisión antes de vender)?
2. Venta rápida: ¿la mayoría de ventas son contado? (define si el modo 2 pasos es el predeterminado).

---

## Estado al 2026-11-11 (implementado, sin desplegar)

Ramas apiladas sobre `main` (cada una contiene las anteriores; la última, `refactor/tema-global`, tiene todo):
`fix/columnas-faltantes-prod` → `feat/gasto-comprobante` → `fix/toma-vehiculo-stock` → `feat/venta-rapida` → `feat/kpi-navegables` → `refactor/tema-global`.

| Fase | Estado | Notas |
|------|--------|-------|
| 0 Tests | Hecho | `APP_URL` fijo en phpunit; Pest corre con PHP 8.3 local (`composer install --ignore-platform-req=php`). 23 tests viejos siguen fallando (mocks de clases `final`, datos de prueba incompletos) — sin regresiones nuevas. |
| 1 Errores 500 | Hecho | Migración `2026_11_11_000001_add_missing_audit_columns` + test modelo-vs-esquema. Extra: usuarios desactivados ya no pueden entrar (ni con sesión abierta); simulación de 0 cuotas. |
| 2 Comprobante gasto | Hecho | Tabla `documentos`; acceso web directo a `public/uploads/documentos` bloqueado (`.htaccess`), se sirve por `/documentos/{id}/download`. |
| 3 Toma de vehículo | Hecho | Entra como `EN_PREPARACION` con valor de toma = monto del pago; anular la revierte (bloquea si ya se revendió). |
| 4 Venta rápida | Parcial | Cobrar saldo, restaurar carrito ante error, N+1 clientes, mensajes claros, i18n español. **Pendiente**: pagos en PYG/BRL y selector de caja → depende de la rama `feat/caja-chica-pyg-capital-usd`. |
| 5 KPIs | Hecho | `x-kpi-link` con permiso; filtro de vehículos por estado; `?tab=` en notificaciones. Finanzas "Ingresos/Egresos del mes" sin enlace (suman ambas cajas, no hay pantalla destino). |
| 6 Tema/emojis | Hecho | 28 vistas a tokens del tema; 45 emojis fuera. Pendiente menor: `layouts/auth` (2FA) mantiene su propia paleta. |

### Deploy (cuando se apruebe)
1. Backup: `deployment/production-backup.sh`.
2. `git pull` de la rama mergeada en `main`, `composer install --no-dev`, `php artisan migrate --force` (solo agrega columnas nullable), `php artisan optimize:clear && php artisan config:cache route:cache view:cache`.
3. Verificar: eliminar imagen de un vehículo, registrar gasto con comprobante, venta con toma, modo claro.
