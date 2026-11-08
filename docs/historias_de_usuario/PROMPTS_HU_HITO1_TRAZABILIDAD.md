# Prompts por Historia de Usuario · Hito 1 · Con trazabilidad

Este archivo contiene un prompt por cada HU de `HU_Hito1_compacto.txt`, enlazado con sus criterios de aceptación, dependencias y notas de trazabilidad (`H-24` a `H-33`).

> **Actualización 2026-11-08:** el esquema canónico es `bd/serviinmueble (1).sql` y el prompt general vigente es [`../PROMPT_GENERAL.md`](../PROMPT_GENERAL.md). La sección “PROMPT GENERAL” de este archivo queda como referencia histórica; no usarla para nuevas implementaciones.

## Cómo usar este archivo

1. Copiar el **PROMPT GENERAL** de [`../PROMPT_GENERAL.md`](../PROMPT_GENERAL.md) al inicio de la sesión de trabajo.
2. Copiar el prompt de la HU asignada.
3. Leer el archivo `HU-XX_compacto.md` correspondiente y el bloque de trazabilidad de `HU_Hito1_compacto.txt`.
4. Implementar solo el alcance de esa HU.
5. Validar cada `CA-XX.Y` y cada nota `H-XX` aplicable antes de cerrar.

## Matriz de trazabilidad

| HU | Archivo funcional | Fase | Depende de | Notas aplicables | CAs |
|---|---|---|---|---|---|
| **HU-01** · Registro de nuevos usuarios | [`HU-01_compacto.md`](HU-01_compacto.md) | Fase 1 · Autenticación | Ninguna | H-33 | CA-01.1 a CA-01.6 |
| **HU-02** · Inicio de sesión | [`HU-02_compacto.md`](HU-02_compacto.md) | Fase 1 · Autenticación | HU-01 | H-24 (referencia a /dashboard), H-33 | CA-02.1 a CA-02.4 |
| **HU-03** · Cierre de sesión | [`HU-03_compacto.md`](HU-03_compacto.md) | Fase 1 · Autenticación | HU-02 | H-24 (pendiente de validar), H-33 | CA-03.1 |
| **HU-04** · Alta de usuarios | [`HU-04_compacto.md`](HU-04_compacto.md) | Fase 2 · Usuarios y catálogo | HU-01, HU-02, HU-03 | H-32, H-33 | CA-04.1 a CA-04.6 |
| **HU-05** · Modificación y baja de usuarios | [`HU-05_compacto.md`](HU-05_compacto.md) | Fase 2 · Usuarios y catálogo | HU-04, HU-06 | H-32, H-33 | CA-05.1 a CA-05.8 |
| **HU-06** · Consulta y listado de usuarios | [`HU-06_compacto.md`](HU-06_compacto.md) | Fase 2 · Usuarios y catálogo | HU-04 | H-26, H-32, H-33 | CA-06.1 a CA-06.5 |
| **HU-07** · Alta de tipos de servicio | [`HU-07_compacto.md`](HU-07_compacto.md) | Fase 2 · Usuarios y catálogo | HU-04 | H-32, H-33 | CA-07.1 a CA-07.6 |
| **HU-08** · Modificación y baja de tipos de servicio | [`HU-08_compacto.md`](HU-08_compacto.md) | Fase 2 · Usuarios y catálogo | HU-07, HU-09 | H-25, H-26, H-27, H-32, H-33 | CA-08.1 a CA-08.6 |
| **HU-09** · Consulta y listado de tipos de servicio | [`HU-09_compacto.md`](HU-09_compacto.md) | Fase 2 · Usuarios y catálogo | HU-07 | H-26, H-32, H-33 | CA-09.1 a CA-09.5 |
| **HU-10** · Alta de inmuebles | [`HU-10_compacto.md`](HU-10_compacto.md) | Fase 3 · Inmuebles | HU-01 a HU-06, HU-07 a HU-09 | H-32, H-33 | CA-10.1 a CA-10.6 |
| **HU-11** · Modificación y baja lógica de inmuebles | [`HU-11_compacto.md`](HU-11_compacto.md) | Fase 3 · Inmuebles | HU-10, HU-12 | H-26, H-32, H-33 | CA-11.1 a CA-11.6 |
| **HU-12** · Consulta y listado de inmuebles | [`HU-12_compacto.md`](HU-12_compacto.md) | Fase 3 · Inmuebles | HU-10 | H-26, H-32, H-33 | CA-12.1 a CA-12.5 |
| **HU-13** · Vinculación de servicios a un inmueble | [`HU-13_compacto.md`](HU-13_compacto.md) | Fase 4 · Servicios | HU-10 a HU-12, HU-07 a HU-09 | H-29, H-30, H-32, H-33 | CA-13.1 a CA-13.9 |
| **HU-14** · Alta de factura | [`HU-14_compacto.md`](HU-14_compacto.md) | Fase 5 · Facturación | HU-13 | H-27, H-28, H-30, H-31, H-32, H-33 | CA-14.1 a CA-14.6 |
| **HU-15** · Listado de facturas con totales | [`HU-15_compacto.md`](HU-15_compacto.md) | Fase 5 · Facturación | HU-14 | H-28, H-32, H-33 | CA-15.1 a CA-15.5 |

## Notas de trazabilidad aplicables

| Nota | HUs | Decisión |
|---|---|---|
| **H-24** | HU-03 | Logout usa una ruta protegida genérica (`/dashboard`) en lugar de `/inmuebles`. Pendiente de validar con el equipo. |
| **H-25** | HU-08 | Los escenarios de HU-08 que dependen de servicios/facturas se completan en integración, después de HU-13 y HU-14; el resto va con el catálogo. |
| **H-26** | HU-06, HU-08, HU-09, HU-11, HU-12 | Los grupos son funcionales, no un orden estricto. Los detalles dependientes de módulos posteriores se completan en integración. |
| **H-27** | HU-08, HU-14 | La factura guarda snapshot de unidad, proveedor y categoría del tipo para preservar el historial. |
| **H-28** | HU-14, HU-15 | Vencida se calcula con el 2.º vencimiento si existe; si no, con el 1.º, y suma al total pendiente. |
| **H-29** | HU-13 | Un vínculo Inactivo permite revincular el mismo tipo; un vínculo Activo/Pausado/En Revisión no. |
| **H-30** | HU-13, HU-14 | En servicios sin medidor, las lecturas son opcionales y el consumo se ingresa manualmente. |
| **H-31** | HU-14 | Un Admin puede cargar facturas de cualquier inmueble; un Propietario solo de los propios. |
| **H-32** | HU-04 a HU-15 | Los accesos denegados conservan HTTP 403 y muestran una página amigable con botón Volver al dashboard; no redirigen automáticamente. |
| **H-33** | Todas | La UI oculta menús y acciones no permitidas; la autorización real se valida siempre en el servidor. |
| **H-14** | Todas | El Gherkin completo de los archivos originales no se reproduce; el compacto usa una línea por escenario. |

## PROMPT GENERAL

Copiar y pegar antes de cada prompt de HU.

~~~text
Actuá como desarrollador senior de CodeIgniter 4 para el proyecto "ServiInmueble 2026".

1. LECTURA OBLIGATORIA
- Leer siempre: agents/servi-inmueble-ci4/SKILL.md
- Leer el archivo funcional de la HU asignada: docs/historias_de_usuario/HU-XX_compacto.md
- Leer la trazabilidad y las notas: docs/historias_de_usuario/HU_Hito1_compacto.txt (secciones TRAZABILIDAD y NOTAS)
- Consultar docs/features-hito1.txt si hay dudas de alcance.
- Tener presentes los skills: ci4-module-scaffold, ci4-routes-guard, ci4-migration-builder, adminlte-view-craft, ci4-frontend-ui, ci4-security-audit, ci4-hu-validator, ci4-test-suite.

2. REGLA DE TRAZABILIDAD
- Cada criterio CA-XX.Y debe quedar implementado y con una prueba o verificación explícita.
- Cada nota H-XX aplicable debe respetarse; no contradecir H-27 a H-33.
- Si una nota está pendiente (H-24), no bloquear la implementación: documentar la decisión y continuar.
- Al finalizar, informar la matriz CA -> archivo/test y las notas aplicadas.

