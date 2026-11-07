# HU-02 · Inicio de sesión

**Grupo 1:** ACCESO Y SESIÓN

Como usuario registrado, quiero iniciar sesión, para acceder a mi panel.

## Reglas globales aplicables

- Password: mínimo 8 caracteres, al menos una letra y un número (registro, alta por Admin y password temporal).
- Email único, case-insensitive, incluye usuarios en Baja. Error: "El email ya se encuentra registrado".
- Bajas lógicas (nunca borrado físico), salvo tipo de servicio sin uso (HU-08).
- Sin permiso: error 403 "No tiene permisos para esta acción". Se muestra una página 403 amigable con el mensaje y un botón "Volver al dashboard"; no se redirige automáticamente.
- UI: se ocultan menús y acciones no permitidas para el rol o el estado del recurso (ej. un Propietario no ve el menú de Admin ni acciones sobre inmuebles ajenos). Ocultar es solo UX; no reemplaza la validación del servidor (403).

## Escenarios

- Login exitoso: en /auth/login ingresa email y password, es redirigido a /dashboard y ve su nombre en la barra de navegación.
- Credenciales inválidas: "Email o contraseña incorrectos", permanece en /auth/login.
- Usuario Bloqueado (con credenciales correctas): "Usuario bloqueado, contacte al administrador", no se crea sesión.
- Usuario en Baja (con credenciales correctas): "Usuario dado de baja, contacte al administrador", no se crea sesión.
- Fuerza bruta: tras 5 intentos fallidos, bloqueo temporal de 15 minutos por cuenta (email) e IP, aunque use credenciales correctas: "Demasiados intentos, intente en 15 minutos". No cambia el estado del usuario (el estado Bloqueado solo lo asigna un Admin en HU-05). El mensaje es el mismo exista o no la cuenta.
- Password temporal: tras el login es redirigido a cambiar sgit u contraseña y no accede al dashboard hasta hacerlo.

## Criterios de aceptación

- **CA-02.1** ID de sesión aleatorio de alta entropía, regenerado tras el login (anti fixation).
- **CA-02.2** La sesión expira a los 30 min de inactividad y al hacer logout (reutiliza la invalidación de HU-03).
- **CA-02.3** Mensaje genérico para credenciales inválidas y bloqueo por intentos (no revela si el email existe); los mensajes de Bloqueado/Baja solo se muestran después de validar la contraseña.
- **CA-02.4** Todo acceso a rutas protegidas sin sesión redirige a /auth/login.

---

_Fuente: `HU_Hito1_compacto.txt`. Notas y decisiones de trazabilidad en la sección NOTAS de ese archivo._
