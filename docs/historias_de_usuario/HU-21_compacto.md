# HU-21 · Informe de consumo interanual

**Grupo 8:** INFORMES Y MÉTRICAS

Como Administrador o Propietario, quiero comparar el consumo mensual de un inmueble entre años, para detectar variaciones.

**Contexto (Antecedentes):** Las facturas guardan período, consumo y unidad de medida. El Hito 1 ya calcula y muestra facturas por período.

## Reglas globales aplicables

- Se mantienen las reglas del Hito 1: password, email único case-insensitive, bajas lógicas, 403 amigable y ocultamiento de acciones no permitidas en la UI.
- El Administrador accede a cualquier inmueble; el Propietario solo a los propios.
- Los datos se filtran en el servidor por rol.
- Los importes se muestran en ARS con 2 decimales.

## Escenarios

- Comparación de dos años para un inmueble y servicio.
- Cambio de inmueble, servicio o unidad.
- Meses sin factura: se muestran en 0.
- Facturas anuales: se informan aparte.
- Sin datos para los filtros elegidos.
- Acceso a un inmueble ajeno: 403.

## Criterios de aceptación

- **CA-21.1** El Administrador accede a cualquier inmueble; el Propietario solo a los propios; cualquier otro caso devuelve 403.
- **CA-21.2** Filtros: inmueble (obligatorio), servicio (opcional), año base (por defecto el actual), año comparado (por defecto el anterior) y unidad de medida.
- **CA-21.3** El gráfico muestra 12 puntos por año con el consumo mensual de cada uno; los meses sin factura valen 0.
- **CA-21.4** La tabla muestra mes, consumo del año base, consumo del año comparado, diferencia y variación porcentual, e incluye los totales anuales.
- **CA-21.5** Solo se incluyen facturas mensuales (`periodo = 'mes'`); las anuales se muestran en un resumen aparte.
- **CA-21.6** El agrupamiento usa `periodo_anio` y `periodo_mes`; no se mezclan inmuebles ni servicios no seleccionados.
- **CA-21.7** Sin datos: "No hay datos de consumo para los filtros seleccionados".
- **CA-21.8** Para servicios con unidad "Moneda fija" el gráfico muestra montos y la etiqueta "Monto", no consumo.
- **CA-21.9** Si el valor del año base es 0, la variación se muestra como "—" y no se divide por cero.

---

_Fuente: definición del Hito 2 (2026-11-20). Decisiones aprobadas en `README.md` de esta carpeta._
