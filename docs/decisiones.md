# Decisiones de arquitectura y esquema · ServiInmueble 2026

**Proyecto:** ServiInmueble 2026 (CodeIgniter 4.7.5 · PHP 8.3 · MariaDB 10.11)
**Hito:** 1 (HU-01 a HU-15)
**Fecha:** 2026-11-08
**Estado:** vigente
**Fuente canónica del esquema:** [`bd/serviinmueble (1).sql`](<../bd/serviinmueble (1).sql>)

---

## 1. Objetivo

Este documento fija las decisiones que **no** deben quedar libradas a la interpretación de cada HU o de cada skill. Su función es evitar que dos personas implementen el mismo sistema con nombres de tablas, estados o flujos distintos.

Regla de oro:

> Si el código, un skill o un prompt contradice este documento o el SQL canónico, **manda el SQL canónico** y este documento explica cómo mapearlo.

---

## 2. Fuente canónica y precedencia

Orden de precedencia para cualquier decisión técnica:

1. `bd/serviinmueble (1).sql` — DDL real de la base de datos.
2. `docs/decisiones.md` — este documento (mapeos, interpretaciones y pendientes).
3. `docs/rutas.md` — mapa de rutas, controladores y filtros.
4. `docs/historias_de_usuario/HU-XX_compacto.md` — alcance funcional de cada HU.
5. `docs/historias_de_usuario/HU_Hito1_compacto.txt` — notas de trazabilidad H-24 a H-33.
6. `docs/00_bootstrap.md` — base técnica previa a HU-01.
7. `agents/*/SKILL.md` — convenciones de código y arquitectura.

**Excepción importante:** la sección 6 del skill `agents/servi-inmueble-ci4/SKILL.md` describe un esquema anterior (`roles`, `usuarios`, `inmuebles`, `servicios_inmueble`, `facturas`, `id_rol`, `password_hash`). Ese esquema **no se usa**. El esquema vigente es el del SQL y el de este documento. El skill debe actualizarse o interpretarse con esta salvedad.

---

## 3. Principios generales

- **Nombres en español** para tablas, columnas, clases, rutas y mensajes.
- **snake_case** en base de datos; **PascalCase** en clases; **camelCase** en métodos y variables.
- **Sin borrado físico** de usuarios, propiedades, servicios, facturas ni pagos. Se usan estados o banderas (`estado`, `activo`).
- **Excepción única:** un `tipo_servicio` sin ningún `propiedad_servicio` asociado puede eliminarse físicamente (HU-08).
- **Control de propiedad en el servidor**, no solo en la UI.
- **HTTP 403 real** para accesos denegados, con página amigable y botón “Volver al dashboard”. No redirecciones silenciosas.
- **CSRF obligatorio** en todo POST.
- **PRG** (Post/Redirect/Get) en toda mutación.
- **Historial preservado:** las facturas guardan snapshot de proveedor, categoría y unidad de medida.

---

## 4. Esquema canónico: tablas y responsabilidades

| Tabla | Rol | Claves y particularidades |
|---|---|---|
| `usuario` | Usuarios del sistema | `id_usuario`; `mail` único; `rol` ENUM(`propietario`,`admin`); `estado` ENUM(`activo`,`bloqueado`,`baja`); `cambiar_contrasenia` |
| `tipo_inmueble` | Catálogo de tipos de inmueble | ENUM(`casa`,`departamento`,`local comercial`,`oficina`,`galpon`) |
| `direccion` | Dirección de una propiedad | `calle`, `numero`, `piso`, `depto`, `ciudad`; sin `provincia` |
| `propiedad` | Inmueble | `id_propietario` → `usuario`; `id_tipo_inmueble`; `id_direccion`; `estado` ENUM(`ocupado`,`disponible`,`en mantenimiento`,`inactivo`) |
| `tipo_servicio` | Catálogo maestro de servicios | `tipo` ENUM de 9 valores; `categoria` ENUM(`publico`,`privado`); `unidad_medida` ENUM; `proveedor_predeterminado`; `activo` |
| `propiedad_servicio` | Vínculo servicio-inmueble | `id_propiedad`, `id_tipo_servicio`; `codigo_pago_electronico`, `numero_cuenta_contrato`, `numero_medidor` (NULL), `domicilio_facturacion`, `titular`; `estado` ENUM(`activo`,`pausado`,`en revision`,`inactivo`) |
| `factura` | Factura de un servicio | `id_propiedad_servicio`; `periodo` ENUM(`mes`,`anio`); `periodo_mes` ENUM(`00`..`12`); `periodo_anio` YEAR; vencimientos; `monto_total`; `consumo_del_periodo`; lecturas; snapshot `unidad_medida`, `proveedor`, `categoria_factura`; `estado` ENUM(`pendiente`,`pagada`) |
| `transaccion` | Pago de una factura | `id_factura`, `id_propietario`; `fecha_pago`, `monto_abonado`, `medio_pago`, `numero_comprobante`, `comprobante_digital`, `en_mora` |
| `sesion` | Sesiones activas | `id_usuario`, `token` CHAR(64) único, `fecha_creacion`, `ultima_actividad` |
| `historico_login` | Intentos de login | `email`, `ip`, `exitoso` (0/1), `fecha`; sin FK a usuario |
| `modificacion_usuario` | Auditoría de cambios de admin | `id_usuario`, `id_admin`, `fecha_modificacion` |

