# HU-08 · Modificación y baja de tipos de servicio

**Grupo 3:** CATÁLOGO DE TIPOS DE SERVICIO

Como Administrador, quiero modificar o dar de baja tipos de servicio, para mantener actualizado el catálogo maestro.

## Reglas globales aplicables

- Password: mínimo 8 caracteres, al menos una letra y un número (registro, alta por Admin y password temporal).
- Email único, case-insensitive, incluye usuarios en Baja. Error: "El email ya se encuentra registrado".
- Bajas lógicas (nunca borrado físico), salvo tipo de servicio sin uso (HU-08).
- Sin permiso: error 403 "No tiene permisos para esta acción". Se muestra una página 403 amigable con el mensaje y un botón "Volver al dashboard"; no se redirige automáticamente.
- UI: se ocultan menús y acciones no permitidas para el rol o el estado del recurso (ej. un Propietario no ve el menú de Admin ni acciones sobre inmuebles ajenos). Ocultar es solo UX; no reemplaza la validación del servidor (403).

## Escenarios

- Modificar unidad: "Internet" de Gigas a Moneda fija; facturas futuras usan la nueva, las existentes conservan la original.
- Modificar proveedor y categoría: el catálogo refleja los cambios.
- Nombre duplicado al modificar (case-insensitive): "Ya existe un tipo de servicio con ese nombre".
- Baja con servicios asociados: aviso "No se puede eliminar: tiene servicios asociados"; la única acción disponible es desactivar.
- Desactivar tipo con uso: no aparece para nuevos vínculos; servicios y facturas existentes intactos.
- Reactivar tipo desactivado: vuelve a aparecer para vincular.
- Baja de tipo sin uso: se elimina físicamente (única excepción a la baja lógica; solo si nunca tuvo servicios).
- Acceso denegado: Propietario ve la página 403.

## Criterios de aceptación

- **CA-08.1** Editables: nombre, categoría, unidad, proveedor y estado activo/inactivo.
- **CA-08.2** Cambiar unidad, proveedor o categoría no altera facturas ya registradas; cada factura conserva los valores del tipo al momento del alta.
- **CA-08.3** No se elimina un tipo con servicios vinculados: se desactiva.
- **CA-08.4** Se preserva siempre el historial de facturas y lecturas.
- **CA-08.5** Solo Administrador.
- **CA-08.6** La desactivación y reactivación solo la realiza un Admin; un tipo desactivado no aparece para nuevos vínculos.

---

_Fuente: `HU_Hito1_compacto.txt`. Notas y decisiones de trazabilidad en la sección NOTAS de ese archivo._
