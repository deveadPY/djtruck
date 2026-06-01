# Refactor + Deploy Producción - 27 Mayo 2026

> **Sesión maratónica de refactorización Clean Architecture + DDD, fixes críticos y deploy a producción (`erp.djtrucks.com.py`) con datos preservados al 100%.**

---

## 📑 Índice

1. [Resumen Ejecutivo](#-resumen-ejecutivo)
2. [Arquitectura Aplicada](#-arquitectura-aplicada)
3. [Refactor por Módulo](#-refactor-por-módulo)
4. [Mejoras UI/UX](#-mejoras-uiux)
5. [Fixes Críticos](#-fixes-críticos)
6. [Proceso de Deploy a Producción](#-proceso-de-deploy-a-producción)
7. [Comandos Útiles](#-comandos-útiles)
8. [Pendientes Futuros](#-pendientes-futuros)
9. [Stack Técnico](#-stack-técnico)
10. [Estructura de Directorios](#-estructura-de-directorios)

---

## 📋 Resumen Ejecutivo

### Métricas

| Métrica | Valor |
|---------|-------|
| Commits subidos al repo | 7 |
| Archivos modificados/creados | ~50+ |
| Migraciones nuevas aplicadas | 24 |
| Tests Pest nuevos | 27 archivos |
| Líneas de código eliminadas (deuda técnica) | ~500 |
| Líneas de código nuevas | ~3000 |
| Downtime en producción | ~5 min |
| Datos preservados | 100% ✅ |

### Commits realizados

```
9efbb84 feat(clientes): comandos artisan para detectar y limpiar duplicados
84c5e04 feat(clientes): validar RUC y email únicos al crear/editar
700f73c fix(db): migración para renombrar columnas con encoding UTF-8
ff3d2f8 fix(ui): modal de confirmación + forms anidados en vehicle edit
95efb1b chore: scripts de deploy a producción SiteGround
d8451dd refactor: clean architecture completa + UX mejoras
2d62f85 style: fix manual installments table layout (commit anterior)
```

---

## 🏛️ Arquitectura Aplicada

### Clean Architecture + DDD por capas

```
┌─────────────────────────────────────────────────────────┐
│                   INFRASTRUCTURE                        │
│  HTTP Controllers (Web + API)                           │
│  Eloquent Models + Repositories                         │
│  Mail, Storage, Currency, ActivityLog                   │
└──────────────────┬──────────────────────────────────────┘
                   │ inyecta
┌──────────────────▼──────────────────────────────────────┐
│                  APPLICATION                            │
│  Use Cases (ProcessHybridSaleUseCase, etc.)             │
│  DTOs (CreateSaleDTO, UpdateVehicleDTO, etc.)           │
│  Application Services (SaleApplicationService, etc.)    │
└──────────────────┬──────────────────────────────────────┘
                   │ usa
┌──────────────────▼──────────────────────────────────────┐
│                     DOMAIN                              │
│  Aggregates (Sale, Vehicle, Purchase)                   │
│  Value Objects (Money, Currency, SaleId)                │
│  Validators (CreditLimitValidator, etc.)                │
│  Processors (PaymentProcessor, etc.)                    │
│  Calculator (SaleCalculator, BookValueCalculator)       │
│  Services (ItemDescriptionResolver, etc.)               │
│  Events + Listeners                                     │
│  Domain Exceptions (tipadas con factory methods)        │
│  Repository Interfaces                                  │
└─────────────────────────────────────────────────────────┘
```

### Principios aplicados

- ✅ **SRP** — cada Validator/Processor/Calculator tiene UNA responsabilidad
- ✅ **DIP** — todo inyectado vía interfaces (Repository Pattern)
- ✅ **Open/Closed** — extender comportamiento sin modificar existente
- ✅ **Tell, don't ask** — Aggregates validan sus propias invariantes
- ✅ **Factory methods en excepciones** — `InvalidVehicleData::invalidYear(2030)`
- ✅ **Container resolution** — verificado en SaleController, VehicleController

---

## 🔨 Refactor por Módulo

### Sales (más maduro — modelo a replicar)

#### Aggregate Root
- `app/Domain/Sales/Aggregates/Sale.php` — guardian de invariantes
- `SaleId`, `SaleItem`, `Payment` (Value Objects)

#### Validators (Domain)
- `SaleIntegrityValidator` — vehículos disponibles + stock + precio consistency
- `CreditLimitValidator` — línea de crédito del cliente
- `InstallmentValidator` — plan, count, dates

#### Processors (Domain)
- `SaleItemProcessor` — registra items + actualiza estado vehículos
- `PaymentProcessor` — detalles de pago + caja
- `InstallmentProcessor` — plan de cuotas + generación

#### Calculator (Domain)
- `SaleCalculator` — precios finales, márgenes, número de venta

#### Services (Domain)
- `ItemDescriptionResolver` — descripciones enriquecidas
- `PaymentImputationService` — imputación mora/interés/capital
- `InstallmentGenerator` — Francesa/Alemana/Manual

#### Use Cases (Application)
- `CreateSaleUseCase` — venta normal (DTO → SaleModel)
- `UpdateSaleUseCase` — revierte y reaplica
- `CancelSaleUseCase` — rollback completo
- `ProcessHybridSaleUseCase` — efectivo + canje + cuotas

#### Application Service
- `SaleApplicationService` — fachada para Web + API

#### Domain Exceptions
- `InvalidVehicleStateException`
- `InsufficientCreditLimitException`
- `InvalidInstallmentConfigException`
- `SalePriceInconsistencyException`
- `DuplicateVehicleSaleException`

#### Resultado
- `CreateSaleUseCase`: **392 → 139 líneas** (-64%)
- `SaleController` (API): **345 → 108 líneas** (-69%)

### Vehicle

#### Aggregate
- `app/Domain/Vehicle/Aggregates/Vehicle.php`
- `VehicleId` Value Object

#### Validators
- `VehicleIntegrityValidator` — chasis único, año válido, costo no negativo

#### Calculator
- `VehicleBookValueCalculator` — costo + gastos = valor en libros

#### Processors
- `VehicleImageProcessor` — upload + portada + reglas
  - `process()` — al crear vehículo
  - `appendMore()` — al editar (agrega sin alterar portada)
  - `remove()` — soft-delete + promueve siguiente como portada
  - `setCover()` — cambia portada

#### Services
- `TradeInVehicleRegistrar` — registro de vehículo de canje

#### Repositories
- `VehicleRepositoryInterface`
- `VehicleImageRepositoryInterface`

#### Exceptions
- `InvalidVehicleDataException` (factory methods)
- `DuplicateChassisException`

### Purchases

- `Purchase` Aggregate + `PurchaseId`
- `PurchaseValidator`
- `PurchaseCalculator`
- `PurchaseItemProcessor` — items + stock
- `PurchaseDocumentProcessor` — documentos adjuntos
- 2 Domain Exceptions

### Parts (Repuestos)

- `DiscontinuePartUseCase` — descontinuar/reactivar
- Endpoint `POST /repuestos/{id}/toggle-active`

### Clientes

- Validación HTTP `unique` en `ruc` y `email` (`StoreClienteRequest` + `UpdateClienteRequest`)
- Comandos artisan:
  - `clientes:find-duplicates`
  - `clientes:remove-duplicate {id} [--force]`

---

## 🎨 Mejoras UI/UX

### Dashboard

| Fix | Detalle |
|-----|---------|
| Stock con números enteros | `number_format($n, 0)` en vez de `0.000` |
| Déficit positivo | Removido `-` hardcoded, ahora muestra cantidad faltante con tooltip |
| Botón cerrar alerta | Dismiss 24h via `localStorage` |
| Botón "Descontinuar" | Directo en tabla de stock bajo |

### Modal de confirmación destructiva (`danger-confirm-modal`)

Reutilizable globalmente vía atributos `data-*`:

```html
<form data-danger-confirm="¿Eliminar producto X?"
      data-danger-title="Eliminar producto"
      data-danger-action-label="Sí, eliminar"
      data-danger-icon="trash">
```

- Iconos: `trash` (rojo), `warning`/`stop` (ámbar)
- Cierre con ESC, click backdrop, botón cancelar
- z-index máximo (2147483647 !important) para evitar problemas con Tailwind CDN

### Modal de confirmación normal (`confirm-modal`)

Simplificado y robusto:
- Solo mensaje + 2 botones (sin preview de campos)
- Try/catch defensivo
- Soporta `data-confirm`, `data-confirm-action-label`, `data-confirm-cancel-label`

### Gestión de imágenes de vehículos

- Agregar más imágenes (preserva existentes y portada)
- Eliminar imagen (soft-delete + auto-promueve siguiente como portada)
- Cambiar portada (UI hover en thumbnails)

### Repuestos

- Botón "Descontinuar/Reactivar" con icono dinámico (Ø / ✓)
- Modal de confirmación al eliminar
- Tooltip explicativo

---

## 🐛 Fixes Críticos

### Fix #1 — Forms anidados en `vehicles/edit`

**Bug:** El form principal contenía 2 forms anidados (portada/eliminar imagen). HTML5 NO permite forms anidados → el parser cerraba el form principal prematuramente, dejando el botón "Actualizar" sin form asociado.

**Solución:**
- Cerrar el form principal antes de la galería
- Asignar `id="vehicleEditForm"` al form
- Input file y botón Actualizar usan `form="vehicleEditForm"` (HTML5 attribute)

### Fix #2 — Modal de confirmación no aparecía

**Bug:** Las clases Tailwind `z-[99999]` arbitrary y `inset-0` no se compilan correctamente con CDN. El modal quedaba detrás de otros elementos con z-index alto.

**Solución:**
- Reemplazar clases Tailwind por estilos inline con `!important`
- `z-index: 2147483647 !important` (valor máximo de 32-bit int)
- `modal.style.setProperty('display', 'flex', 'important')`

### Fix #3 — InstallmentPlan PSR-4

**Bug:** Enum `InstallmentPlan` vivía dentro de `PaymentType.php`. PSR-4 requiere una clase por archivo.

**Solución:** Mover a `app/Domain/Sales/ValueObjects/InstallmentPlan.php`.

### Fix #4 — Columna `tamaño_bytes` con ñ

**Bug:** La columna de la tabla `documentos` tenía nombre `tamaño_bytes` (con ñ válida UTF-8 en producción, `tama??o_bytes` con encoding roto en local). El código intentaba `tamano_bytes` (sin ñ).

**Solución:**
- Migración `2026_06_01_000001_fix_remaining_utf8_columns` que renombra ambos casos
- Idempotente: si la columna destino ya existe, no hace nada

### Fix #5 — Validación duplicados de clientes

**Bug:** No había validación de duplicados al crear clientes. En producción se tenían 4 "ESTANCIA J.V SA" con mismo RUC.

**Solución:**
- `Rule::unique('clientes', 'ruc')->whereNull('deleted_at')` (respeta soft-deletes)
- `prepareForValidation()` normaliza: RUC trim + vacío→null, Email lowercase
- Comandos artisan para encontrar y limpiar duplicados existentes

### Fix #6 — `procesarVentaConCanje` con 270 líneas embebidas

**Bug:** Toda la lógica de venta híbrida (efectivo + canje + cuotas) estaba en el Controller API.

**Solución:**
- `ProcessHybridSaleDTO` + `ProcessHybridSaleUseCase`
- `TradeInVehicleRegistrar` (Domain Service)
- SaleController API: 345 → 108 líneas

### Fix #7 — VentaWebController usaba DB::table directo

**Solución:** Refactor para usar `SaleApplicationService` (con eager loading optimizado).

---

## 🚀 Proceso de Deploy a Producción

### Pre-requisitos

- Acceso SSH a SiteGround (Site Tools → Devs → SSH Keys Manager)
- PHP 8.3+ en el servidor (SiteGround tiene 8.4.21)
- MySQL accesible
- Repositorio Git accesible (privado con PAT o público temporalmente)
- Composer 2.7+ (instalar local en `~/bin/` si el del sistema es viejo)

### Fase 1 — Backup TRIPLE (no negociable)

```bash
mkdir -p ~/backups
cd ~/www/erp.djtrucks.com.py/public_html
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Backup DB
DB_NAME=$(grep "^DB_DATABASE=" .env | cut -d'=' -f2 | xargs)
DB_USER=$(grep "^DB_USERNAME=" .env | cut -d'=' -f2 | xargs)
DB_PASS=$(grep "^DB_PASSWORD=" .env | cut -d'=' -f2 | xargs)

mysqldump -h 127.0.0.1 -u "$DB_USER" -p"$DB_PASS" \
  --single-transaction --quick --no-tablespaces --routines --triggers \
  "$DB_NAME" | gzip > ~/backups/backup_db_${TIMESTAMP}.sql.gz

# Backup uploads + storage
tar -czf ~/backups/backup_uploads_${TIMESTAMP}.tar.gz \
    --exclude="storage/framework/*" --exclude="storage/logs/*" \
    storage/app public/uploads

# Backup .env
cp .env ~/backups/backup_env_${TIMESTAMP}.txt
chmod 600 ~/backups/backup_env_${TIMESTAMP}.txt
```

### Fase 2 — Inicializar git en directorio existente

```bash
cd ~/www/erp.djtrucks.com.py/public_html
git init
git remote add origin https://github.com/deveadPY/djtruck.git
git fetch origin
git checkout -f -B main origin/main  # preserva .env, vendor/, storage/
```

### Fase 3 — Limpiar archivos huérfanos

```bash
# Identificar
git status --short | grep "^??" | head -20

# Mover a cuarentena (no borrar de una)
mkdir -p ~/orphans-djtrucksV1
mv archivos_huerfanos ~/orphans-djtrucksV1/
```

### Fase 4 — Mantenimiento + Composer + Migraciones

```bash
# Activar mantenimiento
php artisan down --retry=60

# Instalar Composer 2.7+ si es viejo
cd ~
curl -sS https://getcomposer.org/installer -o composer-setup.php
php composer-setup.php --quiet
mkdir -p ~/bin && mv composer.phar ~/bin/composer && chmod +x ~/bin/composer
export PATH="$HOME/bin:$PATH"

# Dependencias producción
cd ~/www/erp.djtrucks.com.py/public_html
~/bin/composer install --no-dev --optimize-autoloader --no-interaction

# Verificar migraciones pendientes
php artisan migrate:status

# Aplicar migraciones
php artisan migrate --force

# Caches
php artisan config:clear && php artisan route:clear && php artisan view:clear
php artisan config:cache && php artisan route:cache && php artisan view:cache

# Storage link (si no existe)
[ ! -L "public/storage" ] && php artisan storage:link

# Permisos
chmod -R 755 storage bootstrap/cache
find storage -type d -exec chmod 775 {} \;
find storage -type f -exec chmod 664 {} \;
```

### Fase 5 — Verificar APP_KEY

```bash
# Si el APP_KEY está roto/vacío, regenerar
php artisan key:generate --force
php artisan config:cache
```

⚠️ Regenerar APP_KEY invalida todas las sesiones activas (los usuarios deben volver a loguearse).

### Fase 6 — Salir de mantenimiento

```bash
php artisan up
curl -I https://erp.djtrucks.com.py/  # debe responder 200 o 302
```

### Fase 7 — Verificación funcional

Ver conteos de tablas críticas:

```bash
php artisan tinker --execute="
foreach (['users','clientes','vehiculos','ventas','cuotas','stock_repuestos'] as \$t) {
    echo \$t.': '.DB::table(\$t)->whereNull('deleted_at')->count().PHP_EOL;
}
"
```

Comparar con backup previo para confirmar que NO se perdieron datos.

### Rollback

```bash
cd ~/www/erp.djtrucks.com.py/public_html
php artisan down

# Revertir código
git reset --hard <commit-anterior>

# Restaurar DB
LATEST=$(ls -t ~/backups/backup_db_*.sql.gz | head -1)
gunzip < "$LATEST" | mysql -u DB_USER -p DB_NAME

php artisan up
```

---

## 🛠️ Comandos Útiles

### Detección y limpieza de duplicados de clientes

```bash
# Listar duplicados por RUC y email con datos asociados
php artisan clientes:find-duplicates

# Soft-delete reversible de un cliente
php artisan clientes:remove-duplicate {id}

# Forzar eliminación aunque tenga ventas/planes (CUIDADO)
php artisan clientes:remove-duplicate {id} --force
```

**Reversible:** `UPDATE clientes SET deleted_at = NULL WHERE id = N;`

### Diagnóstico de DB

```bash
# Ver columnas reales de una tabla (con encoding)
php artisan tinker --execute="
\$cols = Schema::getColumnListing('documentos');
foreach (\$cols as \$c) echo \$c.PHP_EOL;
"

# Ver hex de un nombre de columna (detectar encoding raro)
php artisan tinker --execute="
\$r = DB::select(\"SELECT COLUMN_NAME, HEX(COLUMN_NAME) as hex FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='documentos' AND COLUMN_NAME LIKE 'tam%'\");
foreach (\$r as \$c) echo \$c->COLUMN_NAME.' = '.\$c->hex.PHP_EOL;
"
```

### Maintenance mode

```bash
# Activar
php artisan down --retry=60

# Desactivar
php artisan up

# Con secret bypass para QA
php artisan down --secret="mi-secreto"
# Luego accede a: https://erp.djtrucks.com.py/mi-secreto
```

### Limpiar caches (orden correcto)

```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 🔴 Pendientes Futuros

### Seguridad

| Tarea | Urgencia | Por qué |
|-------|----------|---------|
| Regenerar SSH key `deploy-key` en SiteGround | 🔴 Alta | La llave actual fue compartida en chat IA |
| Cambiar passphrase de SSH | 🔴 Alta | Por la misma razón |
| Revisar `php_errorlog` periódicamente | 🟡 Media | Detectar errores 500 silenciosos |

### Arquitectura (continuar el patrón)

| Módulo | Trabajo pendiente |
|--------|-------------------|
| Customers | Refactorizar ClienteWebController para usar `CustomerApplicationService` (hoy usa DB::table directo) |
| Clientes UI | Refactor de forms a usar Aggregates + Use Cases |
| Notificaciones | Mover lógica de NotificacionesWebController a Application Layer |

### Tests

| Pendiente | Cobertura actual | Meta |
|-----------|------------------|------|
| Tests integración para `ProcessHybridSaleUseCase` | 0% | 70% |
| Tests de UI con Pest Browser | 0% | flujos críticos |
| CI/CD GitHub Actions | No configurado | Tests on push |

### UX

| Mejora | Detalle |
|--------|---------|
| Validación duplicados en tiempo real | Async fetch mientras escribe el RUC |
| Búsqueda con autocompletar en selects de cliente | Reduce duplicados accidentales |
| Audit log visual por entidad | Ver historial de cambios desde la UI |

### Performance

| Optimización | Detalle |
|--------------|---------|
| Reemplazar Tailwind CDN por compilado | El CDN es lento y muestra warning en consola |
| Implementar Redis cache | Si crece la carga |
| Lazy loading en listas grandes | Vehículos, repuestos con muchos registros |

### Cleanup de servidor

```bash
# Después de 30 días con el deploy estable
rm -rf ~/backups/backup_*_20260527_*
rm -rf ~/orphans-djtrucksV1/  # si todavía existe
```

---

## 🔧 Stack Técnico

### Backend
- PHP 8.4.21 (producción) / 8.2.12 (local)
- Laravel 11.48.0
- MySQL 8 (utf8mb4)
- Sanctum 4 (API Auth)
- Spatie Permission 6 (Roles)
- Spatie ActivityLog 4
- Predis 2 (cache opcional)

### Frontend
- Blade Templates
- Tailwind CSS (vía CDN — ⚠️ migrar a compilado)
- Alpine.js
- Vanilla JS para modales y forms

### Testing
- Pest 2 (BDD-style)
- PHPUnit 11
- Mockery 1.6

### Hosting
- SiteGround Cloud
- Subdominio `erp.djtrucks.com.py`
- SSH habilitado puerto 18765
- Composer 2.9.8 (instalado local en `~/bin/`)

---

## 📂 Estructura de Directorios

```
app/
├── Application/                    # Casos de uso, DTOs, Application Services
│   ├── Sales/
│   │   ├── CreateSaleDTO.php
│   │   ├── CreateSaleUseCase.php
│   │   ├── UpdateSaleUseCase.php
│   │   ├── CancelSaleUseCase.php
│   │   ├── ProcessHybridSaleDTO.php
│   │   ├── ProcessHybridSaleUseCase.php
│   │   └── SaleApplicationService.php
│   ├── Vehicle/
│   ├── Purchases/
│   ├── Parts/
│   ├── Installments/
│   ├── Customers/
│   ├── Suppliers/
│   ├── Leads/
│   ├── Quotes/
│   ├── Warranties/
│   ├── Commissions/
│   ├── Billing/
│   └── Auth/TwoFactor/
│
├── Console/Commands/               # Comandos artisan
│   ├── FindDuplicateClientesCommand.php
│   ├── RemoveDuplicateClienteCommand.php
│   ├── BackupDatabase.php
│   ├── CheckOverdueInstallments.php
│   └── ...
│
├── Domain/                         # Núcleo de negocio
│   ├── Sales/
│   │   ├── Aggregates/             # Sale, SaleId, SaleItem, Payment
│   │   ├── Validators/             # SaleIntegrityValidator, etc.
│   │   ├── Processors/             # SaleItemProcessor, etc.
│   │   ├── Calculator/             # SaleCalculator
│   │   ├── Services/               # PaymentImputationService, etc.
│   │   ├── Repositories/           # SaleRepositoryInterface
│   │   ├── ValueObjects/           # InstallmentPlan, PaymentType
│   │   ├── Exceptions/             # SalePriceInconsistencyException, etc.
│   │   └── Events/                 # SaleCompleted, etc.
│   ├── Vehicle/
│   ├── Purchases/
│   ├── Parts/
│   ├── Customers/
│   ├── Suppliers/
│   ├── Leads/
│   ├── Quotes/
│   ├── Warranties/
│   ├── Commissions/
│   ├── Auth/TwoFactor/
│   ├── Finance/
│   └── Shared/
│       ├── ValueObjects/           # Money, Currency, ExchangeRate
│       └── Exceptions/             # DomainException base
│
├── Infrastructure/                 # Implementaciones técnicas
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/                # SaleController, VehicleController, etc.
│   │   │   └── Web/                # VentaWebController, ClienteWebController, etc.
│   │   ├── Requests/               # FormRequests (StoreClienteRequest, etc.)
│   │   ├── Resources/              # API Resources
│   │   ├── Middleware/             # AuditMiddleware, EnforceTwoFactorMiddleware
│   │   └── Policies/               # ClientePolicy, etc.
│   ├── Persistence/
│   │   ├── Eloquent/
│   │   │   ├── Models/             # SaleModel, VehicleModel, etc.
│   │   │   └── Repositories/       # EloquentSaleRepository, etc.
│   │   └── Observers/              # ClienteObserver, etc. (audit)
│   ├── Mail/
│   ├── Currency/
│   ├── Billing/
│   ├── Jobs/
│   └── Settings/
│
├── Models/
│   └── User.php                    # Solo este queda (Auth)
│
└── Providers/
    └── AppServiceProvider.php      # DI registry

database/migrations/                # 51 migraciones
deployment/                         # Scripts de deploy
├── DEPLOY_GUIDE.md
├── production-backup.sh
├── production-deploy.sh
└── env-additions.txt

docs/                              # Documentación
└── REFACTOR_AND_DEPLOY_2026-05-27.md  # ESTE archivo

resources/views/
├── partials/
│   ├── confirm-modal.blade.php          # Modal confirmación normal
│   └── danger-confirm-modal.blade.php   # Modal confirmación destructiva
├── vehicles/
│   ├── edit.blade.php              # Con form= attribute para evitar nested forms
│   └── ...
└── ...

tests/
├── Unit/
│   └── Domain/
│       ├── Sales/
│       │   ├── Aggregates/SaleTest.php
│       │   ├── Validators/...
│       │   ├── Processors/...
│       │   └── Calculator/SaleCalculatorTest.php
│       ├── Customers/
│       ├── Parts/
│       └── ...
└── Feature/
    └── Application/Sales/CreateSaleUseCaseTest.php
```

---

## 🎯 Convenciones del Proyecto

### Naming

- **Spanish para dominio del negocio** (clientes, vehiculos, repuestos) — coincide con tablas
- **English para arquitectura** (Aggregate, Validator, UseCase, Repository)
- **Migrations:** `YYYY_MM_DD_NNNNNN_action_description.php`
- **Use Cases:** `<Verbo><Entidad>UseCase` (ej: `CreateSale`, `DiscontinuePart`)
- **DTOs:** `<Action><Entity>DTO` (ej: `CreateSaleDTO`)
- **Exceptions:** `<Problem>Exception` con factory methods (ej: `InvalidYearException::invalidYear(2030)`)

### Reglas obligatorias

- ❌ NO usar `DB::table()` en Use Cases/Domain — solo en Repositories
- ❌ NO usar `Eloquent::Model` en Domain Aggregates — solo en Infrastructure
- ❌ NO usar `Auth::id()` en Domain — pasar como parámetro desde Application
- ✅ Toda transacción DB debe ir dentro de `DB::transaction()`
- ✅ Excepciones de dominio deben heredar de un base `DomainException`
- ✅ DTOs son `readonly` y `final` (PHP 8.3+)
- ✅ Inyección por constructor con `private readonly`

### Testing

- Tests Pest en lugar de PHPUnit (más legible)
- Unit tests para Validators/Processors/Calculators (mock Repository)
- Feature tests para Use Cases (con `RefreshDatabase`)
- Coverage objetivo: 70%+ en Domain layer

---

## 🌐 URLs Útiles

- **Producción:** https://erp.djtrucks.com.py
- **Repositorio:** https://github.com/deveadPY/djtruck (privado)
- **Hosting:** SiteGround Cloud — Site Tools djtrucks.com.py
- **Subdominio path en server:** `~/www/erp.djtrucks.com.py/public_html/`

---

## 👥 Contacto / Soporte

Si encuentras issues:
1. Revisa logs: `tail -100 storage/logs/laravel.log`
2. Revisa el `request_id` del header `X-Request-Id` (AuditMiddleware)
3. Restaura desde backup si es crítico

---

**Última actualización:** 2026-05-27
**Versión deployada:** commit `9efbb84`
**Autor del refactor:** Claude (Sonnet 4.7) + DeveaD (deveadPY)