3. ARQUITECTURA OBLIGATORIA
- Flujo: Controller -> Service -> Repository -> Model/Entity.
- La lógica de negocio, validaciones, transiciones de estado y control de propiedad van en el Service.
- El Controller solo maneja HTTP, llama al Service, muestra flash y redirige con PRG.
- El Repository solo accede a datos con Query Builder/Model de CI4.
- No usar lógica de negocio en Controllers ni en Models/Entities.

4. REGLAS DEL PROYECTO
- CodeIgniter 4.x, PHP 8.2+, MySQL/MariaDB, AdminLTE 3/4.
- Sin SPA (no React, Vue ni Angular); formularios clásicos.
- Sin borrado físico: usar baja lógica con estados o soft-delete, salvo la excepción de HU-08.
- Control de propiedad obligatorio: un Propietario solo accede a sus inmuebles, servicios y facturas.
- Usar filtros auth/role en todas las rutas protegidas.
- Usar password_hash()/password_verify(), regenerar sesión al login y destruirla al logout.
- Usar CSRF en todos los formularios POST con csrf_field().
- Mensajes de usuario en español.
- Accesos denegados: HTTP 403 con página amigable y botón "Volver al dashboard"; no redirigir automáticamente (H-32).
- La UI oculta acciones no permitidas, pero la autorización se valida siempre en el servidor (H-33).

5. FLUJO DE TRABAJO
1) ci4-migration-builder
2) ci4-routes-guard
3) ci4-module-scaffold
4) adminlte-view-craft
5) ci4-security-audit
6) ci4-hu-validator
7) ci4-test-suite

6. VALIDACIÓN FINAL
- Ejecutar ci4-hu-validator contra la HU y sus CAs.
- Corregir todo lo marcado como FALTANTE o PARCIAL.
- Ejecutar las pruebas y reportar resultados.
- No inventar rutas, campos ni alcance fuera de la HU.

NO rompas la arquitectura. NO elimines registros históricos. NO implementes HUs fuera de alcance.
~~~

## Prompts por HU

### HU-01 · Registro de nuevos usuarios

- **Archivo funcional:** [`HU-01_compacto.md`](HU-01_compacto.md)
- **Grupo:** 1 · Acceso y sesión
- **Fase:** Fase 1 · Autenticación
- **Depende de:** Ninguna
- **Notas aplicables:** H-33
- **Riesgo de conflicto:** `AuthController.php`, `app/Config/Routes.php`, `app/Views/layout/sidebar.php`
- **Skills:** ci4-module-scaffold, ci4-routes-guard, adminlte-view-craft, ci4-security-audit, ci4-hu-validator

~~~text
Implementá la HU-01 · Registro de nuevos usuarios siguiendo el PROMPT GENERAL.

TRAZABILIDAD OBLIGATORIA
- Fuente funcional: docs/historias_de_usuario/HU-01_compacto.md
- Documento maestro: docs/historias_de_usuario/HU_Hito1_compacto.txt (TRAZABILIDAD y NOTAS)
- Grupo: 1 · Acceso y sesión
- Fase: Fase 1 · Autenticación
- Depende de: Ninguna
- Notas aplicables: H-33
- Riesgo de conflicto: `AuthController.php`, `app/Config/Routes.php`, `app/Views/layout/sidebar.php`

OBJETIVO
Como visitante del sitio, quiero crear una cuenta, para acceder a las funcionalidades autenticadas.

ALCANCE
- Implementar todos los escenarios de HU-01_compacto.md sin omitir ninguno.
- El formulario público no muestra rol; la aceptación de términos es obligatoria (CA-01.6). No permitir elegir Administrador desde el registro.

CRITERIOS DE ACEPTACIÓN TRAZABLES
- CA-01.1: Password hasheado (bcrypt/argon2), nunca texto plano.
- CA-01.2: Email validado por formato y unicidad (case-insensitive, incluye Baja).
- CA-01.3: Password mínimo 8, con letra y número.
- CA-01.4: Rol por defecto Propietario; solo un Admin crea Administradores.
- CA-01.5: Cuentas nuevas nacen Activas.
- CA-01.6: La aceptación de términos es obligatoria para completar el registro.

DECISIONES QUE NO PODÉS CONTRADECIR
- Aplicar las notas indicadas en "Notas aplicables", tal como están documentadas en HU_Hito1_compacto.txt.
- Mantener la coherencia con las HUs de las que depende: Ninguna.
- No resolver accesos denegados con redirecciones silenciosas: usar 403 con página amigable (H-32).
- Ocultar en la UI solo lo que el rol o el estado no permiten; validar siempre en el servidor (H-33).

ENTREGABLES
- Migración(es) y modelos/entidades necesarios.
- Repository, Service, Controller, rutas y vistas.
- Pruebas o verificaciones por cada CA.
- Checklist de aceptación completo.

CHECKLIST DE ACEPTACIÓN
- [ ] CA-01.1
- [ ] CA-01.2
- [ ] CA-01.3
- [ ] CA-01.4
- [ ] CA-01.5
- [ ] CA-01.6

VALIDACIÓN FINAL
- Ejecutar ci4-hu-validator y ci4-test-suite.
- Reportar: archivos creados/modificados, CAs cubiertos, notas aplicadas y pruebas ejecutadas.
- No cerrar la HU si queda algún CA FALTANTE o PARCIAL.
~~~

### HU-02 · Inicio de sesión

- **Archivo funcional:** [`HU-02_compacto.md`](HU-02_compacto.md)
- **Grupo:** 1 · Acceso y sesión
- **Fase:** Fase 1 · Autenticación
- **Depende de:** HU-01
- **Notas aplicables:** H-24 (referencia a /dashboard), H-33
- **Riesgo de conflicto:** `AuthController.php`, `app/Filters/*`, `app/Config/Routes.php`, `app/Views/layout/sidebar.php`
- **Skills:** ci4-module-scaffold, ci4-routes-guard, ci4-security-audit, ci4-hu-validator

~~~text
Implementá la HU-02 · Inicio de sesión siguiendo el PROMPT GENERAL.

TRAZABILIDAD OBLIGATORIA
- Fuente funcional: docs/historias_de_usuario/HU-02_compacto.md
- Documento maestro: docs/historias_de_usuario/HU_Hito1_compacto.txt (TRAZABILIDAD y NOTAS)
- Grupo: 1 · Acceso y sesión
- Fase: Fase 1 · Autenticación
- Depende de: HU-01
- Notas aplicables: H-24 (referencia a /dashboard), H-33
- Riesgo de conflicto: `AuthController.php`, `app/Filters/*`, `app/Config/Routes.php`, `app/Views/layout/sidebar.php`

OBJETIVO
Como usuario registrado, quiero iniciar sesión, para acceder a mi panel.

ALCANCE
- Implementar todos los escenarios de HU-02_compacto.md sin omitir ninguno.
- Usar /dashboard como destino posterior al login. Los mensajes de Bloqueado/Baja solo se muestran con credenciales correctas; el mensaje genérico no revela si el email existe. El bloqueo por fuerza bruta es por email e IP.

CRITERIOS DE ACEPTACIÓN TRAZABLES
- CA-02.1: ID de sesión aleatorio de alta entropía, regenerado tras el login (anti fixation).
- CA-02.2: La sesión expira a los 30 min de inactividad y al hacer logout (reutiliza la invalidación de HU-03).
- CA-02.3: Mensaje genérico para credenciales inválidas y bloqueo por intentos (no revela si el email existe); los mensajes de Bloqueado/Baja solo se muestran después de validar la contraseña.
- CA-02.4: Todo acceso a rutas protegidas sin sesión redirige a /auth/login.

DECISIONES QUE NO PODÉS CONTRADECIR
- Aplicar las notas indicadas en "Notas aplicables", tal como están documentadas en HU_Hito1_compacto.txt.
- Mantener la coherencia con las HUs de las que depende: HU-01.
- No resolver accesos denegados con redirecciones silenciosas: usar 403 con página amigable (H-32).
- Ocultar en la UI solo lo que el rol o el estado no permiten; validar siempre en el servidor (H-33).

ENTREGABLES
- Migración(es) y modelos/entidades necesarios.
- Repository, Service, Controller, rutas y vistas.
- Pruebas o verificaciones por cada CA.
- Checklist de aceptación completo.

