# HU-22 · Promedio de consumo por unidad de medida

**Grupo 8:** INFORMES Y MÉTRICAS

Como Administrador o Propietario, quiero ver el consumo promedio por unidad de medida, para comparar el comportamiento entre servicios e inmuebles.

**Contexto (Antecedentes):** La factura guarda el snapshot de `unidad_medida` y `consumo_del_periodo`. Las unidades del catálogo son kWh, m³, Gigas y Moneda fija.

## Reglas globales aplicables

- Se mantienen las reglas del Hito 1: password, email único case-insensitive, bajas lógicas, 403 amigable y ocultamiento de acciones no permitidas en la UI.
- El Administrador ve todos los inmuebles; el Propietario solo los propios.
- Los datos se filtran en el servidor por rol.
- Los importes se muestran en ARS con 2 decimales.

## Escenarios

- Promedio de consumo para kWh, m³ y Gigas.
- Promedio de monto para "Moneda fija".
- Filtros por período, inmueble, tipo de servicio y unidad.
- Sin datos para los filtros elegidos.
- Acceso a datos ajenos: 403.

## Criterios de aceptación

- **CA-22.1** El Administrador ve todos los inmuebles; el Propietario solo ve los propios; cualquier otro caso devuelve 403.
- **CA-22.2** Filtros: rango de períodos (desde y hasta), inmueble (opcional; por defecto todos los permitidos por el rol), tipo de servicio y unidad de medida.
- **CA-22.3** Para kWh, m³ y Gigas se promedia `consumo_del_periodo` por unidad de medida, informando cantidad de facturas, promedio, mínimo y máximo.
- **CA-22.4** Para "Moneda fija" se promedia `monto_total` y se rotula "Monto promedio", porque el consumo no aplica.
- **CA-22.5** El gráfico de barras muestra el promedio por unidad de medida y la tabla informa unidad, cantidad de facturas, promedio, mínimo y máximo.
- **CA-22.6** Solo se incluyen facturas mensuales (`periodo = 'mes'`) para que los promedios sean comparables; las facturas anuales se informan en un resumen aparte.
- **CA-22.7** Sin datos: "No hay datos para los filtros seleccionados".
- **CA-22.8** Los consumos se muestran con 2 decimales y los montos en ARS con 2 decimales.

---

_Fuente: definición del Hito 2 (2026-11-20). Decisiones aprobadas en `README.md` de esta carpeta._