---

## 5. Mapa de nombres: skills antiguos → SQL canónico

| Concepto / nombre antiguo | Nombre vigente |
|---|---|
| `roles` (tabla) | **No existe.** `usuario.rol` ENUM(`propietario`,`admin`) |
| `usuarios` | `usuario` |
| `usuarios.nombre` | `usuario.nombre_completo` |
| `usuarios.email` | `usuario.mail` |
| `usuarios.password_hash` | `usuario.contrasenia` |
| `usuarios.id_rol` | `usuario.rol` |
| `usuarios.created_at` | `usuario.fecha_alta` |
| `usuarios.deleted_at` | `usuario.estado = 'baja'` |
| `tipos_servicio` | `tipo_servicio` |
| `tipos_servicio.nombre` | `tipo_servicio.tipo` (ENUM) |
| `tipos_servicio.proveedor_predeterminado` | `tipo_servicio.proveedor_predeterminado` |
| `inmuebles` | `propiedad` + `direccion` |
| `inmuebles.id_usuario_propietario` | `propiedad.id_propietario` |
| `inmuebles.calle/numero/piso/depto/ciudad` | `direccion.calle/numero/piso/depto/ciudad` |
| `inmuebles.tipo` | `propiedad.id_tipo_inmueble` → `tipo_inmueble.nombre` |
| `servicios_inmueble` | `propiedad_servicio` |
| `servicios_inmueble.id_inmueble` | `propiedad_servicio.id_propiedad` |
| `servicios_inmueble.cpe_pagar` | `propiedad_servicio.codigo_pago_electronico` |
| `servicios_inmueble.numero_cuenta` | `propiedad_servicio.numero_cuenta_contrato` |
| `servicios_inmueble.estado` | `propiedad_servicio.estado` (mismos valores, en minúscula) |
| `facturas` | `factura` |
| `facturas.id_servicio_inmueble` | `factura.id_propiedad_servicio` |
| `facturas.periodo` (MM/YYYY) | `factura.periodo` + `factura.periodo_mes` + `factura.periodo_anio` |
| `facturas.fecha_vto1` | `factura.fecha_primer_vencimiento` |
| `facturas.fecha_vto2` | `factura.fecha_segundo_vencimiento` |
| `facturas.consumo` | `factura.consumo_del_periodo` |
| `facturas.estado = Vencida` | **No se guarda.** Se calcula (ver D-16) |
| `facturas.fecha_pago/monto_abonado/medio_pago/nro_comprobante` | `transaccion.*` |
| `pagos` | `transaccion` |

---

## 6. Decisiones vigentes

### D-01 · El esquema canónico es el SQL

**Decisión:** todo el código nuevo usa exclusivamente las tablas y columnas de `bd/serviinmueble (1).sql`.
**Motivo:** es el DDL que ya está importado en la base `serviinmueble` y el que el equipo acordó como fuente de verdad.
**Impacto:** los skills que describen `roles`, `usuarios`, `inmuebles`, `servicios_inmueble` o `facturas` quedan obsoletos en su sección de esquema. Se debe usar el mapa de la sección 5.

---

### D-02 · Roles sin tabla `roles`