CHECKLIST DE ACEPTACIÓN
- [ ] CA-02.1
- [ ] CA-02.2
- [ ] CA-02.3
- [ ] CA-02.4

VALIDACIÓN FINAL
- Ejecutar ci4-hu-validator y ci4-test-suite.
- Reportar: archivos creados/modificados, CAs cubiertos, notas aplicadas y pruebas ejecutadas.
- No cerrar la HU si queda algún CA FALTANTE o PARCIAL.
~~~

### HU-03 · Cierre de sesión

- **Archivo funcional:** [`HU-03_compacto.md`](HU-03_compacto.md)
- **Grupo:** 1 · Acceso y sesión
- **Fase:** Fase 1 · Autenticación
- **Depende de:** HU-02
- **Notas aplicables:** H-24 (pendiente de validar), H-33
- **Riesgo de conflicto:** `AuthController.php`, `app/Config/Routes.php`
- **Skills:** ci4-routes-guard, ci4-security-audit, ci4-hu-validator

~~~text
Implementá la HU-03 · Cierre de sesión siguiendo el PROMPT GENERAL.

TRAZABILIDAD OBLIGATORIA
- Fuente funcional: docs/historias_de_usuario/HU-03_compacto.md
- Documento maestro: docs/historias_de_usuario/HU_Hito1_compacto.txt (TRAZABILIDAD y NOTAS)
- Grupo: 1 · Acceso y sesión
- Fase: Fase 1 · Autenticación
- Depende de: HU-02
- Notas aplicables: H-24 (pendiente de validar), H-33
- Riesgo de conflicto: `AuthController.php`, `app/Config/Routes.php`

OBJETIVO
Como usuario autenticado, quiero cerrar mi sesión, para proteger mi cuenta en equipos compartidos.

ALCANCE
- Implementar todos los escenarios de HU-03_compacto.md sin omitir ninguno.
- Aplicar H-24: usar una ruta protegida genérica (ej. /dashboard) en lugar de /inmuebles. No bloquear la implementación por la validación pendiente; dejar la decisión documentada.

CRITERIOS DE ACEPTACIÓN TRAZABLES
- CA-03.1: La cookie de sesión se elimina del cliente y el registro se invalida en el servidor.

DECISIONES QUE NO PODÉS CONTRADECIR
- Aplicar las notas indicadas en "Notas aplicables", tal como están documentadas en HU_Hito1_compacto.txt.
- Mantener la coherencia con las HUs de las que depende: HU-02.
- No resolver accesos denegados con redirecciones silenciosas: usar 403 con página amigable (H-32).
- Ocultar en la UI solo lo que el rol o el estado no permiten; validar siempre en el servidor (H-33).

ENTREGABLES
- Migración(es) y modelos/entidades necesarios.
- Repository, Service, Controller, rutas y vistas.
- Pruebas o verificaciones por cada CA.
- Checklist de aceptación completo.

CHECKLIST DE ACEPTACIÓN
- [ ] CA-03.1

VALIDACIÓN FINAL
- Ejecutar ci4-hu-validator y ci4-test-suite.
- Reportar: archivos creados/modificados, CAs cubiertos, notas aplicadas y pruebas ejecutadas.
- No cerrar la HU si queda algún CA FALTANTE o PARCIAL.
~~~

### HU-04 · Alta de usuarios

- **Archivo funcional:** [`HU-04_compacto.md`](HU-04_compacto.md)
- **Grupo:** 2 · Gestión de usuarios (solo Administrador)
- **Fase:** Fase 2 · Usuarios y catálogo
- **Depende de:** HU-01, HU-02, HU-03
- **Notas aplicables:** H-32, H-33
- **Riesgo de conflicto:** `app/Controllers/Admin/UsuariosController.php`, `app/Views/admin/usuarios/*`, `app/Config/Routes.php`, `app/Views/layout/sidebar.php`
- **Skills:** ci4-migration-builder, ci4-routes-guard, ci4-module-scaffold, adminlte-view-craft, ci4-security-audit, ci4-hu-validator

~~~text
Implementá la HU-04 · Alta de usuarios siguiendo el PROMPT GENERAL.

TRAZABILIDAD OBLIGATORIA
- Fuente funcional: docs/historias_de_usuario/HU-04_compacto.md
- Documento maestro: docs/historias_de_usuario/HU_Hito1_compacto.txt (TRAZABILIDAD y NOTAS)
- Grupo: 2 · Gestión de usuarios (solo Administrador)
- Fase: Fase 2 · Usuarios y catálogo
- Depende de: HU-01, HU-02, HU-03
- Notas aplicables: H-32, H-33
- Riesgo de conflicto: `app/Controllers/Admin/UsuariosController.php`, `app/Views/admin/usuarios/*`, `app/Config/Routes.php`, `app/Views/layout/sidebar.php`

OBJETIVO
Como Administrador, quiero dar de alta usuarios, para autorizarles el acceso al sistema.

ALCANCE
- Implementar todos los escenarios de HU-04_compacto.md sin omitir ninguno.
- Solo un Administrador crea usuarios y asigna roles. El email es único incluso para usuarios en Baja. El usuario nuevo nace Activo. El password cumple la política global.

CRITERIOS DE ACEPTACIÓN TRAZABLES
- CA-04.1: Obligatorios: nombre, email, password, rol.
- CA-04.2: Email único (case-insensitive, incluye Baja).
- CA-04.3: Password hasheado; mínimo 8 con letra y número.
- CA-04.4: Rol en {Administrador, Propietario}.
- CA-04.5: Solo un Admin crea usuarios y asigna roles.
- CA-04.6: Usuario nuevo nace Activo.

DECISIONES QUE NO PODÉS CONTRADECIR
- Aplicar las notas indicadas en "Notas aplicables", tal como están documentadas en HU_Hito1_compacto.txt.
- Mantener la coherencia con las HUs de las que depende: HU-01, HU-02, HU-03.
- No resolver accesos denegados con redirecciones silenciosas: usar 403 con página amigable (H-32).
- Ocultar en la UI solo lo que el rol o el estado no permiten; validar siempre en el servidor (H-33).

ENTREGABLES
- Migración(es) y modelos/entidades necesarios.
- Repository, Service, Controller, rutas y vistas.
- Pruebas o verificaciones por cada CA.
- Checklist de aceptación completo.

CHECKLIST DE ACEPTACIÓN
- [ ] CA-04.1
- [ ] CA-04.2
- [ ] CA-04.3
- [ ] CA-04.4
- [ ] CA-04.5
- [ ] CA-04.6

VALIDACIÓN FINAL
- Ejecutar ci4-hu-validator y ci4-test-suite.
- Reportar: archivos creados/modificados, CAs cubiertos, notas aplicadas y pruebas ejecutadas.
- No cerrar la HU si queda algún CA FALTANTE o PARCIAL.
~~~

### HU-05 · Modificación y baja de usuarios

- **Archivo funcional:** [`HU-05_compacto.md`](HU-05_compacto.md)
- **Grupo:** 2 · Gestión de usuarios (solo Administrador)
- **Fase:** Fase 2 · Usuarios y catálogo
- **Depende de:** HU-04, HU-06
- **Notas aplicables:** H-32, H-33
- **Riesgo de conflicto:** `app/Controllers/Admin/UsuariosController.php`, `app/Views/admin/usuarios/*`, `app/Config/Routes.php`, `app/Views/layout/sidebar.php`
- **Skills:** ci4-module-scaffold, ci4-routes-guard, adminlte-view-craft, ci4-security-audit, ci4-hu-validator

~~~text
Implementá la HU-05 · Modificación y baja de usuarios siguiendo el PROMPT GENERAL.

TRAZABILIDAD OBLIGATORIA
- Fuente funcional: docs/historias_de_usuario/HU-05_compacto.md
- Documento maestro: docs/historias_de_usuario/HU_Hito1_compacto.txt (TRAZABILIDAD y NOTAS)
- Grupo: 2 · Gestión de usuarios (solo Administrador)
- Fase: Fase 2 · Usuarios y catálogo
- Depende de: HU-04, HU-06
- Notas aplicables: H-32, H-33
- Riesgo de conflicto: `app/Controllers/Admin/UsuariosController.php`, `app/Views/admin/usuarios/*`, `app/Config/Routes.php`, `app/Views/layout/sidebar.php`

