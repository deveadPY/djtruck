# Guía de Deploy a Producción — erp.djtrucks.com.py

## 🎯 Objetivo
Subir la nueva versión (`d8451dd`) a SiteGround **conservando todos los datos del cliente** en producción.

## 📊 Diff entre versiones

| Aspecto | djtrucksV1 (actual prod) | djtrucks (nuevo) |
|---------|--------------------------|------------------|
| Migraciones | 28 | 50 (+22 nuevas) |
| Módulo SIFEN | ✓ presente | ✗ eliminado |
| Aggregates/DDD | parcial | completo |
| 2FA | ✗ | ✓ |
| Billing API | ✗ | ✓ |
| Tests | parcial | 27 archivos Pest |

## ✅ Garantías de seguridad

- ✅ Las 22 migraciones nuevas son **aditivas** (solo agregan columnas/tablas)
- ✅ La única que renombra (`cotizaciones → tasas_cambio`) tiene guard `Schema::hasTable`
- ✅ Las migraciones SIFEN que fueron borradas del repo **ya están registradas en `migrations` table** — Laravel no las ejecutará de nuevo
- ✅ Ninguna migración hace `dropColumn`, `dropTable`, `truncate` o destruye datos

---

## 🚀 PROCESO DE DEPLOY (orden estricto)

### FASE 0 — Preparación local (en tu PC)

```bash
# 1. Verificar que el código está pusheado al repo
git status                    # debe decir "nothing to commit"
git log --oneline -1          # debe ser d8451dd

# 2. Subir los scripts de deploy al repo
git add deployment/
git commit -m "chore: scripts de deploy a producción SiteGround"
git push origin main
```

---

### FASE 1 — Conectar al servidor SiteGround vía SSH

En el panel de SiteGround:
1. **Site Tools** → **Devs** → **SSH Keys Manager**
2. Crear/usar tu llave SSH
3. Conectarte:

```bash
ssh -p PUERTO USUARIO@SERVIDOR.sgvps.net
```

Una vez dentro del servidor, ir al directorio de la app:
```bash
cd ~/www/erp.djtrucks.com.py/public_html
pwd                           # confirma que estás en el lugar correcto
ls -la                        # debe mostrar artisan, composer.json, etc.
```

---

### FASE 2 — Configurar repositorio git en el servidor (solo primera vez)

**Si el directorio del sitio ya tiene código pero NO es un repo git:**

```bash
# 1. Hacer backup del código actual por seguridad
cd ~
tar -czf code-backup-pre-git-$(date +%Y%m%d).tar.gz www/erp.djtrucks.com.py/public_html/

# 2. Inicializar git en el directorio
cd ~/www/erp.djtrucks.com.py/public_html
git init
git remote add origin https://github.com/deveadPY/djtruck.git
git fetch origin
git reset --hard origin/main  # ⚠ esto reemplaza el código (los datos en DB están a salvo)

# Si te pide credenciales, usar Personal Access Token de GitHub
# (Settings → Developer settings → PAT)
```

**Si ya es un repo git:** saltar este paso.

---

### FASE 3 — Backup TRIPLE (CRÍTICO)

```bash
# 1. Subir el script de backup al servidor
cd ~/www/erp.djtrucks.com.py/public_html
git pull origin main          # baja el directorio deployment/

# 2. Hacer ejecutables y correr
chmod +x deployment/production-backup.sh
./deployment/production-backup.sh

# Esto crea en ~/backups/:
#   - backup_db_YYYYMMDD_HHMMSS.sql.gz      (DB completa comprimida)
#   - backup_uploads_YYYYMMDD_HHMMSS.tar.gz (storage + uploads)
#   - backup_env_YYYYMMDD_HHMMSS.txt        (copia del .env actual)
```

**Descargar los 3 backups a tu PC** (importante):
```bash
# En tu PC local, abrir otra terminal:
scp -P PUERTO USUARIO@SERVIDOR.sgvps.net:~/backups/backup_*_$(date +%Y%m%d)* ./backups-local/
```

---

### FASE 4 — Actualizar `.env` de producción con variables nuevas

```bash
# En el servidor, editar el .env
nano .env
```

**Agregar al final** (sin tocar las credenciales existentes de DB/mail):

```bash
# ── Billing (placeholder, sin proveedor activo) ──
BILLING_PROVIDER=null

# ── 2FA ──
TWO_FACTOR_ISSUER="DJ Trucks ERP"
TWO_FACTOR_ENABLED=false
```

**Verificar que estas variables ya existen y son correctas:**
- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://erp.djtrucks.com.py`
- `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` (no tocar)

Guardar con `Ctrl+O`, salir con `Ctrl+X`.

---