**Decisión:** el rol vive en `usuario.rol` con valores `propietario` y `admin`.
**Etiquetas de UI:** `Propietario` y `Administrador`.
**Motivo:** el SQL no tiene tabla `roles` y no se va a crear una.
**Impacto:** `RoleFilter` compara contra `session('rol')` o el valor de la BD, no contra `id_rol`. Los seeds no crean roles; crean usuarios con `rol`.

---

### D-03 · Email único case-insensitive

**Decisión:** normalizar `mail` a minúsculas y `trim()` antes de guardar y antes de buscar. La columna es `utf8mb4_unicode_ci`, por lo que el índice único ya es case-insensitive.
**Alcance:** la unicidad incluye usuarios en estado `baja` (no se filtra por estado).
**Mensaje:** “El email ya se encuentra registrado”.

---

### D-04 · Contraseñas

**Decisión:** `password_hash()` con `PASSWORD_ARGON2ID`; si la extensión no lo soporta, `PASSWORD_BCRYPT`. Verificación con `password_verify()`.
**Columna:** `usuario.contrasenia` VARCHAR(255).
**Política:** mínimo 8 caracteres, al menos una letra y un número. Regex sugerida: `/^(?=.*[A-Za-z])(?=.*\d).{8,}$/`.
**Contraseña temporal:** se genera con letras y números, se hashea, se guarda `cambiar_contrasenia = 1` y se muestra **una sola vez** al administrador que la generó. No se envía por email en el Hito 1.

---

### D-05 · Cambio obligatorio de contraseña

**Decisión:** si `usuario.cambiar_contrasenia = 1`, después del login el usuario solo puede acceder a `GET/POST /auth/cambiar-contrasenia`. Cualquier otra ruta protegida lo redirige allí.
**Al completar:** se valida la política, que la nueva contraseña no sea igual a la anterior (`password_verify($nueva, $hashAnterior) === false`), se actualiza el hash, se pone `cambiar_contrasenia = 0` y se regenera la sesión.
**Motivo:** CA-02.2 (password temporal) y CA-05.8.

---

### D-06 · Sesión: CI4 + tabla `sesion`

**Decisión:** usar la sesión nativa de CI4 para los datos de sesión y la tabla `sesion` para poder invalidar sesiones desde el servidor.
**Flujo:**
1. En login exitoso: generar `token = bin2hex(random_bytes(32))` (64 caracteres), insertar en `sesion` (`id_usuario`, `token`, `fecha_creacion`, `ultima_actividad`) y guardar `id_sesion` y `token` en la sesión CI4.
2. `AuthFilter` valida que exista la fila `sesion` con ese `id_sesion` y `token`; si no existe, destruye la sesión y redirige a `/auth/login`.
3. `AuthFilter` valida también que `usuario.estado = 'activo'` y que `cambiar_contrasenia = 0` (salvo en la ruta de cambio).
4. `AuthFilter` compara `ultima_actividad` con `now()`. Si supera 1800 segundos, invalida la fila y redirige al login.
5. Logout: eliminar la fila de `sesion`, destruir la sesión CI4 y borrar la cookie.
6. Bloqueo/baja de usuario: eliminar **todas** las filas de `sesion` de ese `id_usuario`.
**Config:** `Session::$expiration = 1800`, `regenerateDestroy = true`, `timeToUpdate = 300`.
**Motivo:** CA-02.2, CA-03.1, CA-05.7.

---

### D-07 · Fuerza bruta con `historico_login`

**Decisión:** no se agregan columnas `intentos_fallidos` ni `bloqueado_hasta`. Se usa `historico_login`.
**Regla:**
- En cada intento se inserta una fila con `email`, `ip`, `exitoso` (0 o 1) y `fecha`.
- Antes de validar credenciales, contar fallos (`exitoso = 0`) de los últimos 15 minutos para ese `email` **o** esa `ip`. Si cualquiera de los dos alcanza 5, responder “Demasiados intentos, intente en 15 minutos”.
- Un login exitoso inserta `exitoso = 1`; no borra los fallos anteriores.
- El bloqueo por fuerza bruta **no** cambia `usuario.estado`.
**Motivo:** HU-02 y el esquema provisto.

---

### D-08 · Auditoría de cambios de usuario

