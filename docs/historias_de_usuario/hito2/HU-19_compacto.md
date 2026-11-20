# HU-19 · Control de mora

**Grupo 7:** FACTURACIÓN Y PAGOS

Como Administrador o Propietario, quiero identificar facturas y pagos en mora, para hacer seguimiento de la cobranza.

**Contexto (Antecedentes):** Una factura pendiente cuyo vencimiento aplicable ya pasó se muestra como vencida. El pago tardío se registra con `en_mora = 1`. El Administrador ve toda la mora; el Propietario solo la de sus inmuebles.

## Reglas globales aplicables

- Se mantienen las reglas del Hito 1: password, email único case-insensitive, bajas lógicas, 403 amigable y ocultamiento de acciones no permitidas en la UI.
- El Administrador ve la mora de todos los inmuebles; el Propietario solo la de los propios.
- El vencimiento aplicable es el segundo si existe; en caso contrario, el primero.
- En el Hito 2 no se aplican intereses, recargos ni bloqueos automáticos por mora.

## Escenarios

- Factura vencida: aparece en el reporte con sus días de mora.
- Pago tardío: se registra con `en_mora = 1`.
- Pago tardío validado: la factura pasa a pagada y el pago conserva la marca de mora.
- Filtros por propietario, inmueble, vencimiento y días de mora.
- Sin mora: el reporte muestra el estado vacío correspondiente.

## Criterios de aceptación

- **CA-19.1** Una factura está en mora si sigue pendiente y su vencimiento aplicable es anterior a hoy. Es el mismo universo que el estado calculado "vencida".
- **CA-19.2** Un pago está en mora si `fecha_pago` es posterior al vencimiento aplicable; se guarda `en_mora = 1` al registrarse y no cambia después.
- **CA-19.3** El reporte muestra propietario, inmueble, servicio, período, vencimiento, días de mora y monto, ordenado por días de mora descendente.
- **CA-19.4** Filtros: propietario (solo Administrador), inmueble, rango de vencimiento y rango de días de mora. El Propietario solo ve sus facturas.
- **CA-19.5** El dashboard muestra cantidad y monto de facturas en mora y cantidad de pagos tardíos.
- **CA-19.6** Si un pago tardío se valida, la factura pasa a pagada pero el pago conserva `en_mora = 1` para el historial.
- **CA-19.7** Los días de mora se calculan como hoy menos el vencimiento aplicable, con mínimo 0.
- **CA-19.8** El Hito 2 no aplica intereses, recargos ni bloqueos automáticos por mora.

---

_Fuente: definición del Hito 2 (2026-11-20). Decisiones aprobadas en `README.md` de esta carpeta._
