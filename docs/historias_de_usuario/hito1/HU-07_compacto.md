# HU-07 · Alta de tipos de servicio

**Grupo 3:** CATÁLOGO DE TIPOS DE SERVICIO

Como Administrador, quiero dar de alta tipos de servicio en el catálogo, para que los propietarios puedan vincularlos a sus inmuebles.

## Reglas globales aplicables

- Password: mínimo 8 caracteres, al menos una letra y un número (registro, alta por Admin y password temporal).
- Email único, case-insensitive, incluye usuarios en Baja. Error: "El email ya se encuentra registrado".
- Bajas lógicas (nunca borrado físico), salvo tipo de servicio sin uso (HU-08).
- Sin permiso: error 403 "No tiene permisos para esta acción". Se muestra una página 403 amigable con el mensaje y un botón "Volver al dashboard"; no se redirige automáticamente.
- UI: se ocultan menús y acciones no permitidas para el rol o el estado del recurso (ej. un Propietario no ve el menú de Admin ni acciones sobre inmuebles ajenos). Ocultar es solo UX; no reemplaza la validación del servidor (403).

## Escenarios

- Alta exitosa: nombre "Energía Eléctrica", categoría Público, unidad kWh, proveedor Edenor; aparece en el catálogo y los propietarios lo ven al vincular.
- Nombre duplicado: "Ya existe un tipo de servicio con ese nombre".
- Acceso denegado: Propietario en /admin/tipos-servicio/nuevo ve la página 403.
- Validaciones: nombre vacío -> "El nombre es obligatorio"; unidad vacía -> "La unidad de medida es obligatoria"; categoría vacía -> "La categoría es obligatoria"; categoría "Mixto" -> "Categoría inválida"; proveedor vacío -> "El proveedor es obligatorio"; unidad "Pesos" -> "Unidad de medida inválida".

## Criterios de aceptación

- **CA-07.1** Obligatorios: nombre, categoría, unidad, proveedor predeterminado.
- **CA-07.2** Categoría en {Público, Privado}.
- **CA-07.3** Unidad en {kWh, m³, Gigas, Moneda fija}; en el Hito 1 el catálogo de unidades es fijo.
- **CA-07.4** Nombre único, case-insensitive.
- **CA-07.5** Solo Administrador.
- **CA-07.6** Los tipos de servicio nuevos nacen activos y visibles para los propietarios.

---

_Fuente: `HU_Hito1_compacto.txt`. Notas y decisiones de trazabilidad en la sección NOTAS de ese archivo._