**Decisión:** en cada modificación, bloqueo, baja, reactivación o reseteo de contraseña hecho por un admin, insertar una fila en `modificacion_usuario` (`id_usuario`, `id_admin`).
**Motivo:** CA-05.6.

---

### D-09 · Dirección separada de la propiedad

**Decisión:** cada alta de `propiedad` crea primero una fila en `direccion` y luego la propiedad con `id_direccion`. En edición se actualiza la misma fila de `direccion`.
**Motivo:** el SQL separa `direccion` de `propiedad`.
**Nota:** no hay restricción única de dirección; la aplicación evita duplicados por flujo, no por esquema.

---

### D-10 · Tipo de inmueble como catálogo

**Decisión:** `tipo_inmueble` se siembra con los 5 valores del CA-10.2. El formulario usa un select; no se escribe texto libre.
**Valores:** `casa`, `departamento`, `local comercial`, `oficina`, `galpon`.
**Etiquetas:** Casa, Departamento, Local Comercial, Oficina, Galpón.

---

### D-11 · Estados de propiedad

**Decisión:** el ENUM de `propiedad.estado` incluye `inactivo`, pero el alta solo ofrece `ocupado`, `disponible` y `en mantenimiento`.
**Baja:** se hace por la acción `POST /inmuebles/{id}/baja`, que setea `inactivo`. No se elige “Inactivo” en el formulario de alta.
**Motivo:** CA-10.3 vs CA-11.2.

---

### D-12 · `tipo_servicio.tipo` es ENUM, no texto libre

**Decisión:** el campo que la HU-07 llama “nombre” se implementa como un **select** de `tipo_servicio.tipo` con los 9 valores del esquema. El texto libre se usa solo en `proveedor_predeterminado`.
**Valores:** `agua`, `gas`, `electricidad`, `internet`, `telefonia`, `television`, `impuesto`, `expensas`, `otro`.
**Etiquetas:** Agua, Gas, Electricidad, Internet, Telefonía, Televisión, Impuesto, Expensas, Otro.
**Consecuencia:** el ejemplo “Energía Eléctrica” de HU-07 se muestra como “Electricidad” y se guarda como `electricidad`.

---

### D-13 · Unicidad de `tipo_servicio`

**Decisión:** la unicidad real es `UNIQUE(tipo, proveedor_predeterminado)`, no `UNIQUE(nombre)`.
**Interpretación de CA-07.4:** “no puede existir dos veces la misma combinación de tipo + proveedor”. Sí puede haber `electricidad/Edenor` y `electricidad/Edesur`.
**Mensaje:** usar el texto de HU-07: “Ya existe un tipo de servicio con ese nombre”. Si se quiere precisar, “Ya existe un tipo de servicio con ese nombre y proveedor”.
**Pendiente:** si el equipo exige nombre libre único, hay que cambiar el esquema (no recomendado en este hito).

---

### D-14 · Unidad de medida y trigger

**Decisión:** el formulario filtra las unidades compatibles según el `tipo` seleccionado. El trigger `trg_tipo_servicio_unidad_bi/bu` es la última defensa; si se dispara, el servicio captura el error SQL y muestra “Unidad de medida inválida”.
**Unidades:** `kWh`, `m³`, `Gigas`, `Moneda fija`.
**Motivo:** CA-07.3 y trigger del esquema.

---

### D-15 · Estados del vínculo servicio-inmueble

**Decisión:** usar exactamente los valores del ENUM: `activo`, `pausado`, `en revision`, `inactivo` (con espacio y sin tilde en `revision`).
**Etiquetas:** Activo, Pausado, En Revisión, Inactivo.
**Transiciones permitidas:** `activo → pausado`, `pausado → en revision`, `en revision → activo`, y desde `activo`, `pausado` o `en revision` → `inactivo`.
**Unicidad vigente:** no puede haber dos vínculos del mismo `id_tipo_servicio` en la misma `id_propiedad` con estado `activo`, `pausado` o `en revision`. Un vínculo `inactivo` permite revincular.
**Motivo:** CA-13.4 y CA-13.7.

---

### D-16 · Factura: período y estado “Vencida”

