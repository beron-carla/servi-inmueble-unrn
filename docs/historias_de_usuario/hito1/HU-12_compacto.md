# HU-12 · Consulta y listado de inmuebles

**Grupo 4:** INMUEBLES

Como Propietario/Gestor (y Administrador), quiero consultar los inmuebles en grilla con filtros, para encontrar rápido una propiedad.

## Reglas globales aplicables

- Password: mínimo 8 caracteres, al menos una letra y un número (registro, alta por Admin y password temporal).
- Email único, case-insensitive, incluye usuarios en Baja. Error: "El email ya se encuentra registrado".
- Bajas lógicas (nunca borrado físico), salvo tipo de servicio sin uso (HU-08).
- Sin permiso: error 403 "No tiene permisos para esta acción". Se muestra una página 403 amigable con el mensaje y un botón "Volver al dashboard"; no se redirige automáticamente.
- UI: se ocultan menús y acciones no permitidas para el rol o el estado del recurso (ej. un Propietario no ve el menú de Admin ni acciones sobre inmuebles ajenos). Ocultar es solo UX; no reemplaza la validación del servidor (403).

## Escenarios

- Grilla en /inmuebles: alias, dirección, ciudad, tipo, superficie, estado.
- Filtro por ciudad y tipo.
- Filtro por estado; por defecto solo se listan inmuebles activos y el estado Inactivo se muestra al filtrar explícitamente.
- Detalle: todos los datos, servicios vinculados y estado de facturación.
- Listado Admin en /admin/inmuebles: todos los inmuebles activos, con filtro por propietario y por estado (Inactivo al filtrar explícitamente).
- Sin resultados: "No se encontraron inmuebles".

## Criterios de aceptación

- **CA-12.1** Grilla/tarjetas con datos resumidos.
- **CA-12.2** Filtros: ciudad, tipo, estado y (Admin) propietario. Por defecto solo se listan inmuebles activos; los inactivos aparecen al filtrar por estado Inactivo.
- **CA-12.3** Propietario solo lista los suyos.
- **CA-12.4** Admin audita el mapa general.
- **CA-12.5** Detalle con servicios vinculados y resumen de facturación.

---

_Fuente: `HU_Hito1_compacto.txt`. Notas y decisiones de trazabilidad en la sección NOTAS de ese archivo._
