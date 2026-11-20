# HU-10 · Alta de inmuebles

**Grupo 4:** INMUEBLES

Como Propietario/Gestor, quiero registrar un inmueble, para administrar mis propiedades.

## Reglas globales aplicables

- Password: mínimo 8 caracteres, al menos una letra y un número (registro, alta por Admin y password temporal).
- Email único, case-insensitive, incluye usuarios en Baja. Error: "El email ya se encuentra registrado".
- Bajas lógicas (nunca borrado físico), salvo tipo de servicio sin uso (HU-08).
- Sin permiso: error 403 "No tiene permisos para esta acción". Se muestra una página 403 amigable con el mensaje y un botón "Volver al dashboard"; no se redirige automáticamente.
- UI: se ocultan menús y acciones no permitidas para el rol o el estado del recurso (ej. un Propietario no ve el menú de Admin ni acciones sobre inmuebles ajenos). Ocultar es solo UX; no reemplaza la validación del servidor (403).

## Escenarios

- Alta exitosa (Propietario): alias, calle, número, piso, depto, ciudad, tipo, superficie, estado; aparece en su listado, asignado a él.
- Alta por Administrador: para el propietario indicado.
- Solo ve sus propios inmuebles.
- Validaciones: alias vacío -> "El alias es obligatorio"; calle vacía -> "La calle es obligatoria"; número vacío -> "El número es obligatorio"; ciudad vacía -> "La ciudad es obligatoria"; tipo vacío -> "El tipo es obligatorio"; estado vacío -> "El estado es obligatorio"; estado "Alquilado" -> "Estado inválido"; superficie <=0 -> "La superficie debe ser > 0"; no numérica -> "La superficie debe ser numérica"; más de 2 decimales -> "La superficie admite hasta 2 decimales"; tipo "Estacionamiento" -> "Tipo de inmueble inválido".

## Criterios de aceptación

- **CA-10.1** Obligatorios: alias, calle, número, ciudad, tipo, superficie, estado; piso/depto opcional.
- **CA-10.2** Tipo en {Casa, Departamento, Local Comercial, Oficina, Galpón}.
- **CA-10.3** Estado en {Ocupado, Disponible, En Mantenimiento}.
- **CA-10.4** Superficie positiva, máx. 2 decimales.
- **CA-10.5** Queda asociado al propietario que lo crea (o al indicado por el Admin).
- **CA-10.6** Un Propietario solo da de alta inmuebles para sí.

---

_Fuente: `HU_Hito1_compacto.txt`. Notas y decisiones de trazabilidad en la sección NOTAS de ese archivo._