**Período:** la UI puede seguir mostrando `MM/YYYY`. Al guardar:
- Mensual: `periodo = 'mes'`, `periodo_mes = 'MM'`, `periodo_anio = YYYY`.
- Anual (si se habilita): `periodo = 'anio'`, `periodo_mes = '00'`, `periodo_anio = YYYY`.

**Unicidad:** `(id_propiedad_servicio, periodo, periodo_anio, periodo_mes)`.

**Estado:** la BD solo guarda `pendiente` o `pagada`. **`Vencida` nunca se guarda**; se calcula:
```
vencimiento_aplicable = COALESCE(fecha_segundo_vencimiento, fecha_primer_vencimiento)
si estado = 'pagada' → Pagada
si estado = 'pendiente' y vencimiento_aplicable < hoy → Vencida
si estado = 'pendiente' y vencimiento_aplicable >= hoy → Pendiente
```
**Motivo:** CA-14.1, H-28 y el ENUM del esquema.

---

### D-17 · Snapshot de factura

**Decisión:** al crear la factura se copian a `factura` los valores vigentes de `tipo_servicio`: `proveedor`, `categoria_factura` y `unidad_medida`. La factura **no** se muestra leyendo el catálogo en vivo.
**Motivo:** CA-08.2, H-27.

---

### D-18 · Pagos: `transaccion` sin HU de origen

**Situación:** el esquema tiene `transaccion`, pero ninguna HU define el registro de pagos. HU-15 solo muestra totales.
**Decisión recomendada (pendiente de aprobación):** agregar una acción mínima `POST /facturas/{id}/pagar` que:
1. valide pertenencia y estado `pendiente`;
2. inserte una `transaccion` con `monto_abonado = factura.monto_total`, `medio_pago`, `numero_comprobante`, `fecha_pago` y `en_mora` calculado;
3. actualice `factura.estado = 'pagada'`.

**Si no se aprueba:** HU-15 solo podrá demostrar “Total Pagado” con datos sembrados (`DemoSeeder`) y el flujo real de pago queda fuera del Hito 1.
**Estado:** **PENDIENTE DE APROBACIÓN** (ver sección 8).

---

### D-19 · Accesos denegados: 403 real

**Decisión:** `RoleFilter` y los servicios de dominio devuelven HTTP 403 con la vista `app/Views/errors/html/error_403.php`. No se redirige.
**Contenido:** mensaje “No tiene permisos para esta acción” y botón “Volver al dashboard”.
**Motivo:** H-32 y regla global de las HU.

---

### D-20 · Control de propiedad

**Decisión:** todo servicio de dominio que reciba un `id` de propiedad, servicio o factura valida:
- si el usuario es `admin`, permite;
- si es `propietario`, exige que `propiedad.id_propietario = session('id_usuario')`;
- si no, devuelve 403.
**Motivo:** CA-10.6, CA-11.6, CA-13.3, CA-14.6, CA-15.5.

---

### D-21 · Bajas lógicas

| Entidad | Mecanismo |
|---|---|
| `usuario` | `estado = 'baja'` |
| `propiedad` | `estado = 'inactivo'` |
| `propiedad_servicio` | `estado = 'inactivo'` |
| `tipo_servicio` con uso | `activo = 0` |
| `tipo_servicio` sin uso | borrado físico permitido (HU-08) |
| `factura` | nunca se borra; se conserva |
| `transaccion` | nunca se borra; se conserva |

---

### D-22 · Email saliente

**Decisión:** `EmailService` usa la configuración de `app/Config/Email.php` y variables de entorno. En desarrollo se recomienda **Mailpit** en `localhost:1025` (SMTP sin autenticación). En tests se inyecta un doble que no envía.
**Config mínima:** `fromEmail`, `fromName`, `protocol = smtp`, `SMTPHost`, `SMTPPort`, `SMTPUser`, `SMTPPass`, `SMTPCrypto`, `mailType = html`.
**Template:** `app/Views/emails/bienvenida.php`.
**Motivo:** HU-01 exige email de bienvenida y `app/Config/Email.php` está vacío.

---

### D-23 · Base de datos de tests

**Decisión:** los tests usan MySQL/MariaDB, no SQLite.
**Base:** `serviinmueble_test`, creada aparte, con el mismo esquema que `serviinmueble`.
**Motivo:** el esquema usa `ENUM`, `CHECK`, `YEAR` y triggers que SQLite no reproduce.
**Config:** `app/Config/Database.php` → `$tests` con `MySQLi`; o variables en `phpunit.dist.xml`.