OBJETIVO
Como Administrador, quiero modificar y dar de baja usuarios, para mantener el control de accesos.

CONTEXTO
Admin logueado, existe maria@mail.com Propietario.

ALCANCE
- Implementar todos los escenarios de HU-05_compacto.md sin omitir ninguno.
- Un Admin no puede darse de baja, bloquearse ni quitarse el rol Administrador a sí mismo. Al bloquear o dar de baja se invalidan todas las sesiones. El password temporal cumple la política y obliga cambio en el próximo login. Reactivación desde Baja o Bloqueado.

CRITERIOS DE ACEPTACIÓN TRAZABLES
- CA-05.1: Editables: nombre, email, rol, estado (email con unicidad case-insensitive, incluye Baja; el rol Administrador propio no puede quitarse, CA-05.5).
- CA-05.2: Estado en {Activo, Bloqueado, Baja}.
- CA-05.3: Baja lógica, nunca borrado físico.
- CA-05.4: Nunca se pierde el historial de facturas/pagos.
- CA-05.5: Un Admin no puede darse de baja, bloquearse ni quitarse el rol Administrador a sí mismo.
- CA-05.6: Auditoría básica: quién modificó y cuándo.
- CA-05.7: Al bloquear o dar de baja se invalidan todas las sesiones activas del usuario (mismo mecanismo que CA-03.1).
- CA-05.8: El password temporal cumple la política global, no puede ser igual al anterior y obliga cambio en el próximo login.

DECISIONES QUE NO PODÉS CONTRADECIR
- Aplicar las notas indicadas en "Notas aplicables", tal como están documentadas en HU_Hito1_compacto.txt.
- Mantener la coherencia con las HUs de las que depende: HU-04, HU-06.
- No resolver accesos denegados con redirecciones silenciosas: usar 403 con página amigable (H-32).
- Ocultar en la UI solo lo que el rol o el estado no permiten; validar siempre en el servidor (H-33).

ENTREGABLES
- Migración(es) y modelos/entidades necesarios.
- Repository, Service, Controller, rutas y vistas.
- Pruebas o verificaciones por cada CA.
- Checklist de aceptación completo.

CHECKLIST DE ACEPTACIÓN
- [ ] CA-05.1
- [ ] CA-05.2
- [ ] CA-05.3
- [ ] CA-05.4
- [ ] CA-05.5
- [ ] CA-05.6
- [ ] CA-05.7
- [ ] CA-05.8

VALIDACIÓN FINAL
- Ejecutar ci4-hu-validator y ci4-test-suite.
- Reportar: archivos creados/modificados, CAs cubiertos, notas aplicadas y pruebas ejecutadas.
- No cerrar la HU si queda algún CA FALTANTE o PARCIAL.
~~~

### HU-06 · Consulta y listado de usuarios

- **Archivo funcional:** [`HU-06_compacto.md`](HU-06_compacto.md)
- **Grupo:** 2 · Gestión de usuarios (solo Administrador)
- **Fase:** Fase 2 · Usuarios y catálogo
- **Depende de:** HU-04
- **Notas aplicables:** H-26, H-32, H-33
- **Riesgo de conflicto:** `app/Controllers/Admin/UsuariosController.php`, `app/Views/admin/usuarios/*`, `app/Config/Routes.php`, `app/Views/layout/sidebar.php`
- **Skills:** ci4-module-scaffold, ci4-routes-guard, adminlte-view-craft, ci4-hu-validator

~~~text
Implementá la HU-06 · Consulta y listado de usuarios siguiendo el PROMPT GENERAL.

TRAZABILIDAD OBLIGATORIA
- Fuente funcional: docs/historias_de_usuario/HU-06_compacto.md
- Documento maestro: docs/historias_de_usuario/HU_Hito1_compacto.txt (TRAZABILIDAD y NOTAS)
- Grupo: 2 · Gestión de usuarios (solo Administrador)
- Fase: Fase 2 · Usuarios y catálogo
- Depende de: HU-04
- Notas aplicables: H-26, H-32, H-33
- Riesgo de conflicto: `app/Controllers/Admin/UsuariosController.php`, `app/Views/admin/usuarios/*`, `app/Config/Routes.php`, `app/Views/layout/sidebar.php`

OBJETIVO
Como Administrador, quiero consultar el listado de usuarios, para auditar quién tiene acceso.

CONTEXTO
Admin logueado; existen juan@mail.com (Activo) y maria@mail.com (Baja).

ALCANCE
- Implementar todos los escenarios de HU-06_compacto.md sin omitir ninguno.
- El listado incluye fecha de alta. El detalle con resumen de inmuebles y facturas se completa en integración porque depende de HU-10 a 15 (H-26).

CRITERIOS DE ACEPTACIÓN TRAZABLES
- CA-06.1: Columnas: nombre, email, rol, estado, fecha de alta.
- CA-06.2: Búsqueda parcial case-insensitive.
- CA-06.3: Filtros por rol y estado.
- CA-06.4: Solo Administrador.
- CA-06.5: Estados vacíos y mensajes claros.

DECISIONES QUE NO PODÉS CONTRADECIR
- Aplicar las notas indicadas en "Notas aplicables", tal como están documentadas en HU_Hito1_compacto.txt.
- Mantener la coherencia con las HUs de las que depende: HU-04.
- No resolver accesos denegados con redirecciones silenciosas: usar 403 con página amigable (H-32).
- Ocultar en la UI solo lo que el rol o el estado no permiten; validar siempre en el servidor (H-33).

ENTREGABLES
- Migración(es) y modelos/entidades necesarios.
- Repository, Service, Controller, rutas y vistas.
- Pruebas o verificaciones por cada CA.
- Checklist de aceptación completo.

CHECKLIST DE ACEPTACIÓN
- [ ] CA-06.1
- [ ] CA-06.2
- [ ] CA-06.3
- [ ] CA-06.4
- [ ] CA-06.5

VALIDACIÓN FINAL
- Ejecutar ci4-hu-validator y ci4-test-suite.
- Reportar: archivos creados/modificados, CAs cubiertos, notas aplicadas y pruebas ejecutadas.
- No cerrar la HU si queda algún CA FALTANTE o PARCIAL.
~~~

### HU-07 · Alta de tipos de servicio

- **Archivo funcional:** [`HU-07_compacto.md`](HU-07_compacto.md)
- **Grupo:** 3 · Catálogo de tipos de servicio
- **Fase:** Fase 2 · Usuarios y catálogo
- **Depende de:** HU-04
- **Notas aplicables:** H-32, H-33
- **Riesgo de conflicto:** `app/Controllers/Admin/TiposServicioController.php`, `app/Views/admin/tipos_servicio/*`, `app/Config/Routes.php`, `app/Views/layout/sidebar.php`
- **Skills:** ci4-migration-builder, ci4-routes-guard, ci4-module-scaffold, adminlte-view-craft, ci4-security-audit, ci4-hu-validator

~~~text
Implementá la HU-07 · Alta de tipos de servicio siguiendo el PROMPT GENERAL.

TRAZABILIDAD OBLIGATORIA
- Fuente funcional: docs/historias_de_usuario/HU-07_compacto.md
- Documento maestro: docs/historias_de_usuario/HU_Hito1_compacto.txt (TRAZABILIDAD y NOTAS)
- Grupo: 3 · Catálogo de tipos de servicio
- Fase: Fase 2 · Usuarios y catálogo
- Depende de: HU-04
- Notas aplicables: H-32, H-33
- Riesgo de conflicto: `app/Controllers/Admin/TiposServicioController.php`, `app/Views/admin/tipos_servicio/*`, `app/Config/Routes.php`, `app/Views/layout/sidebar.php`

OBJETIVO
Como Administrador, quiero dar de alta tipos de servicio en el catálogo, para que los propietarios puedan vincularlos a sus inmuebles.

ALCANCE
- Implementar todos los escenarios de HU-07_compacto.md sin omitir ninguno.
- Los tipos nuevos nacen activos y visibles para los propietarios (CA-07.6). El catálogo de unidades es fijo en el Hito 1.

