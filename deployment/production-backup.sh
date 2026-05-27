#!/bin/bash
# =============================================================================
# Production Backup Script — erp.djtrucks.com.py
# =============================================================================
# Ejecutar EN EL SERVIDOR de SiteGround vía SSH antes de cualquier deploy.
#
# Genera 3 backups con timestamp en ~/backups/:
#   1. backup_db_YYYYMMDD_HHMMSS.sql.gz   (dump completo MySQL)
#   2. backup_uploads_YYYYMMDD_HHMMSS.tar.gz (storage + public/uploads)
#   3. backup_env_YYYYMMDD_HHMMSS.txt      (copia del .env actual)
#
# Uso:
#   chmod +x production-backup.sh
#   ./production-backup.sh
# =============================================================================
set -e

# ── CONFIGURACIÓN — Ajustar al entorno SiteGround ─────────────────────────────
APP_DIR="${HOME}/www/erp.djtrucks.com.py/public_html"
BACKUP_DIR="${HOME}/backups"
TIMESTAMP="$(date +%Y%m%d_%H%M%S)"

# Crear directorio de backups si no existe
mkdir -p "${BACKUP_DIR}"

echo "═══════════════════════════════════════════════════════════"
echo "  Backup de producción — erp.djtrucks.com.py"
echo "  Timestamp: ${TIMESTAMP}"
echo "═══════════════════════════════════════════════════════════"

# ── 1. BACKUP DE BASE DE DATOS ───────────────────────────────────────────────
echo ""
echo "[1/3] Backup de base de datos..."

if [ ! -f "${APP_DIR}/.env" ]; then
    echo "  ❌ ERROR: No se encontró .env en ${APP_DIR}"
    exit 1
fi

# Leer credenciales del .env
DB_HOST=$(grep -E "^DB_HOST=" "${APP_DIR}/.env" | cut -d'=' -f2- | tr -d '"' | tr -d "'" | xargs)
DB_PORT=$(grep -E "^DB_PORT=" "${APP_DIR}/.env" | cut -d'=' -f2- | tr -d '"' | tr -d "'" | xargs)
DB_NAME=$(grep -E "^DB_DATABASE=" "${APP_DIR}/.env" | cut -d'=' -f2- | tr -d '"' | tr -d "'" | xargs)
DB_USER=$(grep -E "^DB_USERNAME=" "${APP_DIR}/.env" | cut -d'=' -f2- | tr -d '"' | tr -d "'" | xargs)
DB_PASS=$(grep -E "^DB_PASSWORD=" "${APP_DIR}/.env" | cut -d'=' -f2- | tr -d '"' | tr -d "'" | xargs)

DB_BACKUP="${BACKUP_DIR}/backup_db_${TIMESTAMP}.sql.gz"

mysqldump \
    --host="${DB_HOST}" \
    --port="${DB_PORT:-3306}" \
    --user="${DB_USER}" \
    --password="${DB_PASS}" \
    --single-transaction \
    --quick \
    --lock-tables=false \
    --routines \
    --triggers \
    --add-drop-table \
    --no-tablespaces \
    "${DB_NAME}" 2>/dev/null | gzip > "${DB_BACKUP}"

if [ -s "${DB_BACKUP}" ]; then
    SIZE=$(du -h "${DB_BACKUP}" | cut -f1)
    echo "  ✓ DB respaldada: ${DB_BACKUP} (${SIZE})"
else
    echo "  ❌ ERROR: El backup de DB está vacío"
    exit 1
fi

# ── 2. BACKUP DE ARCHIVOS SUBIDOS POR USUARIOS ───────────────────────────────
echo ""
echo "[2/3] Backup de archivos (storage + uploads)..."

UPLOADS_BACKUP="${BACKUP_DIR}/backup_uploads_${TIMESTAMP}.tar.gz"

cd "${APP_DIR}"
tar -czf "${UPLOADS_BACKUP}" \
    --exclude="storage/framework/cache/*" \
    --exclude="storage/framework/sessions/*" \
    --exclude="storage/framework/views/*" \
    --exclude="storage/logs/*" \
    storage/app \
    public/uploads 2>/dev/null || true

if [ -s "${UPLOADS_BACKUP}" ]; then
    SIZE=$(du -h "${UPLOADS_BACKUP}" | cut -f1)
    echo "  ✓ Uploads respaldados: ${UPLOADS_BACKUP} (${SIZE})"
else
    echo "  ⚠  No se encontraron archivos en storage/uploads"
fi

# ── 3. BACKUP DEL .env ────────────────────────────────────────────────────────
echo ""
echo "[3/3] Backup de .env..."

ENV_BACKUP="${BACKUP_DIR}/backup_env_${TIMESTAMP}.txt"
cp "${APP_DIR}/.env" "${ENV_BACKUP}"
chmod 600 "${ENV_BACKUP}"
echo "  ✓ .env respaldado: ${ENV_BACKUP}"

# ── RESUMEN ───────────────────────────────────────────────────────────────────
echo ""
echo "═══════════════════════════════════════════════════════════"
echo "  ✅ Backup completado exitosamente"
echo "═══════════════════════════════════════════════════════════"
echo ""
echo "Archivos generados en ${BACKUP_DIR}/:"
ls -lh "${BACKUP_DIR}"/*"${TIMESTAMP}"* 2>/dev/null
echo ""
echo "💡 TIP: Descarga estos archivos a tu PC con SFTP antes de continuar."
echo "        scp user@host:${BACKUP_DIR}/backup_*${TIMESTAMP}* ./"
echo ""
