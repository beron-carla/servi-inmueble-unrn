# HU-05 · Modificación y baja de usuarios

**Grupo 2:** GESTIÓN DE USUARIOS (solo Administrador)

Como Administrador, quiero modificar y dar de baja usuarios, para mantener el control de accesos.

**Contexto (Antecedentes):** Admin logueado, existe maria@mail.com Propietario.

## Reglas globales aplicables

- Password: mínimo 8 caracteres, al menos una letra y un número (registro, alta por Admin y password temporal).
- Email único, case-insensitive, incluye usuarios en Baja. Error: "El email ya se encuentra registrado".
- Bajas lógicas (nunca borrado físico), salvo tipo de servicio sin uso (HU-08).
- Sin permiso: error 403 "No tiene permisos para esta acción". Se muestra una página 403 amigable con el mensaje y un botón "Volver al dashboard"; no se redirige automáticamente.
- UI: se ocultan menús y acciones no permitidas para el rol o el estado del recurso (ej. un Propietario no ve el menú de Admin ni acciones sobre inmuebles ajenos). Ocultar es solo UX; no reemplaza la validación del servidor (403).

## Escenarios

- Modificar datos: cambia nombre y rol; el listado lo refleja.
- Bloquear: estado Bloqueado; no puede iniciar sesión ("Usuario bloqueado, contacte al administrador"); se invalidan sus sesiones activas.
- Restablecer contraseña: genera password temporal (cumple regla de password), queda marcado para cambio en el próximo login, el anterior deja de servir.
- Baja lógica: estado Baja, no puede iniciar sesión, se invalidan sus sesiones, se conserva su historial de facturas, sigue en listados históricos con estado Baja.
- Reactivar: de Baja o Bloqueado a Activo, puede volver a iniciar sesión.
- No darse de baja a sí mismo: error "No puede darse de baja a sí mismo"; tampoco puede bloquearse ni quitarse el rol Administrador.
- Email duplicado al modificar: misma regla de unicidad del alta.
- Acceso denegado: Propietario por URL directa ve la página 403.

## Criterios de aceptación

- **CA-05.1** Editables: nombre, email, rol, estado (email con unicidad case-insensitive, incluye Baja; el rol Administrador propio no puede quitarse, CA-05.5).
- **CA-05.2** Estado en {Activo, Bloqueado, Baja}.
- **CA-05.3** Baja lógica, nunca borrado físico.
- **CA-05.4** Nunca se pierde el historial de facturas/pagos.
- **CA-05.5** Un Admin no puede darse de baja, bloquearse ni quitarse el rol Administrador a sí mismo.
- **CA-05.6** Auditoría básica: quién modificó y cuándo.
- **CA-05.7** Al bloquear o dar de baja se invalidan todas las sesiones activas del usuario (mismo mecanismo que CA-03.1).
- **CA-05.8** El password temporal cumple la política global, no puede ser igual al anterior y obliga cambio en el próximo login.

---

_Fuente: `HU_Hito1_compacto.txt`. Notas y decisiones de trazabilidad en la sección NOTAS de ese archivo._