CRITERIOS DE ACEPTACIÓN TRAZABLES
- CA-07.1: Obligatorios: nombre, categoría, unidad, proveedor predeterminado.
- CA-07.2: Categoría en {Público, Privado}.
- CA-07.3: Unidad en {kWh, m³, Gigas, Moneda fija}; en el Hito 1 el catálogo de unidades es fijo.
- CA-07.4: Nombre único, case-insensitive.
- CA-07.5: Solo Administrador.
- CA-07.6: Los tipos de servicio nuevos nacen activos y visibles para los propietarios.

DECISIONES QUE NO PODÉS CONTRADECIR
- Aplicar las notas indicadas en "Notas aplicables", tal como están documentadas en HU_Hito1_compacto.txt.
- Mantener la coherencia con las HUs de las que depende: HU-04.
- No resolver accesos denegados con redirecciones silenciosas: usar 403 con página amigable (H-32).
- Ocultar en la UI solo lo que el rol o el estado no permiten; validar siempre en el servidor (H-33).

ENTREGABLES
- Migración(es) y modelos/entidades necesarios.
- Repository, Service, Controller, rutas y vistas.
- Pruebas o verificaciones por cada CA.
- Checklist de aceptación completo.

CHECKLIST DE ACEPTACIÓN
- [ ] CA-07.1
- [ ] CA-07.2
- [ ] CA-07.3
- [ ] CA-07.4
- [ ] CA-07.5
- [ ] CA-07.6

VALIDACIÓN FINAL
- Ejecutar ci4-hu-validator y ci4-test-suite.
- Reportar: archivos creados/modificados, CAs cubiertos, notas aplicadas y pruebas ejecutadas.
- No cerrar la HU si queda algún CA FALTANTE o PARCIAL.
~~~

### HU-08 · Modificación y baja de tipos de servicio

- **Archivo funcional:** [`HU-08_compacto.md`](HU-08_compacto.md)
- **Grupo:** 3 · Catálogo de tipos de servicio
- **Fase:** Fase 2 · Usuarios y catálogo
- **Depende de:** HU-07, HU-09
- **Notas aplicables:** H-25, H-26, H-27, H-32, H-33
- **Riesgo de conflicto:** `app/Controllers/Admin/TiposServicioController.php`, `app/Views/admin/tipos_servicio/*`, `app/Config/Routes.php`, `app/Views/layout/sidebar.php`
- **Skills:** ci4-module-scaffold, ci4-routes-guard, adminlte-view-craft, ci4-security-audit, ci4-hu-validator

~~~text
Implementá la HU-08 · Modificación y baja de tipos de servicio siguiendo el PROMPT GENERAL.

TRAZABILIDAD OBLIGATORIA
- Fuente funcional: docs/historias_de_usuario/HU-08_compacto.md
- Documento maestro: docs/historias_de_usuario/HU_Hito1_compacto.txt (TRAZABILIDAD y NOTAS)
- Grupo: 3 · Catálogo de tipos de servicio
- Fase: Fase 2 · Usuarios y catálogo
- Depende de: HU-07, HU-09
- Notas aplicables: H-25, H-26, H-27, H-32, H-33
- Riesgo de conflicto: `app/Controllers/Admin/TiposServicioController.php`, `app/Views/admin/tipos_servicio/*`, `app/Config/Routes.php`, `app/Views/layout/sidebar.php`

OBJETIVO
Como Administrador, quiero modificar o dar de baja tipos de servicio, para mantener actualizado el catálogo maestro.

ALCANCE
- Implementar todos los escenarios de HU-08_compacto.md sin omitir ninguno.
- No eliminar un tipo con servicios: desactivar. Aplicar snapshot de unidad, proveedor y categoría en la factura (H-27). Los escenarios que dependen de servicios o facturas se completan en integración después de HU-13 y HU-14 (H-25); el resto se implementa con el catálogo.

CRITERIOS DE ACEPTACIÓN TRAZABLES
- CA-08.1: Editables: nombre, categoría, unidad, proveedor y estado activo/inactivo.
- CA-08.2: Cambiar unidad, proveedor o categoría no altera facturas ya registradas; cada factura conserva los valores del tipo al momento del alta.
- CA-08.3: No se elimina un tipo con servicios vinculados: se desactiva.
- CA-08.4: Se preserva siempre el historial de facturas y lecturas.
- CA-08.5: Solo Administrador.
- CA-08.6: La desactivación y reactivación solo la realiza un Admin; un tipo desactivado no aparece para nuevos vínculos.

DECISIONES QUE NO PODÉS CONTRADECIR
- Aplicar las notas indicadas en "Notas aplicables", tal como están documentadas en HU_Hito1_compacto.txt.
- Mantener la coherencia con las HUs de las que depende: HU-07, HU-09.
- No resolver accesos denegados con redirecciones silenciosas: usar 403 con página amigable (H-32).
- Ocultar en la UI solo lo que el rol o el estado no permiten; validar siempre en el servidor (H-33).

ENTREGABLES
- Migración(es) y modelos/entidades necesarios.
- Repository, Service, Controller, rutas y vistas.
- Pruebas o verificaciones por cada CA.
- Checklist de aceptación completo.

CHECKLIST DE ACEPTACIÓN
- [ ] CA-08.1
- [ ] CA-08.2
- [ ] CA-08.3
- [ ] CA-08.4
- [ ] CA-08.5
- [ ] CA-08.6

VALIDACIÓN FINAL
- Ejecutar ci4-hu-validator y ci4-test-suite.
- Reportar: archivos creados/modificados, CAs cubiertos, notas aplicadas y pruebas ejecutadas.
- No cerrar la HU si queda algún CA FALTANTE o PARCIAL.
~~~

### HU-09 · Consulta y listado de tipos de servicio

- **Archivo funcional:** [`HU-09_compacto.md`](HU-09_compacto.md)
- **Grupo:** 3 · Catálogo de tipos de servicio
- **Fase:** Fase 2 · Usuarios y catálogo
- **Depende de:** HU-07
- **Notas aplicables:** H-26, H-32, H-33
- **Riesgo de conflicto:** `app/Controllers/Admin/TiposServicioController.php`, `app/Views/admin/tipos_servicio/*`, `app/Config/Routes.php`, `app/Views/layout/sidebar.php`
- **Skills:** ci4-module-scaffold, ci4-routes-guard, adminlte-view-craft, ci4-hu-validator

~~~text
Implementá la HU-09 · Consulta y listado de tipos de servicio siguiendo el PROMPT GENERAL.

TRAZABILIDAD OBLIGATORIA
- Fuente funcional: docs/historias_de_usuario/HU-09_compacto.md
- Documento maestro: docs/historias_de_usuario/HU_Hito1_compacto.txt (TRAZABILIDAD y NOTAS)
- Grupo: 3 · Catálogo de tipos de servicio
- Fase: Fase 2 · Usuarios y catálogo
- Depende de: HU-07
- Notas aplicables: H-26, H-32, H-33
- Riesgo de conflicto: `app/Controllers/Admin/TiposServicioController.php`, `app/Views/admin/tipos_servicio/*`, `app/Config/Routes.php`, `app/Views/layout/sidebar.php`

OBJETIVO
Como usuario del sistema, quiero consultar el catálogo de tipos de servicio, para conocer los servicios disponibles y sus características.

CONTEXTO
Administrador logueado (el escenario del Propietario tiene su propio contexto).

ALCANCE
- Implementar todos los escenarios de HU-09_compacto.md sin omitir ninguno.
- El Propietario solo ve tipos activos y en modo lectura. El detalle con cantidad de inmuebles vinculados depende de HU-13 y se completa en integración (H-26).

CRITERIOS DE ACEPTACIÓN TRAZABLES
- CA-09.1: Listado: nombre, categoría, unidad, proveedor.
- CA-09.2: Filtro por categoría y por estado activo.
- CA-09.3: Admin ve catálogo completo con edición.
- CA-09.4: Propietario solo consulta activos (lectura).
- CA-09.5: El detalle muestra categoría, unidad, proveedor y cantidad de inmuebles vinculados.

DECISIONES QUE NO PODÉS CONTRADECIR
- Aplicar las notas indicadas en "Notas aplicables", tal como están documentadas en HU_Hito1_compacto.txt.
- Mantener la coherencia con las HUs de las que depende: HU-07.
- No resolver accesos denegados con redirecciones silenciosas: usar 403 con página amigable (H-32).
- Ocultar en la UI solo lo que el rol o el estado no permiten; validar siempre en el servidor (H-33).