### FASE 5 — Ejecutar el deploy

```bash
chmod +x deployment/production-deploy.sh
./deployment/production-deploy.sh
```

El script automáticamente:
1. ✓ Verifica backup reciente
2. ✓ Activa modo mantenimiento (`artisan down`)
3. ✓ Hace `git pull` del nuevo código
4. ✓ Instala dependencias (`composer install --no-dev`)
5. ✓ Aplica migraciones nuevas (`artisan migrate --force`)
6. ✓ Reconstruye caches
7. ✓ Crea storage link
8. ✓ Ajusta permisos
9. ✓ Sale de modo mantenimiento (`artisan up`)

**Si algo falla**, el script automáticamente restaura el sitio con `artisan up`.

---

### FASE 6 — Verificación post-deploy

#### En el servidor:

```bash
# Ver migraciones aplicadas
php artisan migrate:status | tail -25

# Ver últimos logs
tail -50 storage/logs/laravel.log

# Verificar que el sitio responde
curl -I https://erp.djtrucks.com.py/
```

#### En el navegador:

| Página | URL | Verificar |
|--------|-----|-----------|
| Home | https://erp.djtrucks.com.py/ | redirige a login |
| Login | https://erp.djtrucks.com.py/login | aparece el form |
| Dashboard | https://erp.djtrucks.com.py/dashboard | gráficos y datos cargan |
| Vehículos | https://erp.djtrucks.com.py/vehicles | lista con datos previos |
| Ventas | https://erp.djtrucks.com.py/ventas | ventas anteriores visibles |
| Repuestos | https://erp.djtrucks.com.py/repuestos | productos previos |
| Cuotas | https://erp.djtrucks.com.py/planes_cuotas | cuotas activas intactas |

#### Conteos rápidos vía SSH:

```bash
php artisan tinker --execute="
echo 'Vehículos: '.\DB::table('vehiculos')->whereNull('deleted_at')->count().PHP_EOL;
echo 'Ventas: '.\DB::table('ventas')->whereNull('deleted_at')->count().PHP_EOL;
echo 'Clientes: '.\DB::table('clientes')->whereNull('deleted_at')->count().PHP_EOL;
echo 'Repuestos: '.\DB::table('stock_repuestos')->whereNull('deleted_at')->count().PHP_EOL;
echo 'Cuotas pendientes: '.\DB::table('cuotas')->where('estado','PENDIENTE')->count().PHP_EOL;
"
```

Comparar con tu backup local (db0bhn9zibdgmv.sql) — los conteos deben coincidir.

---

## 🆘 ROLLBACK (si algo sale mal)

```bash
cd ~/www/erp.djtrucks.com.py/public_html
php artisan down

# 1. Revertir código
git reset --hard 2d62f85      # commit anterior conocido

# 2. Restaurar DB del backup
LATEST_BACKUP=$(ls -t ~/backups/backup_db_*.sql.gz | head -1)
gunzip < "${LATEST_BACKUP}" | mysql -u DB_USER -p DB_NAME

# 3. Limpiar caches
php artisan config:clear
php artisan cache:clear
php artisan up
```

---

## 📋 CHECKLIST FINAL

Antes del deploy:
- [ ] `git push` ya hecho de los scripts de deployment
- [ ] Acceso SSH a SiteGround funcionando
- [ ] Personal Access Token de GitHub a mano (si el repo es privado)
- [ ] Backup `db0bhn9zibdgmv.sql` descargado HOY en tu PC

Durante el deploy:
- [ ] Backup triple ejecutado y descargado
- [ ] `.env` actualizado con nuevas variables
- [ ] Sitio en modo mantenimiento mostrando mensaje correcto
- [ ] Script de deploy completado sin errores

Después del deploy:
- [ ] Conteos de DB coinciden con backup previo
- [ ] Login funciona
- [ ] Lista de vehículos muestra datos previos
- [ ] Ventas y cuotas operativas

---

## 💡 Tiempos estimados

| Fase | Duración |
|------|----------|
| Backup triple | 1–3 min |
| Git pull + composer | 2–5 min |
| Migraciones | 30 seg |
| Caches | 10 seg |
| Verificación | 5 min |
| **TOTAL** | **~10–15 min** |

Modo mantenimiento dura solo durante las fases 2–3 (~5 min).

---

## 🔐 Notas de seguridad

- ❌ NUNCA correr `php artisan migrate:fresh` ni `migrate:reset` en producción
- ❌ NUNCA hacer `composer update` en producción (usa lock file con `install`)
- ❌ NUNCA subir el `.env` al repo
- ✅ Siempre `--force` en `migrate` y `db:seed` en producción
- ✅ Mantener backups por al menos 30 días

