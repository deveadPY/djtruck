# Plan de optimización de interfaz y procesos — DJ Trucks ERP

Fecha: 2026-11-12 · Rama: `feat/ux-modulos` (apilada sobre `feat/cotizacion-automatica`)

Método: 4 agentes auditaron en paralelo (inventario, comercial, administración, experiencia global) leyendo el código y midiendo uso real en una copia local de producción (36 vehículos, 10 clientes, 6 ventas, 60 cuotas pendientes, 45 movimientos de caja, 25 facturas). Luego 3 agentes implementaron el lote 1 en paralelo sobre archivos separados, y la parte global se hizo en el hilo principal.

## Diagnóstico por proceso

| Proceso | Hoy | Fricción principal | Objetivo |
|---|---|---|---|
| Ingreso y preparación del camión | Alta → gastos de taller → stock | La lista mezcla 15 bajas con el stock; editar un vehículo podía cambiarle estado o costo sin aviso | Ver solo stock vendible; editar sin efectos colaterales |
| Venta | 4 pasos + modal: **9 clics** en una venta contado | Doble confirmación, el cliente no avanza solo, no se puede vender desde la ficha del camión ni del cliente | **~5 clics**, venta iniciada desde el camión o el cliente |
| Cobro de cuotas | Una cuota por vez desde el plan | Con mora la cuota quedaba a medias y sin botón para terminar de cobrarla; la ficha del cliente no muestra saldo ni próxima cuota | Cobrar desde el cliente, con mora y pagos parciales |
| Caja y finanzas | Movimientos manuales + automáticos | Totales inflados por transferencias, etiquetas internas, totales que no coinciden con el PDF, doble envío posible | Cifras confiables y conciliables |
| Facturas de proveedor | Listado sin paginar | Crece sin límite; borrar usaba un modal propio con un dato sin escapar | Paginado con total correcto y confirmación estándar |
| Global | Sidebar | Mensajes de éxito que no se veían en 6 módulos, 3 mecanismos de confirmación, botones de icono sin nombre accesible, cierre de sesión por GET | Feedback y confirmaciones únicos, accesible por teclado |

## Lote 1 — implementado en esta rama (sin cambios de base de datos)

Corrección de errores que alteraban u ocultaban datos:
- **INV-1** Editar un vehículo VENDIDO ya no le cambia el estado.
- **INV-2** Abrir la edición ya no recalcula el costo en USD con la tasa del día.
- **INV-5** Compras: los repuestos sin proveedor ya no se ocultan al elegir proveedor.
- **COM-1** Cobro de cuotas con mora y cuotas `PAGADA_PARCIAL`.
- **ADM-1/6** Texto suelto en Finanzas; transferencias excluidas de ingresos/egresos del mes.
- **ADM-2** Montos en USD mostrados con la moneda correcta en Notificaciones.

Flujo y experiencia:
- **INV-3/4** Vehículos: "En stock" por defecto, estados legibles, botón **Vender** en la ficha.
- **COM-2/3/4/5** Venta: preselección de camión y cliente, avance automático, una sola confirmación, crédito disponible en la tarjeta del cliente; ficha del cliente con saldo, vencidas, próxima cuota y "Cobrar"; WhatsApp y teléfono clicables.
- **ADM-3/4/5/8** Caja: etiquetas legibles, totales que respetan el filtro, sin doble envío; Notificaciones recuerda la pestaña.
- **GLB-1** Mensajes de éxito/error en un solo lugar del layout (antes se perdían en 6 módulos).
- **GLB-3** `aria-label` en botones de icono, foco visible para teclado, página 403 con colores del tema.
- **GLB-4** Facturas paginadas con total filtrado correcto.
- **GLB-5** Confirmaciones nativas del navegador reemplazadas por el modal del sistema.
- **GLB-12** Cierre de sesión por POST.

El detalle por archivo está en los commits de la rama.

## Lote 2 — siguiente (esfuerzo medio, sin cambios de base de datos)

| id | Mejora | Impacto |
|---|---|---|
| GLB-2 | Botón "+ Nuevo" (venta, gasto, cliente, vehículo) y atajos de teclado | Alto |
| GLB-7 | Componentes `x-page-header`, `x-search-bar`, `x-empty-state`, `x-table` y migrar los 7 listados | Alto |
| GLB-8 | Búsqueda global (chasis, RUC, N° de venta) en el encabezado | Alto |
| GLB-9 | Dashboard con franja "Hoy": cuotas que vencen, stock bajo, acciones rápidas | Alto |
| INV-6 | Lista de vehículos con foto, días en stock y orden por precio/año/antigüedad | Alto |
| INV-7 | Ficha: margen (precio − valor libro), año, motor, eliminar gasto | Medio |
| INV-8 | Gastos: "Guardar y registrar otro" | Medio |
| COM-6/7 | Pago de cuota con fecha editable; enviar recibo por WhatsApp | Alto |
| COM-11 | Ventas: columna saldo/mora y filtro por modalidad | Medio |
| ADM-11 | Caja: filtros por origen y moneda, exportar CSV, saldo inicial y final del período | Alto |
| ADM-13/14 | Mora: descarte en bloque, contactar por WhatsApp y "Registrar pago" | Alto |
| ADM-15 | Reportes de caja y de gastos | Medio |
| COM-13/14 | `ventas/create` y `planes_cuotas/show` a tokens del tema y accesibles por teclado | Medio |

## Lote 3 — requiere decisión o cambios mayores

| id | Mejora | Decisión necesaria |
|---|---|---|
| ADM-10 | Registrar el egreso de una factura solo cuando se paga, con acción "Registrar pago" | Qué hacer con las 21 facturas ya cargadas como PAGADAS sin duplicar movimientos |
| ADM-16 | Saldo de cada caja desglosado por moneda (USD/PYG/BRL) | Depende de la rama de caja chica en PYG |
| INV-14 | Un repuesto usado en un gasto descuenta stock | Hoy el kardex no se usa: confirmar si el taller consume del stock propio |
| GLB-11 | Compilar Tailwind en lugar del CDN (menos parpadeo, más rápido en el móvil) | Agrega un paso de build al deploy |
| — | Zona horaria del sistema a America/Asuncion | Mueve las horas de los registros existentes |

## Datos que conviene revisar

- Gastos: el 100 % de los 23 gastos están en PYG, pero se pidió USD por defecto; se mantuvo USD.
- 15 de 17 repuestos están bajo el mínimo y hay costos de 11.111.111.111: limpiar el catálogo.
- Solo 1 de 10 clientes tiene email y 5 tienen teléfono: WhatsApp es el canal real.
- Caja Capital muestra un "neto en PYG" negativo porque los gastos se pagan en PYG y las ventas entran en USD: no es un saldo.