ENTREGABLES
- Migración(es) y modelos/entidades necesarios.
- Repository, Service, Controller, rutas y vistas.
- Pruebas o verificaciones por cada CA.
- Checklist de aceptación completo.

CHECKLIST DE ACEPTACIÓN
- [ ] CA-09.1
- [ ] CA-09.2
- [ ] CA-09.3
- [ ] CA-09.4
- [ ] CA-09.5

VALIDACIÓN FINAL
- Ejecutar ci4-hu-validator y ci4-test-suite.
- Reportar: archivos creados/modificados, CAs cubiertos, notas aplicadas y pruebas ejecutadas.
- No cerrar la HU si queda algún CA FALTANTE o PARCIAL.
~~~

### HU-10 · Alta de inmuebles

- **Archivo funcional:** [`HU-10_compacto.md`](HU-10_compacto.md)
- **Grupo:** 4 · Inmuebles
- **Fase:** Fase 3 · Inmuebles
- **Depende de:** HU-01 a HU-06, HU-07 a HU-09
- **Notas aplicables:** H-32, H-33
- **Riesgo de conflicto:** `app/Controllers/InmueblesController.php`, `app/Views/inmuebles/*`, `app/Config/Routes.php`, `app/Views/layout/sidebar.php`
- **Skills:** ci4-migration-builder, ci4-routes-guard, ci4-module-scaffold, adminlte-view-craft, ci4-security-audit, ci4-hu-validator

~~~text
Implementá la HU-10 · Alta de inmuebles siguiendo el PROMPT GENERAL.

TRAZABILIDAD OBLIGATORIA
- Fuente funcional: docs/historias_de_usuario/HU-10_compacto.md
- Documento maestro: docs/historias_de_usuario/HU_Hito1_compacto.txt (TRAZABILIDAD y NOTAS)
- Grupo: 4 · Inmuebles
- Fase: Fase 3 · Inmuebles
- Depende de: HU-01 a HU-06, HU-07 a HU-09
- Notas aplicables: H-32, H-33
- Riesgo de conflicto: `app/Controllers/InmueblesController.php`, `app/Views/inmuebles/*`, `app/Config/Routes.php`, `app/Views/layout/sidebar.php`

OBJETIVO
Como Propietario/Gestor, quiero registrar un inmueble, para administrar mis propiedades.

ALCANCE
- Implementar todos los escenarios de HU-10_compacto.md sin omitir ninguno.
- Validar calle, número, estado, superficie positiva y máximo 2 decimales. Un Propietario solo da de alta inmuebles para sí; un Admin puede asignar el propietario.

CRITERIOS DE ACEPTACIÓN TRAZABLES
- CA-10.1: Obligatorios: alias, calle, número, ciudad, tipo, superficie, estado; piso/depto opcional.
- CA-10.2: Tipo en {Casa, Departamento, Local Comercial, Oficina, Galpón}.
- CA-10.3: Estado en {Ocupado, Disponible, En Mantenimiento}.
- CA-10.4: Superficie positiva, máx. 2 decimales.
- CA-10.5: Queda asociado al propietario que lo crea (o al indicado por el Admin).
- CA-10.6: Un Propietario solo da de alta inmuebles para sí.

DECISIONES QUE NO PODÉS CONTRADECIR
- Aplicar las notas indicadas en "Notas aplicables", tal como están documentadas en HU_Hito1_compacto.txt.
- Mantener la coherencia con las HUs de las que depende: HU-01 a HU-06, HU-07 a HU-09.
- No resolver accesos denegados con redirecciones silenciosas: usar 403 con página amigable (H-32).
- Ocultar en la UI solo lo que el rol o el estado no permiten; validar siempre en el servidor (H-33).

ENTREGABLES
- Migración(es) y modelos/entidades necesarios.
- Repository, Service, Controller, rutas y vistas.
- Pruebas o verificaciones por cada CA.
- Checklist de aceptación completo.

CHECKLIST DE ACEPTACIÓN
- [ ] CA-10.1
- [ ] CA-10.2
- [ ] CA-10.3
- [ ] CA-10.4
- [ ] CA-10.5
- [ ] CA-10.6

VALIDACIÓN FINAL
- Ejecutar ci4-hu-validator y ci4-test-suite.
- Reportar: archivos creados/modificados, CAs cubiertos, notas aplicadas y pruebas ejecutadas.
- No cerrar la HU si queda algún CA FALTANTE o PARCIAL.
~~~

### HU-11 · Modificación y baja lógica de inmuebles

- **Archivo funcional:** [`HU-11_compacto.md`](HU-11_compacto.md)
- **Grupo:** 4 · Inmuebles
- **Fase:** Fase 3 · Inmuebles
- **Depende de:** HU-10, HU-12
- **Notas aplicables:** H-26, H-32, H-33
- **Riesgo de conflicto:** `app/Controllers/InmueblesController.php`, `app/Views/inmuebles/*`, `app/Config/Routes.php`, `app/Views/layout/sidebar.php`
- **Skills:** ci4-module-scaffold, ci4-routes-guard, adminlte-view-craft, ci4-security-audit, ci4-hu-validator

~~~text
Implementá la HU-11 · Modificación y baja lógica de inmuebles siguiendo el PROMPT GENERAL.

TRAZABILIDAD OBLIGATORIA
- Fuente funcional: docs/historias_de_usuario/HU-11_compacto.md
- Documento maestro: docs/historias_de_usuario/HU_Hito1_compacto.txt (TRAZABILIDAD y NOTAS)
- Grupo: 4 · Inmuebles
- Fase: Fase 3 · Inmuebles
- Depende de: HU-10, HU-12
- Notas aplicables: H-26, H-32, H-33
- Riesgo de conflicto: `app/Controllers/InmueblesController.php`, `app/Views/inmuebles/*`, `app/Config/Routes.php`, `app/Views/layout/sidebar.php`

OBJETIVO
Como Propietario/Gestor, quiero modificar o dar de baja un inmueble, para mantener actualizada la información.

CONTEXTO
Propietario logueado, existe "Depto Centro" suyo.

ALCANCE
- Implementar todos los escenarios de HU-11_compacto.md sin omitir ninguno.
- La baja es lógica (Inactivo). Un inmueble Inactivo no admite servicios ni facturas nuevas. La verificación de facturas se integra con HU-14 (H-26).

CRITERIOS DE ACEPTACIÓN TRAZABLES
- CA-11.1: Editables: alias, dirección, ciudad, tipo, superficie, estado.
- CA-11.2: Estado en {Ocupado, Disponible, En Mantenimiento, Inactivo}.
- CA-11.3: Baja lógica; jamás se borran facturas, servicios ni lecturas.
- CA-11.4: Inmueble inactivo no admite nuevos servicios ni facturas.
- CA-11.5: La reactivación restaura el acceso normal.
- CA-11.6: Solo su propietario o un Admin puede modificarlo.

DECISIONES QUE NO PODÉS CONTRADECIR
- Aplicar las notas indicadas en "Notas aplicables", tal como están documentadas en HU_Hito1_compacto.txt.
- Mantener la coherencia con las HUs de las que depende: HU-10, HU-12.
- No resolver accesos denegados con redirecciones silenciosas: usar 403 con página amigable (H-32).
- Ocultar en la UI solo lo que el rol o el estado no permiten; validar siempre en el servidor (H-33).

ENTREGABLES
- Migración(es) y modelos/entidades necesarios.
- Repository, Service, Controller, rutas y vistas.
- Pruebas o verificaciones por cada CA.
- Checklist de aceptación completo.

CHECKLIST DE ACEPTACIÓN
- [ ] CA-11.1
- [ ] CA-11.2
- [ ] CA-11.3
- [ ] CA-11.4
- [ ] CA-11.5
- [ ] CA-11.6

VALIDACIÓN FINAL
- Ejecutar ci4-hu-validator y ci4-test-suite.
- Reportar: archivos creados/modificados, CAs cubiertos, notas aplicadas y pruebas ejecutadas.
- No cerrar la HU si queda algún CA FALTANTE o PARCIAL.
~~~

### HU-12 · Consulta y listado de inmuebles

