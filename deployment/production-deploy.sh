#!/bin/bash
# =============================================================================
# Production Deploy Script — erp.djtrucks.com.py
# =============================================================================
# Ejecutar EN EL SERVIDOR de SiteGround vía SSH, DESPUÉS del backup.
#
# Pasos:
#   1. Verifica que hay backup reciente
#   2. Activa modo mantenimiento (sitio muestra "en mantenimiento")
#   3. Hace git pull del repositorio
#   4. composer install --no-dev --optimize-autoloader
#   5. Migra DB (SOLO migraciones nuevas — preserva datos)
#   6. Reconstruye caches (config, route, view)
#   7. Limpia compiled views obsoletas
#   8. Verifica permisos
#   9. Desactiva modo mantenimiento
#
# Uso:
#   chmod +x production-deploy.sh
#   ./production-deploy.sh
#
# Para rollback ante problema:
#   php artisan down
#   git reset --hard <commit-anterior>
#   gunzip < ~/backups/backup_db_TIMESTAMP.sql.gz | mysql -u USER -p DB
#   php artisan up
# =============================================================================
set -e

# ── CONFIGURACIÓN ─────────────────────────────────────────────────────────────
APP_DIR="${HOME}/www/erp.djtrucks.com.py/public_html"
BACKUP_DIR="${HOME}/backups"
BRANCH="main"
LOG_FILE="${HOME}/deploy_$(date +%Y%m%d_%H%M%S).log"

# ── HELPERS ───────────────────────────────────────────────────────────────────
log() { echo "[$(date +%H:%M:%S)] $*" | tee -a "${LOG_FILE}"; }
err() { echo "❌ ERROR: $*" | tee -a "${LOG_FILE}" >&2; exit 1; }

# ── PRE-FLIGHT CHECKS ─────────────────────────────────────────────────────────
log "═══════════════════════════════════════════════════════════"
log "  DEPLOY a producción — erp.djtrucks.com.py"
log "═══════════════════════════════════════════════════════════"

# 1. Verificar que existe el directorio
[ -d "${APP_DIR}" ] || err "No existe ${APP_DIR}"
cd "${APP_DIR}"

# 2. Verificar que es un repo git
[ -d ".git" ] || err "${APP_DIR} no es un repositorio git. Configurar primero."

# 3. Verificar que hay backup reciente (< 1 hora)
LATEST_BACKUP=$(ls -t "${BACKUP_DIR}"/backup_db_*.sql.gz 2>/dev/null | head -1)
if [ -z "${LATEST_BACKUP}" ]; then
    err "No hay backup de DB. Ejecutar production-backup.sh primero."
fi

BACKUP_AGE_MIN=$(( ($(date +%s) - $(stat -c %Y "${LATEST_BACKUP}")) / 60 ))
if [ ${BACKUP_AGE_MIN} -gt 60 ]; then
    log "⚠  El backup más reciente tiene ${BACKUP_AGE_MIN} minutos."
    read -p "¿Continuar de todas formas? (y/n): " CONFIRM
    [ "${CONFIRM}" = "y" ] || err "Deploy cancelado por el usuario"
fi

log "✓ Backup reciente verificado: ${LATEST_BACKUP}"

# 4. Verificar .env existe
[ -f ".env" ] || err "No existe .env en ${APP_DIR}"
log "✓ .env presente"

# ── DEPLOY ────────────────────────────────────────────────────────────────────

# Paso 1: Modo mantenimiento
log ""
log "[1/8] Activando modo mantenimiento..."
php artisan down --message="Aplicando actualización del sistema" --retry=60 || true
log "✓ Sitio en modo mantenimiento"

# Trap para asegurar que SIEMPRE se restaure el sitio si falla
trap 'log "⚠  Restaurando sitio por error..."; php artisan up; exit 1' ERR

# Paso 2: Git pull
log ""
log "[2/8] Actualizando código desde repositorio..."
git fetch origin
COMMIT_ANTERIOR=$(git rev-parse HEAD)
git pull origin "${BRANCH}"
COMMIT_NUEVO=$(git rev-parse HEAD)
log "✓ Código actualizado: ${COMMIT_ANTERIOR:0:7} → ${COMMIT_NUEVO:0:7}"

# Paso 3: Composer install
log ""
log "[3/8] Instalando dependencias de Composer..."
composer install --no-dev --optimize-autoloader --no-interaction 2>&1 | tail -5
log "✓ Dependencias instaladas"

# Paso 4: Migraciones (SOLO nuevas, --force porque estamos en production)
log ""
log "[4/8] Aplicando migraciones..."
php artisan migrate --force 2>&1 | tee -a "${LOG_FILE}"
log "✓ Migraciones aplicadas"

# Paso 5: Rebuild de caches
log ""
log "[5/8] Reconstruyendo caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
log "✓ Caches reconstruidas"

# Paso 6: Storage link
log ""
log "[6/8] Verificando symlink de storage..."
if [ ! -L "public/storage" ]; then
    php artisan storage:link
    log "✓ Symlink creado"
else
    log "✓ Symlink ya existente"
fi

# Paso 7: Permisos
log ""
log "[7/8] Ajustando permisos..."
chmod -R 755 storage bootstrap/cache 2>/dev/null || true
chmod -R 775 storage/framework storage/logs 2>/dev/null || true
log "✓ Permisos ajustados"

# Paso 8: Salir de mantenimiento
log ""
log "[8/8] Saliendo de modo mantenimiento..."
php artisan up
log "✓ Sitio en línea"

# Quitar trap
trap - ERR

# ── RESUMEN ───────────────────────────────────────────────────────────────────
log ""
log "═══════════════════════════════════════════════════════════"
log "  ✅ Deploy completado exitosamente"
log "═══════════════════════════════════════════════════════════"
log ""
log "Commit anterior: ${COMMIT_ANTERIOR}"
log "Commit actual:   ${COMMIT_NUEVO}"
log "Log completo:    ${LOG_FILE}"
log ""
log "🔍 VERIFICAR en navegador:"
log "   https://erp.djtrucks.com.py/"
log "   https://erp.djtrucks.com.py/dashboard"
log "   https://erp.djtrucks.com.py/vehicles"
log ""
log "📊 Ver logs de Laravel:"
log "   tail -f storage/logs/laravel.log"
log ""
