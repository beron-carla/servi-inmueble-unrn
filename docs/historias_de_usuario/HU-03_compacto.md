# HU-03 · Cierre de sesión

**Grupo 1:** ACCESO Y SESIÓN

Como usuario autenticado, quiero cerrar mi sesión, para proteger mi cuenta en equipos compartidos.

## Reglas globales aplicables

- Password: mínimo 8 caracteres, al menos una letra y un número (registro, alta por Admin y password temporal).
- Email único, case-insensitive, incluye usuarios en Baja. Error: "El email ya se encuentra registrado".
- Bajas lógicas (nunca borrado físico), salvo tipo de servicio sin uso (HU-08).
- Sin permiso: error 403 "No tiene permisos para esta acción". Se muestra una página 403 amigable con el mensaje y un botón "Volver al dashboard"; no se redirige automáticamente.
- UI: se ocultan menús y acciones no permitidas para el rol o el estado del recurso (ej. un Propietario no ve el menú de Admin ni acciones sobre inmuebles ajenos). Ocultar es solo UX; no reemplaza la validación del servidor (403).

## Escenarios

- Logout exitoso: presiona "Cerrar sesión", la sesión se destruye, es redirigido a /auth/login y al intentar entrar a una ruta protegida (ej. /dashboard) ve el login [H-24, ver NOTAS].

## Criterios de aceptación

- **CA-03.1** La cookie de sesión se elimina del cliente y el registro se invalida en el servidor.

---

_Fuente: `HU_Hito1_compacto.txt`. Notas y decisiones de trazabilidad en la sección NOTAS de ese archivo._
