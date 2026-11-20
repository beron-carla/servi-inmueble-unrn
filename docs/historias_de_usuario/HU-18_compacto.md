# HU-18 · Validación de pagos

**Grupo 7:** FACTURACIÓN Y PAGOS

Como Administrador, quiero revisar, validar o rechazar los pagos cargados, para confirmar la cobranza.

**Contexto (Antecedentes):** Existen pagos en estado pendiente de validación cargados por los propietarios.

## Reglas globales aplicables

- Se mantienen las reglas del Hito 1: password, email único case-insensitive, bajas lógicas, 403 amigable y ocultamiento de acciones no permitidas en la UI.
- Solo el Administrador valida o rechaza pagos.
- Un pago validado marca la factura como pagada; un pago rechazado no altera el estado de la factura.
- Los pagos validados son inmutables y no se eliminan.

## Escenarios

- Listado de pagos pendientes.
- Detalle de un pago con su comprobante.
- Validación exitosa: la factura queda pagada y el propietario recibe notificación interna.
- Rechazo con motivo: la factura sigue pendiente, el propietario recibe notificación con el motivo y puede volver a cargar el pago.
- Doble validación o pago ya procesado.
- Acceso denegado: un Propietario no accede al panel de validación.

## Criterios de aceptación

- **CA-18.1** Solo el Administrador accede al listado y a las acciones de validación; cualquier otro rol recibe 403.
- **CA-18.2** El listado permite filtrar por estado, propietario, factura, medio de pago y rango de fechas. Columnas: fecha de pago, propietario, inmueble, factura, monto, medio, comprobante y estado.
- **CA-18.3** El detalle muestra pago, factura, propietario, inmueble y permite previsualizar o descargar el comprobante.
- **CA-18.4** Validar: en una transacción, el pago pasa a "validada", se registran el Administrador validador y la fecha, y la factura pasa a "pagada". Si el pago ya fue procesado: "El pago ya fue procesado".
- **CA-18.5** Rechazar: exige un motivo de 1 a 255 caracteres; el pago pasa a "rechazada", la factura conserva su estado y el propietario puede cargar un nuevo pago. Sin motivo: "El motivo del rechazo es obligatorio".
- **CA-18.6** El comprobante solo lo ven el propietario de la factura y los Administradores; se sirve con el Content-Type y el nombre original, sin ejecución.
- **CA-18.7** Los pagos validados son inmutables: no se editan ni se eliminan. Los rechazados se conservan como historial.
- **CA-18.8** Sin pagos pendientes: "No hay pagos pendientes de validación".
- **CA-18.9** Al validar o rechazar se actualizan las métricas del dashboard.
- **CA-18.10** Al validar se genera una notificación interna `pago_validado` al propietario. Al rechazar se genera `pago_rechazado` al propietario incluyendo el motivo registrado (HU-20).

---

_Fuente: definición del Hito 2 (2026-11-20). Decisiones aprobadas en `README.md` de esta carpeta._
