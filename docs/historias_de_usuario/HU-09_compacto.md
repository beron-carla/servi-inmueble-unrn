# HU-09 · Consulta y listado de tipos de servicio

**Grupo 3:** CATÁLOGO DE TIPOS DE SERVICIO

Como usuario del sistema, quiero consultar el catálogo de tipos de servicio, para conocer los servicios disponibles y sus características.

**Contexto (Antecedentes):** Administrador logueado (el escenario del Propietario tiene su propio contexto).

## Reglas globales aplicables

- Password: mínimo 8 caracteres, al menos una letra y un número (registro, alta por Admin y password temporal).
- Email único, case-insensitive, incluye usuarios en Baja. Error: "El email ya se encuentra registrado".
- Bajas lógicas (nunca borrado físico), salvo tipo de servicio sin uso (HU-08).
- Sin permiso: error 403 "No tiene permisos para esta acción". Se muestra una página 403 amigable con el mensaje y un botón "Volver al dashboard"; no se redirige automáticamente.
- UI: se ocultan menús y acciones no permitidas para el rol o el estado del recurso (ej. un Propietario no ve el menú de Admin ni acciones sobre inmuebles ajenos). Ocultar es solo UX; no reemplaza la validación del servidor (403).

## Escenarios

- Listado Admin en /admin/tipos-servicio: nombre, categoría, unidad, proveedor, con acciones de edición.
- Catálogo Propietario: solo tipos activos, solo lectura (la selección al vincular se mapea en HU-13).
- Filtro por categoría.
- Filtro por estado activo.
- Detalle: categoría, unidad, proveedor y cantidad de inmuebles que lo tienen vinculado (depende de HU-13).
- Catálogo vacío: "No hay tipos de servicio registrados".

## Criterios de aceptación

- **CA-09.1** Listado: nombre, categoría, unidad, proveedor.
- **CA-09.2** Filtro por categoría y por estado activo.
- **CA-09.3** Admin ve catálogo completo con edición.
- **CA-09.4** Propietario solo consulta activos (lectura).
- **CA-09.5** El detalle muestra categoría, unidad, proveedor y cantidad de inmuebles vinculados.

---

_Fuente: `HU_Hito1_compacto.txt`. Notas y decisiones de trazabilidad en la sección NOTAS de ese archivo._