---

### D-24 · URLs y `indexPage`

**Decisión:** `app.baseURL` se toma de `.env` (`http://localhost/servi-inmueble/public/`). `app.indexPage = ''` para generar URLs limpias con el `.htaccess` de `public/`.
**Motivo:** evitar `index.php` en rutas y assets.

---

### D-25 · Zona horaria e idioma

**Decisión:** `appTimezone = 'America/Argentina/Buenos_Aires'`, `defaultLocale = 'es'`, `supportedLocales = ['es']`.
**Motivo:** fechas de vencimiento, formato de moneda y mensajes en español.

---

### D-26 · Fechas y montos

- Fechas de negocio: `DATE` (`Y-m-d`).
- Fechas de auditoría/sesión: `DATETIME` (`Y-m-d H:i:s`).
- Montos: `DECIMAL(12,2)`; mostrar con separador de miles y dos decimales.
- Superficie: `DECIMAL(10,2)`.
- Consumo: entero (`INT`).

---

### D-27 · Orden de implementación

El orden numérico de las HU no es válido por dependencias. Orden acordado:

```
00 (bootstrap) → 01 → 02 → 03 → 04 → 06 → 05 → 07 → 09 → 08 → 10 → 12 → 11 → 13 → 14 → 15
```

**Justificación:**
- HU-05 (modificación/baja) necesita el listado de HU-06.
- HU-08 (modificación/baja de tipos) necesita el listado de HU-09.
- HU-11 (modificación/baja de inmuebles) necesita el listado de HU-12.
- HU-10 depende de usuarios (HU-01 a HU-06) y del catálogo de servicios (HU-07 a HU-09).

---

### D-28 · Regla de cierre: base vs integración

**Problema:** “no cerrar la HU si queda un CA parcial” choca con H-25/H-26, que difieren detalles de HU-06/08/09/11/12 a la integración.
**Decisión:** distinguir dos cierres:
- **Cierre base:** todos los CA implementables con las HU ya construidas.
- **Cierre de integración:** los CA que dependen de módulos posteriores (por ejemplo, “cantidad de inmuebles vinculados” en HU-09 o “resumen de facturación” en HU-12).
**Regla:** una HU se considera cerrada cuando tiene cierre base completo y los CA diferidos están identificados y asignados a la HU de integración correspondiente. No se aceptan CA “parciales” sin dueño ni fecha.

---

### D-29 · H-24: `/dashboard` como ruta de referencia

**Decisión:** se usa `/dashboard` como ruta protegida genérica para el escenario de logout de HU-03 y como destino posterior al login.
**Motivo:** `/inmuebles` no existe hasta HU-10 y el bloque de acceso no puede depender de un módulo posterior.
**Estado:** aceptada.

---

### D-30 · Nombres de rutas y controladores

**Decisión:** rutas en español, kebab-case, sin prefijo `/api`; controladores en PascalCase; nombres de ruta con `as` para evitar strings mágicos.
**Ver:** `docs/rutas.md`.

---

## 7. Conflictos detectados y resolución

| # | Conflicto | Resolución |
|---|---|---|
| C-01 | Skills usan `roles`, `usuarios`, `inmuebles`, `servicios_inmueble`, `facturas` | Se usa el esquema SQL (D-01, sección 5) |
| C-02 | HU-07 pide “nombre” libre y único; SQL usa `tipo` ENUM + único `(tipo, proveedor)` | D-12 y D-13 |
| C-03 | HU-14 pide “período 10/2026”; SQL separa `periodo`, `periodo_mes`, `periodo_anio` | D-16 |
| C-04 | HU-14/15 hablan de “Vencida”; SQL solo tiene `pendiente`/`pagada` | D-16 (cálculo) |
| C-05 | HU-15 necesita “Total Pagado”; no hay HU de pagos | D-18 (pendiente) |
| C-06 | HU-13 usa “En Revisión”; SQL guarda `en revision` | D-15 |
| C-07 | HU-10 usa dirección inline; SQL tiene tabla `direccion` | D-09 |
| C-08 | HU-05/06 usan roles; SQL no tiene tabla `roles` | D-02 |
| C-09 | Skills usan `id_rol`; SQL usa `usuario.rol` | D-02 |
| C-10 | Tests en SQLite; esquema MySQL con ENUM/CHECK/trigger | D-23 |
| C-11 | Prompt dice “no inventes rutas”; no había mapa | `docs/rutas.md` |
| C-12 | “No cerrar con CA parcial” vs H-25/H-26 | D-28 |
| C-13 | `docs/features-hito1.txt`, `docs/enunciado.txt`, `sql/schema.sql` no existen | Se eliminan como referencias; la fuente es el SQL + docs |
| C-14 | `PROMPTS_POR_HISTORIA_DE_USUARIO_HITO1.txt` está desactualizado y mezcla idiomas | Se reemplaza por `docs/PROMPT_GENERAL.md` |
| C-15 | `HU-02_compacto.md` decía “cambiar sgit u contraseña” | Corregido a “cambiar su contraseña” |

