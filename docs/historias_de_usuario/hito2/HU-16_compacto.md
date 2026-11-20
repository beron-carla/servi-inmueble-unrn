# HU-16 · Dashboard de métricas globales

**Grupo 6:** ADMINISTRACIÓN GENERAL

Como Administrador o Propietario, quiero ver un panel con métricas de la inmobiliaria, para monitorear el estado de la facturación y priorizar acciones.

**Contexto (Antecedentes):** El Hito 1 entrega un dashboard que solo saluda al usuario. Ya existen usuarios, inmuebles, facturas, pagos (`transaccion`) y notificaciones internas.

## Reglas globales aplicables

- Se mantienen las reglas del Hito 1: password, email único case-insensitive, bajas lógicas, 403 amigable y ocultamiento de acciones no permitidas en la UI.
- El Administrador ve todas las métricas; el Propietario solo las de sus inmuebles.
- Los importes se muestran en ARS con 2 decimales y las fechas en dd/mm/aaaa.
- Ocultar en la UI no reemplaza la validación del servidor: el alcance se filtra siempre en el backend.

## Escenarios

- Panel del Administrador: ve las métricas de todo el sistema.
- Panel del Propietario: ve solo las métricas de sus inmuebles.
- Sin datos: las tarjetas muestran 0 y los gráficos muestran "Sin datos".
- Navegación: al hacer clic en una tarjeta se abre el listado correspondiente con el filtro aplicado.

## Criterios de aceptación

- **CA-16.1** El panel del Administrador muestra: usuarios activos (total, administradores y propietarios), inmuebles activos, facturas pendientes (cantidad y monto), facturas vencidas (cantidad y monto), pagos pendientes de validación (cantidad) y recaudación del mes en curso (suma de pagos validados con fecha de pago del mes).
- **CA-16.2** El panel del Propietario muestra las mismas métricas, excepto usuarios, limitadas a sus inmuebles: inmuebles activos, facturas pendientes y vencidas (cantidad y monto), pagos pendientes de validación, recaudación del mes y notificaciones sin leer.
- **CA-16.3** Gráfico 1: evolución de los últimos 12 meses con dos series, facturación emitida (suma de `monto_total` por `fecha_emision`) y cobranza (suma de `monto_abonado` de pagos validados por `fecha_pago`).
- **CA-16.4** Gráfico 2: distribución de facturas por estado calculado (pendiente, vencida, pagada) del año en curso.
- **CA-16.5** Todo cálculo se filtra en el servidor por rol: el Propietario nunca recibe datos de inmuebles ajenos, aunque manipule parámetros de la URL.
- **CA-16.6** Cada tarjeta enlaza al listado correspondiente con el filtro aplicado (facturas pendientes, vencidas, pagos pendientes, etc.).
- **CA-16.7** Si no hay datos, las tarjetas muestran 0 y los gráficos muestran "Sin datos" sin errores.
- **CA-16.8** Los importes se muestran con separador de miles y 2 decimales; los porcentajes con 1 decimal.

---

_Fuente: definición del Hito 2 (2026-11-20). Decisiones aprobadas en `README.md` de esta carpeta._
