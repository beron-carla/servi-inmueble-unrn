# HU-04 · Alta de usuarios

**Grupo 2:** GESTIÓN DE USUARIOS (solo Administrador)

Como Administrador, quiero dar de alta usuarios, para autorizarles el acceso al sistema.

## Reglas globales aplicables

- Password: mínimo 8 caracteres, al menos una letra y un número (registro, alta por Admin y password temporal).
- Email único, case-insensitive, incluye usuarios en Baja. Error: "El email ya se encuentra registrado".
- Bajas lógicas (nunca borrado físico), salvo tipo de servicio sin uso (HU-08).
- Sin permiso: error 403 "No tiene permisos para esta acción". Se muestra una página 403 amigable con el mensaje y un botón "Volver al dashboard"; no se redirige automáticamente.
- UI: se ocultan menús y acciones no permitidas para el rol o el estado del recurso (ej. un Propietario no ve el menú de Admin ni acciones sobre inmuebles ajenos). Ocultar es solo UX; no reemplaza la validación del servidor (403).

## Escenarios

- Acceso denegado: Propietario en /admin/usuarios/nuevo ve la página 403.
- Alta exitosa: Admin crea usuario con nombre, email, password, rol Propietario, estado Activo; aparece en el listado, password hasheado, puede iniciar sesión.
- Alta con rol Administrador: queda Administrador y puede administrar usuarios.
- Email duplicado: "El email ya se encuentra registrado", no se crea.
- Validaciones (Esquema nombre/email/password/rol): nombre vacío -> "El nombre es obligatorio"; email vacío -> "El email es obligatorio"; email inválido -> "El email debe tener formato válido"; password vacío -> "La contraseña es obligatoria"; password corto -> "Mínimo 8 caracteres de contraseña"; password sin letra o sin número -> "Debe contener al menos una letra y un número"; rol vacío -> "El rol es obligatorio"; rol "Superusuario" -> "Rol inválido".

## Criterios de aceptación

- **CA-04.1** Obligatorios: nombre, email, password, rol.
- **CA-04.2** Email único (case-insensitive, incluye Baja).
- **CA-04.3** Password hasheado; mínimo 8 con letra y número.
- **CA-04.4** Rol en {Administrador, Propietario}.
- **CA-04.5** Solo un Admin crea usuarios y asigna roles.
- **CA-04.6** Usuario nuevo nace Activo.

---

_Fuente: `HU_Hito1_compacto.txt`. Notas y decisiones de trazabilidad en la sección NOTAS de ese archivo._
