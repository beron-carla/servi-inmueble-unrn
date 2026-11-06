# HU-14 · Alta de factura

**Grupo 5:** SERVICIOS Y FACTURAS

Como Propietario/Administrador, quiero registrar una factura con su consumo y vencimiento, para controlar la facturación del servicio.

**Contexto (Antecedentes):** Propietario logueado, "Depto Centro" con "Energía Eléctrica" Activo.

## Reglas globales aplicables

- Password: mínimo 8 caracteres, al menos una letra y un número (registro, alta por Admin y password temporal).
- Email único, case-insensitive, incluye usuarios en Baja. Error: "El email ya se encuentra registrado".
- Bajas lógicas (nunca borrado físico), salvo tipo de servicio sin uso (HU-08).
- Sin permiso: error 403 "No tiene permisos para esta acción". Se muestra una página 403 amigable con el mensaje y un botón "Volver al dashboard"; no se redirige automáticamente.
- UI: se ocultan menús y acciones no permitidas para el rol o el estado del recurso (ej. un Propietario no ve el menú de Admin ni acciones sobre inmuebles ajenos). Ocultar es solo UX; no reemplaza la validación del servidor (403).

## Escenarios

- Alta exitosa: período 10/2026, emisión, 1er y 2do vencimiento, monto, consumo, lectura anterior y actual; queda Pendiente, consumo = actual - anterior, aparece en pendientes.
- Lecturas: actual menor a anterior -> "La lectura actual debe ser mayor o igual a la anterior".
- Servicio sin medidor: permite cargar el consumo manualmente y no exige lecturas.
- Período duplicado: "Ya existe una factura para ese período".
- Vencimientos: 2do anterior al 1ro -> "El segundo vencimiento debe ser posterior al primero".
- Servicio Pausado: "No se pueden cargar facturas sobre un servicio pausado".
- Servicio En Revisión o Inactivo: "Solo se pueden cargar facturas sobre un servicio activo".
- Inmueble Inactivo: "No se pueden cargar facturas en un inmueble inactivo".
- Emisión futura: "La fecha de emisión no puede ser futura".
- Permisos: Propietario solo sobre servicios de inmuebles propios; Admin sobre cualquiera.
- Validaciones: período vacío -> "El período es obligatorio"; vencimiento vacío -> "La fecha de vencimiento es obligatoria"; monto vacío -> "El monto es obligatorio"; monto <=0 -> "El monto debe ser mayor a 0"; período 13/2026 -> "Período inválido"; consumo negativo -> "El consumo no puede ser negativo".

## Criterios de aceptación

- **CA-14.1** Estado en {Pendiente, Pagada, Vencida (calculado)}. Vencida = Pendiente cuyo vencimiento aplicable (2.º si existe; si no, 1.º) ya pasó.
- **CA-14.2** Campos: período, emisión, 1er vencimiento, 2do vencimiento (opcional), monto, consumo, unidad de medida, proveedor y categoría (snapshot del tipo al momento del alta), lectura anterior y actual (opcionales si el servicio no tiene medidor).
- **CA-14.3** Unicidad por (servicio, período).
- **CA-14.4** Consumo derivable de lecturas si hay medidor; sin medidor se ingresa manualmente.
- **CA-14.5** Emisión no futura.
- **CA-14.6** Solo servicios Activos. Un Propietario solo carga facturas de inmuebles propios; un Admin puede hacerlo sobre cualquier inmueble. Un inmueble Inactivo no admite facturas nuevas.

---

_Fuente: `HU_Hito1_compacto.txt`. Notas y decisiones de trazabilidad en la sección NOTAS de ese archivo._