- **Archivo funcional:** [`HU-12_compacto.md`](HU-12_compacto.md)
- **Grupo:** 4 · Inmuebles
- **Fase:** Fase 3 · Inmuebles
- **Depende de:** HU-10
- **Notas aplicables:** H-26, H-32, H-33
- **Riesgo de conflicto:** `app/Controllers/InmueblesController.php`, `app/Views/inmuebles/*`, `app/Config/Routes.php`, `app/Views/layout/sidebar.php`
- **Skills:** ci4-module-scaffold, ci4-routes-guard, adminlte-view-craft, ci4-hu-validator

~~~text
Implementá la HU-12 · Consulta y listado de inmuebles siguiendo el PROMPT GENERAL.

TRAZABILIDAD OBLIGATORIA
- Fuente funcional: docs/historias_de_usuario/HU-12_compacto.md
- Documento maestro: docs/historias_de_usuario/HU_Hito1_compacto.txt (TRAZABILIDAD y NOTAS)
- Grupo: 4 · Inmuebles
- Fase: Fase 3 · Inmuebles
- Depende de: HU-10
- Notas aplicables: H-26, H-32, H-33
- Riesgo de conflicto: `app/Controllers/InmueblesController.php`, `app/Views/inmuebles/*`, `app/Config/Routes.php`, `app/Views/layout/sidebar.php`

OBJETIVO
Como Propietario/Gestor (y Administrador), quiero consultar los inmuebles en grilla con filtros, para encontrar rápido una propiedad.

ALCANCE
- Implementar todos los escenarios de HU-12_compacto.md sin omitir ninguno.
- Por defecto solo se listan inmuebles activos; el Inactivo aparece al filtrar explícitamente, también para el Admin. El detalle con servicios y facturación se completa en integración (H-26).

CRITERIOS DE ACEPTACIÓN TRAZABLES
- CA-12.1: Grilla/tarjetas con datos resumidos.
- CA-12.2: Filtros: ciudad, tipo, estado y (Admin) propietario. Por defecto solo se listan inmuebles activos; los inactivos aparecen al filtrar por estado Inactivo.
- CA-12.3: Propietario solo lista los suyos.
- CA-12.4: Admin audita el mapa general.
- CA-12.5: Detalle con servicios vinculados y resumen de facturación.

DECISIONES QUE NO PODÉS CONTRADECIR
- Aplicar las notas indicadas en "Notas aplicables", tal como están documentadas en HU_Hito1_compacto.txt.
- Mantener la coherencia con las HUs de las que depende: HU-10.
- No resolver accesos denegados con redirecciones silenciosas: usar 403 con página amigable (H-32).
- Ocultar en la UI solo lo que el rol o el estado no permiten; validar siempre en el servidor (H-33).

ENTREGABLES
- Migración(es) y modelos/entidades necesarios.
- Repository, Service, Controller, rutas y vistas.
- Pruebas o verificaciones por cada CA.
- Checklist de aceptación completo.

CHECKLIST DE ACEPTACIÓN
- [ ] CA-12.1
- [ ] CA-12.2
- [ ] CA-12.3
- [ ] CA-12.4
- [ ] CA-12.5

VALIDACIÓN FINAL
- Ejecutar ci4-hu-validator y ci4-test-suite.
- Reportar: archivos creados/modificados, CAs cubiertos, notas aplicadas y pruebas ejecutadas.
- No cerrar la HU si queda algún CA FALTANTE o PARCIAL.
~~~

### HU-13 · Vinculación de servicios a un inmueble

- **Archivo funcional:** [`HU-13_compacto.md`](HU-13_compacto.md)
- **Grupo:** 5 · Servicios y facturas
- **Fase:** Fase 4 · Servicios
- **Depende de:** HU-10 a HU-12, HU-07 a HU-09
- **Notas aplicables:** H-29, H-30, H-32, H-33
- **Riesgo de conflicto:** `app/Controllers/ServiciosInmuebleController.php`, `app/Views/servicios_inmueble/*`, `app/Config/Routes.php`, `app/Views/layout/sidebar.php`
- **Skills:** ci4-migration-builder, ci4-routes-guard, ci4-module-scaffold, adminlte-view-craft, ci4-security-audit, ci4-hu-validator

~~~text
Implementá la HU-13 · Vinculación de servicios a un inmueble siguiendo el PROMPT GENERAL.

TRAZABILIDAD OBLIGATORIA
- Fuente funcional: docs/historias_de_usuario/HU-13_compacto.md
- Documento maestro: docs/historias_de_usuario/HU_Hito1_compacto.txt (TRAZABILIDAD y NOTAS)
- Grupo: 5 · Servicios y facturas
- Fase: Fase 4 · Servicios
- Depende de: HU-10 a HU-12, HU-07 a HU-09
- Notas aplicables: H-29, H-30, H-32, H-33
- Riesgo de conflicto: `app/Controllers/ServiciosInmuebleController.php`, `app/Views/servicios_inmueble/*`, `app/Config/Routes.php`, `app/Views/layout/sidebar.php`

OBJETIVO
Como Propietario, quiero asociar un tipo de servicio a mi inmueble con sus datos de contrato, para poder cargar facturas.

CONTEXTO
Propietario logueado, inmueble "Depto Centro" suyo, catálogo con "Energía Eléctrica" (kWh, Edenor).

ALCANCE
- Implementar todos los escenarios de HU-13_compacto.md sin omitir ninguno.
- Un vínculo Inactivo no bloquea revincular el mismo tipo (H-29). Sin medidor, las lecturas son opcionales y el consumo se ingresa manualmente (H-30). El medidor es opcional. Un inmueble Inactivo no admite servicios nuevos (CA-13.9).

CRITERIOS DE ACEPTACIÓN TRAZABLES
- CA-13.1: Estados en {Activo, Pausado, En Revisión, Inactivo}.
- CA-13.2: Obligatorios: tipo, CPE/pagar, cuenta, domicilio de facturación, titular; medidor opcional.
- CA-13.3: Solo el propietario del inmueble o un Admin.
- CA-13.4: Un tipo solo puede tener un vínculo vigente (Activo, Pausado o En Revisión) por inmueble; un vínculo Inactivo permite volver a vincularlo.
- CA-13.5: Baja lógica; preserva lecturas, facturación y pagos.
- CA-13.6: Solo servicios Activos admiten carga de facturas.
- CA-13.7: Transiciones permitidas: Activo -> Pausado -> En Revisión -> Activo; desde cualquiera de los tres estados se puede pasar a Inactivo (baja).
- CA-13.8: Si el catálogo no tiene tipos activos, el alta muestra "No hay tipos de servicio disponibles. Contacte al administrador.".
- CA-13.9: Un inmueble Inactivo no admite servicios nuevos (misma regla que CA-11.4): "No se pueden agregar servicios a un inmueble inactivo".

DECISIONES QUE NO PODÉS CONTRADECIR
- Aplicar las notas indicadas en "Notas aplicables", tal como están documentadas en HU_Hito1_compacto.txt.
- Mantener la coherencia con las HUs de las que depende: HU-10 a HU-12, HU-07 a HU-09.
- No resolver accesos denegados con redirecciones silenciosas: usar 403 con página amigable (H-32).
- Ocultar en la UI solo lo que el rol o el estado no permiten; validar siempre en el servidor (H-33).

ENTREGABLES
- Migración(es) y modelos/entidades necesarios.
- Repository, Service, Controller, rutas y vistas.
- Pruebas o verificaciones por cada CA.
- Checklist de aceptación completo.

CHECKLIST DE ACEPTACIÓN
- [ ] CA-13.1
- [ ] CA-13.2
- [ ] CA-13.3
- [ ] CA-13.4
- [ ] CA-13.5
- [ ] CA-13.6
- [ ] CA-13.7
- [ ] CA-13.8
- [ ] CA-13.9

VALIDACIÓN FINAL
- Ejecutar ci4-hu-validator y ci4-test-suite.
- Reportar: archivos creados/modificados, CAs cubiertos, notas aplicadas y pruebas ejecutadas.
- No cerrar la HU si queda algún CA FALTANTE o PARCIAL.
~~~

### HU-14 · Alta de factura

