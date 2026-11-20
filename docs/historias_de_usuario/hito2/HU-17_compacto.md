# HU-17 · Registro de pago con comprobante digital

**Grupo 7:** FACTURACIÓN Y PAGOS

Como Propietario o Administrador, quiero registrar el pago de una factura adjuntando su comprobante, para dejar constancia y que el Administrador lo valide.

**Contexto (Antecedentes):** Propietario logueado con una factura pendiente o vencida. El pago queda pendiente hasta la validación del Administrador.

## Reglas globales aplicables

- Se mantienen las reglas del Hito 1: password, email único case-insensitive, bajas lógicas, 403 amigable y ocultamiento de acciones no permitidas en la UI.
- Solo el Administrador valida pagos; el Propietario solo registra pagos de sus propias facturas.
- El pago debe ser por el total de la factura: no se admiten pagos parciales en el Hito 2.
- Comprobantes: PDF, JPG o JPEG, hasta 5 MB, validados por contenido.
- Un pago no validado no cambia el estado de la factura.

## Escenarios

- Pago exitoso con comprobante: queda pendiente de validación y la factura sigue pendiente.
- Pago en efectivo: permite adjuntar comprobante o no.
- Monto distinto al total: se rechaza con el mensaje definido.
- Factura ya pagada: no admite un nuevo pago.
- Pago pendiente duplicado: no se permite otro pago mientras el anterior espera validación.
- Comprobante inválido: formato o tamaño fuera de lo permitido.
- Pago tardío: se registra con `en_mora = 1`.
- Acceso denegado: un Propietario no puede registrar pagos de facturas ajenas.

## Criterios de aceptación

- **CA-17.1** Puede registrar el pago el propietario de la factura o un Administrador; cualquier otro usuario recibe 403.
- **CA-17.2** Solo se admite sobre facturas en estado pendiente o vencida (calculado). Si la factura está pagada: "La factura ya está pagada".
- **CA-17.3** `monto_abonado` debe ser igual a `monto_total`; si no: "El monto debe ser igual al total de la factura". No se admiten pagos parciales.
- **CA-17.4** `fecha_pago` es obligatoria, válida y no futura: "La fecha de pago no puede ser futura".
- **CA-17.5** `medio_pago` es obligatorio y debe ser uno de {transferencia, débito automático, efectivo, billetera virtual}. `numero_comprobante` es obligatorio, alfanumérico, de 1 a 50 caracteres y único por factura.
- **CA-17.6** El comprobante es obligatorio para transferencia, débito automático y billetera virtual; es opcional para efectivo. Si se adjunta, el formato debe ser PDF, JPG o JPEG, máximo 5 MB, validado por contenido. Errores: "El comprobante debe ser PDF, JPG o JPEG" y "El comprobante no puede superar los 5 MB".
- **CA-17.7** Se guarda `en_mora = 1` si `fecha_pago` es posterior al vencimiento aplicable (2.º si existe; si no, 1.º); en caso contrario, 0.
- **CA-17.8** El pago se crea en estado "pendiente de validación" y la factura sigue pendiente. Si ya existe un pago pendiente para la factura: "Ya existe un pago pendiente de validación para esta factura".
- **CA-17.9** La operación usa CSRF y patrón PRG; éxito: "Pago registrado. Queda pendiente de validación."
- **CA-17.10** El comprobante no es accesible públicamente; solo el propietario de la factura o un Administrador pueden verlo o descargarlo.

---

_Fuente: definición del Hito 2 (2026-11-20). Decisiones aprobadas en `README.md` de esta carpeta._
