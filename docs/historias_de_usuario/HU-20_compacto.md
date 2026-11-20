# HU-20 · Notificaciones internas

**Grupo 7:** FACTURACIÓN Y PAGOS

Como Propietario, quiero recibir notificaciones internas, para enterarme de vencimientos y del estado de mis pagos.

**Contexto (Antecedentes):** No existe un sistema de notificaciones. Se incorpora una campanita en la barra superior y un listado interno con avisos generados por la aplicación.

## Reglas globales aplicables

- Se mantienen las reglas del Hito 1: password, email único case-insensitive, bajas lógicas, 403 amigable y ocultamiento de acciones no permitidas en la UI.
- Las notificaciones son internas: en el Hito 2 no se envían emails ni mensajes externos.
- El destinatario de las notificaciones es el Propietario de la factura. El Administrador puede ver el listado global en modo lectura para soporte, sin avisos personales ni campanita propia por cada factura.
- Una notificación no modifica el estado de la factura ni del pago.

## Escenarios

- Aviso "por vencer": factura pendiente cuyo vencimiento aplicable cae dentro de los próximos 7 días.
- Aviso "vencida": factura pendiente cuyo vencimiento aplicable ya pasó.
- Aviso "pago validado": el Administrador valida un pago del propietario.
- Aviso "pago rechazado": el Administrador rechaza un pago e indica el motivo.
- Marcar una notificación como leída.
- Marcar todas como leídas.
- Sin duplicados para el mismo evento.
- Acceso a notificaciones ajenas: 403.
- Sin notificaciones: mensaje de estado vacío.

## Criterios de aceptación

- **CA-20.1** Existen cuatro tipos de notificación: `por_vencer`, `vencida`, `pago_validado` y `pago_rechazado`.
- **CA-20.2** `por_vencer`: factura pendiente con vencimiento aplicable entre hoy y hoy + 7 días inclusive (ventana configurable, 7 días por defecto). `vencida`: factura pendiente con vencimiento aplicable anterior a hoy.
- **CA-20.3** `pago_validado`: se genera al validar un pago (HU-18). `pago_rechazado`: se genera al rechazar un pago (HU-18) e incluye el motivo registrado.
- **CA-20.4** La generación es idempotente y sin duplicados: como máximo una notificación por usuario, factura y tipo para vencimientos; y como máximo una por usuario, transacción y tipo para pagos.
- **CA-20.5** El destinatario es el propietario de la factura. El Administrador puede consultar el listado global en modo lectura para soporte, sin recibir notificaciones personales ni contador de campanita por cada evento.
- **CA-20.6** La barra superior muestra una campanita con el contador de notificaciones no leídas del usuario destinatario. El listado se ordena por fecha de creación descendente y pagina.
- **CA-20.7** El destinatario puede marcar una notificación o todas como leídas. El Administrador no marca como leídas las notificaciones de terceros en el listado global.
- **CA-20.8** Acceder, marcar o consultar una notificación ajena fuera del alcance permitido devuelve 403; una inexistente devuelve 404.
- **CA-20.9** Sin notificaciones: "No tenés notificaciones".
- **CA-20.10** Las notificaciones de vencimiento se generan al ingresar al dashboard o al listado de facturas, sin depender de un cron. Las de pago se generan al validar o rechazar en HU-18. En el Hito 2 no se envían emails.
- **CA-20.11** Cada notificación enlaza a la factura correspondiente; las de pago muestran el estado del pago y, en caso de rechazo, el motivo.

---

_Fuente: definición del Hito 2 (2026-11-20). Decisiones aprobadas en `README.md` de esta carpeta._