- **Archivo funcional:** [`HU-14_compacto.md`](HU-14_compacto.md)
- **Grupo:** 5 · Servicios y facturas
- **Fase:** Fase 5 · Facturación
- **Depende de:** HU-13
- **Notas aplicables:** H-27, H-28, H-30, H-31, H-32, H-33
- **Riesgo de conflicto:** `app/Controllers/FacturasController.php`, `app/Views/facturas/*`, `app/Config/Routes.php`, `app/Views/layout/sidebar.php`
- **Skills:** ci4-migration-builder, ci4-routes-guard, ci4-module-scaffold, adminlte-view-craft, ci4-security-audit, ci4-hu-validator, ci4-test-suite

~~~text
Implementá la HU-14 · Alta de factura siguiendo el PROMPT GENERAL.

TRAZABILIDAD OBLIGATORIA
- Fuente funcional: docs/historias_de_usuario/HU-14_compacto.md
- Documento maestro: docs/historias_de_usuario/HU_Hito1_compacto.txt (TRAZABILIDAD y NOTAS)
- Grupo: 5 · Servicios y facturas
- Fase: Fase 5 · Facturación
- Depende de: HU-13
- Notas aplicables: H-27, H-28, H-30, H-31, H-32, H-33
- Riesgo de conflicto: `app/Controllers/FacturasController.php`, `app/Views/facturas/*`, `app/Config/Routes.php`, `app/Views/layout/sidebar.php`

OBJETIVO
Como Propietario/Administrador, quiero registrar una factura con su consumo y vencimiento, para controlar la facturación del servicio.

CONTEXTO
Propietario logueado, "Depto Centro" con "Energía Eléctrica" Activo.

ALCANCE
- Implementar todos los escenarios de HU-14_compacto.md sin omitir ninguno.
- Guardar snapshot de unidad, proveedor y categoría del tipo (H-27). Vencida se calcula con el 2.º vencimiento si existe; si no, con el 1.º (H-28). Sin medidor, consumo manual y lecturas opcionales (H-30). Un Admin puede cargar facturas de cualquier inmueble; un Propietario solo de los propios (H-31).

CRITERIOS DE ACEPTACIÓN TRAZABLES
- CA-14.1: Estado en {Pendiente, Pagada, Vencida (calculado)}. Vencida = Pendiente cuyo vencimiento aplicable (2.º si existe; si no, 1.º) ya pasó.
- CA-14.2: Campos: período, emisión, 1er vencimiento, 2do vencimiento (opcional), monto, consumo, unidad de medida, proveedor y categoría (snapshot del tipo al momento del alta), lectura anterior y actual (opcionales si el servicio no tiene medidor).
- CA-14.3: Unicidad por (servicio, período).
- CA-14.4: Consumo derivable de lecturas si hay medidor; sin medidor se ingresa manualmente.
- CA-14.5: Emisión no futura.
- CA-14.6: Solo servicios Activos. Un Propietario solo carga facturas de inmuebles propios; un Admin puede hacerlo sobre cualquier inmueble. Un inmueble Inactivo no admite facturas nuevas.

DECISIONES QUE NO PODÉS CONTRADECIR
- Aplicar las notas indicadas en "Notas aplicables", tal como están documentadas en HU_Hito1_compacto.txt.
- Mantener la coherencia con las HUs de las que depende: HU-13.
- No resolver accesos denegados con redirecciones silenciosas: usar 403 con página amigable (H-32).
- Ocultar en la UI solo lo que el rol o el estado no permiten; validar siempre en el servidor (H-33).

ENTREGABLES
- Migración(es) y modelos/entidades necesarios.
- Repository, Service, Controller, rutas y vistas.
- Pruebas o verificaciones por cada CA.
- Checklist de aceptación completo.

CHECKLIST DE ACEPTACIÓN
- [ ] CA-14.1
- [ ] CA-14.2
- [ ] CA-14.3
- [ ] CA-14.4
- [ ] CA-14.5
- [ ] CA-14.6

VALIDACIÓN FINAL
- Ejecutar ci4-hu-validator y ci4-test-suite.
- Reportar: archivos creados/modificados, CAs cubiertos, notas aplicadas y pruebas ejecutadas.
- No cerrar la HU si queda algún CA FALTANTE o PARCIAL.
~~~

### HU-15 · Listado de facturas con totales

- **Archivo funcional:** [`HU-15_compacto.md`](HU-15_compacto.md)
- **Grupo:** 5 · Servicios y facturas
- **Fase:** Fase 5 · Facturación
- **Depende de:** HU-14
- **Notas aplicables:** H-28, H-32, H-33
- **Riesgo de conflicto:** `app/Controllers/FacturasController.php`, `app/Views/facturas/*`, `app/Config/Routes.php`, `app/Views/layout/sidebar.php`
- **Skills:** ci4-module-scaffold, ci4-routes-guard, adminlte-view-craft, ci4-security-audit, ci4-hu-validator, ci4-test-suite

~~~text
Implementá la HU-15 · Listado de facturas con totales siguiendo el PROMPT GENERAL.

TRAZABILIDAD OBLIGATORIA
- Fuente funcional: docs/historias_de_usuario/HU-15_compacto.md
- Documento maestro: docs/historias_de_usuario/HU_Hito1_compacto.txt (TRAZABILIDAD y NOTAS)
- Grupo: 5 · Servicios y facturas
- Fase: Fase 5 · Facturación
- Depende de: HU-14
- Notas aplicables: H-28, H-32, H-33
- Riesgo de conflicto: `app/Controllers/FacturasController.php`, `app/Views/facturas/*`, `app/Config/Routes.php`, `app/Views/layout/sidebar.php`

OBJETIVO
Como Propietario/Administrador, quiero ver un listado tabular de facturas pendientes y pagadas con totales, para tener una vista rápida de mi situación.

ALCANCE
- Implementar todos los escenarios de HU-15_compacto.md sin omitir ninguno.
- Vencida suma al total pendiente y usa el vencimiento aplicable (2.º si existe; si no, 1.º) (H-28). Los totales se recalculan con los filtros aplicados. El orden por defecto es por vencimiento aplicable descendente.

CRITERIOS DE ACEPTACIÓN TRAZABLES
- CA-15.1: Tabla: inmueble, servicio, período, vencimiento aplicable (2.º si existe; si no, 1.º), monto, estado.
- CA-15.2: Sumarios: total pendiente (incluye Pendiente y Vencida), total pagado (Pagadas) y total general (pendiente + pagado).
- CA-15.3: Filtros: inmueble, servicio, estado y rango de fechas sobre el vencimiento aplicable.
- CA-15.4: Totales recalculados según filtros.
- CA-15.5: Orden por vencimiento aplicable descendente por defecto.

DECISIONES QUE NO PODÉS CONTRADECIR
- Aplicar las notas indicadas en "Notas aplicables", tal como están documentadas en HU_Hito1_compacto.txt.
- Mantener la coherencia con las HUs de las que depende: HU-14.
- No resolver accesos denegados con redirecciones silenciosas: usar 403 con página amigable (H-32).
- Ocultar en la UI solo lo que el rol o el estado no permiten; validar siempre en el servidor (H-33).

ENTREGABLES
- Migración(es) y modelos/entidades necesarios.
- Repository, Service, Controller, rutas y vistas.
- Pruebas o verificaciones por cada CA.
- Checklist de aceptación completo.

CHECKLIST DE ACEPTACIÓN
- [ ] CA-15.1
- [ ] CA-15.2
- [ ] CA-15.3
- [ ] CA-15.4
- [ ] CA-15.5

VALIDACIÓN FINAL
- Ejecutar ci4-hu-validator y ci4-test-suite.
- Reportar: archivos creados/modificados, CAs cubiertos, notas aplicadas y pruebas ejecutadas.
- No cerrar la HU si queda algún CA FALTANTE o PARCIAL.
~~~

## Checklist de cierre del Hito 1

- [ ] Las 15 HU tienen archivo funcional y prompt asociado.
- [ ] Cada CA está implementado y verificado.
- [ ] H-24 queda documentada como decisión pendiente.
- [ ] H-25 y H-26 se usan para separar implementación base e integración.
- [ ] H-27 a H-31 están aplicadas en HU-08, HU-13, HU-14 y HU-15.
- [ ] H-32 y H-33 están aplicadas en todos los accesos y en la UI.
- [ ] No se perdió ningún escenario del compacto.
