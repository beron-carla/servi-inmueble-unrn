# HU-06 · Consulta y listado de usuarios

**Grupo 2:** GESTIÓN DE USUARIOS (solo Administrador)

Como Administrador, quiero consultar el listado de usuarios, para auditar quién tiene acceso.

**Contexto (Antecedentes):** Admin logueado; existen juan@mail.com (Activo) y maria@mail.com (Baja).

## Reglas globales aplicables

- Password: mínimo 8 caracteres, al menos una letra y un número (registro, alta por Admin y password temporal).
- Email único, case-insensitive, incluye usuarios en Baja. Error: "El email ya se encuentra registrado".
- Bajas lógicas (nunca borrado físico), salvo tipo de servicio sin uso (HU-08).
- Sin permiso: error 403 "No tiene permisos para esta acción". Se muestra una página 403 amigable con el mensaje y un botón "Volver al dashboard"; no se redirige automáticamente.
- UI: se ocultan menús y acciones no permitidas para el rol o el estado del recurso (ej. un Propietario no ve el menú de Admin ni acciones sobre inmuebles ajenos). Ocultar es solo UX; no reemplaza la validación del servidor (403).

## Escenarios

- Listado completo en /admin/usuarios: nombre, email, rol, estado y fecha de alta.
- Búsqueda por nombre o email (parcial, case-insensitive).
- Filtro por rol y estado.
- Detalle: perfil + resumen de inmuebles y facturas asociados (depende de HU-10 a 15, se implementa después).
- Acceso denegado: Propietario ve la página 403.
- Sin resultados: "No se encontraron usuarios".

## Criterios de aceptación

- **CA-06.1** Columnas: nombre, email, rol, estado, fecha de alta.
- **CA-06.2** Búsqueda parcial case-insensitive.
- **CA-06.3** Filtros por rol y estado.
- **CA-06.4** Solo Administrador.
- **CA-06.5** Estados vacíos y mensajes claros.

---

_Fuente: `HU_Hito1_compacto.txt`. Notas y decisiones de trazabilidad en la sección NOTAS de ese archivo._
