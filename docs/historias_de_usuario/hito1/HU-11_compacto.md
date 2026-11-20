# HU-11 · Modificación y baja lógica de inmuebles

**Grupo 4:** INMUEBLES

Como Propietario/Gestor, quiero modificar o dar de baja un inmueble, para mantener actualizada la información.

**Contexto (Antecedentes):** Propietario logueado, existe "Depto Centro" suyo.

## Reglas globales aplicables

- Password: mínimo 8 caracteres, al menos una letra y un número (registro, alta por Admin y password temporal).
- Email único, case-insensitive, incluye usuarios en Baja. Error: "El email ya se encuentra registrado".
- Bajas lógicas (nunca borrado físico), salvo tipo de servicio sin uso (HU-08).
- Sin permiso: error 403 "No tiene permisos para esta acción". Se muestra una página 403 amigable con el mensaje y un botón "Volver al dashboard"; no se redirige automáticamente.
- UI: se ocultan menús y acciones no permitidas para el rol o el estado del recurso (ej. un Propietario no ve el menú de Admin ni acciones sobre inmuebles ajenos). Ocultar es solo UX; no reemplaza la validación del servidor (403).

## Escenarios

- Modificar alias, superficie, estado.
- Cambiar a En Mantenimiento: servicios y facturas no se afectan.
- Baja lógica: estado Inactivo, facturas e historial consultables, no aparece por defecto en el listado activo.
- Inmueble Inactivo no admite servicios nuevos: "No se pueden agregar servicios a un inmueble inactivo".
- Inmueble Inactivo no admite facturas nuevas: "No se pueden cargar facturas en un inmueble inactivo".
- Reactivar a Disponible: vuelve al listado activo.
- Inmueble ajeno por URL directa: página 403.
- Validaciones: alias vacío -> "El alias es obligatorio"; superficie -5 -> "La superficie debe ser > 0"; estado "Alquilado" -> "Estado inválido".

## Criterios de aceptación

- **CA-11.1** Editables: alias, dirección, ciudad, tipo, superficie, estado.
- **CA-11.2** Estado en {Ocupado, Disponible, En Mantenimiento, Inactivo}.
- **CA-11.3** Baja lógica; jamás se borran facturas, servicios ni lecturas.
- **CA-11.4** Inmueble inactivo no admite nuevos servicios ni facturas.
- **CA-11.5** La reactivación restaura el acceso normal.
- **CA-11.6** Solo su propietario o un Admin puede modificarlo.

---

_Fuente: `HU_Hito1_compacto.txt`. Notas y decisiones de trazabilidad en la sección NOTAS de ese archivo._
