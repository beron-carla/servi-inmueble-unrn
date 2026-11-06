# HU-01 · Registro de nuevos usuarios

**Grupo 1:** ACCESO Y SESIÓN

Como visitante del sitio, quiero crear una cuenta, para acceder a las funcionalidades autenticadas.

## Reglas globales aplicables

- Password: mínimo 8 caracteres, al menos una letra y un número (registro, alta por Admin y password temporal).
- Email único, case-insensitive, incluye usuarios en Baja. Error: "El email ya se encuentra registrado".
- Bajas lógicas (nunca borrado físico), salvo tipo de servicio sin uso (HU-08).
- Sin permiso: error 403 "No tiene permisos para esta acción". Se muestra una página 403 amigable con el mensaje y un botón "Volver al dashboard"; no se redirige automáticamente.
- UI: se ocultan menús y acciones no permitidas para el rol o el estado del recurso (ej. un Propietario no ve el menú de Admin ni acciones sobre inmuebles ajenos). Ocultar es solo UX; no reemplaza la validación del servidor (403).

## Escenarios

- Registro exitoso: en /auth/registro completa nombre, email, password (el formulario NO muestra rol), acepta términos y presiona "Registrarse". Ve "Registro exitoso", el usuario queda Activo con rol Propietario y recibe email de bienvenida.
- Email duplicado: error "El email ya se encuentra registrado", no se crea usuario.
- Validaciones (Esquema, columnas nombre/email/password): nombre vacío -> "El nombre es obligatorio"; email vacío -> "El email es obligatorio"; email inválido -> "El email debe tener formato válido"; password vacío -> "La contraseña es obligatoria"; password corto -> "Mínimo 8 caracteres"; password sin letra o sin número -> "Debe contener al menos una letra y un número".

## Criterios de aceptación

- **CA-01.1** Password hasheado (bcrypt/argon2), nunca texto plano.
- **CA-01.2** Email validado por formato y unicidad (case-insensitive, incluye Baja).
- **CA-01.3** Password mínimo 8, con letra y número.
- **CA-01.4** Rol por defecto Propietario; solo un Admin crea Administradores.
- **CA-01.5** Cuentas nuevas nacen Activas.
- **CA-01.6** La aceptación de términos es obligatoria para completar el registro.

---

_Fuente: `HU_Hito1_compacto.txt`. Notas y decisiones de trazabilidad en la sección NOTAS de ese archivo._
