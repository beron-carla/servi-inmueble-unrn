# HU-13 · Vinculación de servicios a un inmueble

**Grupo 5:** SERVICIOS Y FACTURAS

Como Propietario, quiero asociar un tipo de servicio a mi inmueble con sus datos de contrato, para poder cargar facturas.

**Contexto (Antecedentes):** Propietario logueado, inmueble "Depto Centro" suyo, catálogo con "Energía Eléctrica" (kWh, Edenor).

## Reglas globales aplicables

- Password: mínimo 8 caracteres, al menos una letra y un número (registro, alta por Admin y password temporal).
- Email único, case-insensitive, incluye usuarios en Baja. Error: "El email ya se encuentra registrado".
- Bajas lógicas (nunca borrado físico), salvo tipo de servicio sin uso (HU-08).
- Sin permiso: error 403 "No tiene permisos para esta acción". Se muestra una página 403 amigable con el mensaje y un botón "Volver al dashboard"; no se redirige automáticamente.
- UI: se ocultan menús y acciones no permitidas para el rol o el estado del recurso (ej. un Propietario no ve el menú de Admin ni acciones sobre inmuebles ajenos). Ocultar es solo UX; no reemplaza la validación del servidor (403).

## Escenarios

- Alta de servicio: tipo, CPE/Pagar, cuenta, medidor (opcional), domicilio de facturación, titular; queda Activo y permite cargar facturas.
- Transiciones: Activo -> Pausado (no carga facturas) -> En Revisión -> Activo (vuelve a cargar).
- Baja: estado Inactivo; facturas, lecturas y pagos consultables; no crea facturas nuevas.
- Tipo ya vinculado vigente (Activo/Pausado/En Revisión) en el inmueble: "El inmueble ya tiene este servicio vinculado".
- Catálogo vacío o sin tipos activos: "No hay tipos de servicio disponibles. Contacte al administrador."
- Validaciones: tipo vacío -> "El tipo de servicio es obligatorio"; CPE vacío -> "El CPE/Pagar es obligatorio"; cuenta vacía -> "El número de cuenta es obligatorio"; domicilio vacío -> "El domicilio de facturación es obligatorio"; titular vacío -> "El titular es obligatorio".

## Criterios de aceptación

- **CA-13.1** Estados en {Activo, Pausado, En Revisión, Inactivo}.
- **CA-13.2** Obligatorios: tipo, CPE/pagar, cuenta, domicilio de facturación, titular; medidor opcional.
- **CA-13.3** Solo el propietario del inmueble o un Admin.
- **CA-13.4** Un tipo solo puede tener un vínculo vigente (Activo, Pausado o En Revisión) por inmueble; un vínculo Inactivo permite volver a vincularlo.
- **CA-13.5** Baja lógica; preserva lecturas, facturación y pagos.
- **CA-13.6** Solo servicios Activos admiten carga de facturas.
- **CA-13.7** Transiciones permitidas: Activo -> Pausado -> En Revisión -> Activo; desde cualquiera de los tres estados se puede pasar a Inactivo (baja).
- **CA-13.8** Si el catálogo no tiene tipos activos, el alta muestra "No hay tipos de servicio disponibles. Contacte al administrador.".
- **CA-13.9** Un inmueble Inactivo no admite servicios nuevos (misma regla que CA-11.4): "No se pueden agregar servicios a un inmueble inactivo".

---

_Fuente: `HU_Hito1_compacto.txt`. Notas y decisiones de trazabilidad en la sección NOTAS de ese archivo._