---

## 8. Decisiones pendientes de aprobación

| ID | Tema | Opciones | Recomendación | Dueño |
|---|---|---|---|---|
| P-01 | Registro de pagos (D-18) | (a) acción mínima `POST /facturas/{id}/pagar`; (b) solo datos sembrados; (c) nueva HU-16 | (a) para que HU-15 sea demostrable | Equipo / docente |
| P-02 | Nombre libre de tipo de servicio (D-12/D-13) | (a) mantener ENUM + proveedor; (b) cambiar esquema a `nombre` libre único | (a) por costo y por esquema canónico | Equipo |
| P-03 | Período anual de factura | (a) solo mensual en Hito 1; (b) habilitar toggle mes/año | (a) si no hay CA que lo exija; (b) si se quiere usar el ENUM completo | Equipo |
| P-05 | Alcance de “Total Pagado” con pagos parciales | (a) pago total solamente; (b) sumar `transaccion.monto_abonado` | (a) para el Hito 1 | Equipo |

**P-02 resuelta (2026-11-08):** se aprobó mantener `tipo_servicio.tipo` como ENUM y aplicar la unicidad sobre `(tipo, proveedor_predeterminado)`, sin modificar el esquema canónico.

**P-04 resuelta:** se aprobó mostrar la contraseña temporal una sola vez en la salida de consola al crear el administrador inicial; no se envía por email. Las ejecuciones posteriores del seeder no vuelven a mostrarla.

---

## 9. Impacto en skills y prompts

- `agents/servi-inmueble-ci4/SKILL.md` → **sección 6 (Database Design) obsoleta**. Reemplazar por la sección 4 de este documento.
- `agents/ci4-migration-builder/SKILL.md` → las migraciones deben reproducir el SQL canónico, no el esquema de los skills.
- `agents/ci4-routes-guard/SKILL.md` → el ejemplo de rutas es orientativo; la fuente es `docs/rutas.md`.
- `agents/ci4-hu-validator/SKILL.md` → no debe exigir `docs/features-hito1.txt`; usar `HU-XX_compacto.md` + `HU_Hito1_compacto.txt`.
- `agents/ci4-security-audit/SKILL.md` → el RBAC se valida contra `usuario.rol`, no `id_rol`.
- `agents/ci4-test-suite/SKILL.md` → tests contra MySQL, no SQLite.
- `docs/historias_de_usuario/PROMPTS_HU_HITO1_TRAZABILIDAD.md` → su “PROMPT GENERAL” queda reemplazado por `docs/PROMPT_GENERAL.md`.

---

## 10. Checklist de coherencia

Antes de dar por cerrada cualquier HU:

- [ ] No se usan tablas ni columnas fuera del SQL canónico.
- [ ] No se usa `roles`, `id_rol`, `usuarios`, `inmuebles`, `servicios_inmueble` ni `facturas`.
- [ ] Los estados se guardan con los valores exactos del ENUM.
- [ ] `Vencida` se calcula, no se guarda.
- [ ] Los snapshots de factura se copian al crear.
- [ ] El control de propiedad se valida en el servicio.
- [ ] Los accesos denegados devuelven 403 con vista amigable.
- [ ] CSRF activo en todos los POST.
- [ ] La sesión se invalida en logout, bloqueo y baja.
- [ ] Los tests corren contra MySQL.
- [ ] Las rutas usadas existen en `docs/rutas.md`.
