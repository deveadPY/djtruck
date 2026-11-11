# DJ Trucks - Fleet Management System

Laravel 10+ | PHP 8.1+ | MySQL 8 | Tailwind CSS 3 | Alpine.js

## Agent Roles & Skills

Cada responsabilidad tiene skills asignados del catálogo antigravity (1,460 disponibles). Invocalos con `/skill-name` o descriptivamente.

### Arquitectura y Planificacion
Antes de cualquier feature nueva, activar en orden:
1. `/concise-planning` — Planificar alcance, MVP, riesgos
2. `/architecture` — Decisiones de arquitectura (ADR)
3. `/database-design` — Schema, indices, relaciones

Skills: `concise-planning`, `architecture`, `database-design`, `brainstorming`

### Backend / API (PHP + Laravel)
Para endpoints, controladores, servicios, middleware:
- `/laravel-expert` — Laravel especifico: Eloquent, queues, jobs, eventos
- `/php-pro` — PHP puro: tipado, rendimiento, patrones modernos
- `/api-patterns` — REST, paginacion, versionado, respuestas JSON
- `/backend-dev-guidelines` — Produccion: logs, errores, concurrencia

Skills: `laravel-expert`, `php-pro`, `api-patterns`, `backend-dev-guidelines`

### Base de Datos (MySQL)
Para migraciones, queries, optimizacion:
- `/database-optimizer` — EXPLAIN, indices, queries lentos
- `/database-migrations-sql-migrations` — Migraciones seguras, rollback

Skills: `database-optimizer`, `database-migrations-sql-migrations`, `database`

### Frontend (Blade + Tailwind + Alpine.js)
Para vistas, componentes, JS:
- `/tailwind-patterns` — Tailwind v3: responsive, dark mode, design tokens
- `/javascript-pro` — Alpine.js, vanilla JS
- `/ui-ux-pro-max` — Diseno visual, accesibilidad, paletas
- `/frontend-design` — Componentes, layouts, interaccion

Skills: `tailwind-patterns`, `javascript-pro`, `ui-ux-pro-max`, `frontend-design`

### Seguridad
Para auth, validacion, revision de vulnerabilidades:
- `/laravel-security-audit` — CSRF, XSS, SQLi en contexto Laravel
- `/api-security-best-practices` — Rate limiting, CORS, tokens
- `/auth-implementation-patterns` — Login, 2FA, roles, permisos

Skills: `laravel-security-audit`, `api-security-best-practices`, `auth-implementation-patterns`

### Calidad de Codigo
Antes de cada commit/entrega:
- `/code-review-checklist` — Checklist sistematico pre-entrega
- `/systematic-debugging` — Diagnostico de bugs con metodologia
- `/code-refactoring-refactor-clean` — Limpieza, patrones, SOLID

Skills: `code-review-checklist`, `systematic-debugging`, `code-refactoring-refactor-clean`

### Testing
- `/test-driven-development` — TDD: red-green-refactor
- `/testing-patterns` — PHPUnit, factories, mocks

Skills: `test-driven-development`, `testing-patterns`

## Pipeline de Trabajo por Tipo de Tarea

### Nueva Feature Completa
```
concise-planning → architecture → database-design → [implementar]
→ laravel-expert + php-pro (backend)
→ tailwind-patterns + ui-ux-pro-max (frontend)
→ test-driven-development (tests)
→ code-review-checklist (revision final)
```

### Bug Fix
```
systematic-debugging → [fix] → testing-patterns → code-review-checklist
```

### Refactor
```
code-refactoring-refactor-clean → laravel-expert → code-review-checklist
```

### Security Audit
```
laravel-security-audit → api-security-best-practices → [fix] → code-review-checklist
```

## Reglas del Proyecto

### Estructura
- `app/Http/Controllers/` — Controladores (thin controllers, logica en Services)
- `app/Models/` — Modelos Eloquent
- `app/Services/` — Logica de negocio
- `app/Http/Middleware/` — Middleware personalizado
- `database/migrations/` — Migraciones
- `resources/views/` — Blade templates
- `public/` — Assets compilados

### Convenciones
- Codigo en ingles, comentarios en espanol si son necesarios
- Controladores: thin, maxima 7 acciones RESTful
- Servicios: una clase por dominio (FleetService, BillingService)
- Validacion: siempre en FormRequest, nunca en controlador
- Eloquent: usar query scopes para queries complejos
- Migraciones: siempre con down() para rollback
- Nombres de ruta: `resource` convention, prefijados por modulo

### Prohibido
- Queries SQL crudos en controladores o vistas
- Logica de negocio en Blade templates
- var_dump() / dd() en produccion
- Secretos hardcodeados (.env siempre)
- Mezclar responsabilidades (un archivo = una responsabilidad)
