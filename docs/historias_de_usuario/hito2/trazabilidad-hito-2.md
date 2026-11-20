# Trazabilidad · Hito 2 (HU-16 a HU-22) · ServiInmueble 2026

**Proyecto:** ServiInmueble 2026 (CodeIgniter 4.7.5 · PHP 8.3 · MariaDB 10.11 · AdminLTE 3)  
**Fecha:** 2026-11-20  
**Estado del Hito 2:** definición cerrada, decisiones aprobadas, **sin implementación**.  
**Alcance:** HU-16 a HU-22.  
**Uso:** este documento es la matriz maestro. Los 8 prompts (`docs/prompts/prompt-general-hito-2.md` y `prompt-hu-16.md` … `prompt-hu-22.md`) deben respetar literalmente los nombres, rutas, mensajes y decisiones definidos acá.

---

## 1. Fuentes de verdad y orden de precedencia

1. `bd/serviinmueble (1).sql` — esquema canónico de Hito 1. **No se modifica.**
2. `docs/historias_de_usuario/hito2/README.md` — decisiones aprobadas D2-01 a D2-13, impacto de esquema, rutas previstas y fuera de alcance.
3. `docs/historias_de_usuario/hito2/HU_Hito2_compacto.md` — consolidado y notas H2-01 a H2-07.
4. `docs/historias_de_usuario/hito2/HU-16_compacto.md` … `HU-22_compacto.md` — criterios de aceptación literales.
5. `docs/decisiones.md` (D-01 a D-30) y `docs/rutas.md` — convenciones de Hito 1.
6. `docs/reportes/resultado_por_prompt.md` — estado real de Hito 1 al 2026-11-20.
7. `docs/reportes/verificacion_solo_lectura_hu16-hu22_vs_hito1.md` y `docs/reportes/verificacion_solo_lectura_A1-F4_hu16-hu22.md` — verificación de solo lectura.
8. `agents/*/SKILL.md` — convenciones de código; **no** pueden contradecir el SQL ni este documento.

**Regla:** si un skill usa `roles`, `usuarios`, `inmuebles`, `servicios_inmueble`, `facturas`, `id_rol`, `password_hash` como columna, `created_at`, `updated_at` o `deleted_at`, se ignora esa parte del skill (D-01 y mapa de la sección 5 de `docs/decisiones.md`).

---

## 2. Estado real del proyecto al iniciar el Hito 2

### 2.1 Estado canónico (código leído)

| Área | Estado | Evidencia |
|---|---|---|
| Hito 1 (HU-00 a HU-15) | Implementado | `docs/reportes/resultado_por_prompt.md`: HU-08/09/12 integradas; suite `124 tests, 773 assertions; OK`. |
| Throttle de login | Corregido | `app/Services/LoginThrottleService.php` tiene `VENTANA_SEGUNDOS = 900`; `AuthService` usa “Demasiados intentos, intente en 15 minutos”. `LoginThrottleServiceTest` pasa (1 test, 7 aserciones). |
| Zona horaria | OK | `app/Config/App.php` → `America/Argentina/Buenos_Aires`. |
| Límites PHP de carga | OK | `upload_max_filesize=40M`, `post_max_size=40M`; el límite de aplicación de comprobantes es 5 MB (CA-17.6). |
| `max_allowed_packet` | Sin confirmar | `my.ini` registra `1M` y `16M` en secciones distintas; requiere `SHOW VARIABLES LIKE 'max_allowed_packet';` con BD activa. |
| BD / esquema efectivo | No verificable en este entorno | `ERROR 2002 (HY000): Can't connect to server on '127.0.0.1' (10061)`. A3–A5, B1–B2 siguen pendientes de re-verificación. |
| Rutas Hito 2 | No existen | `app/Config/Routes.php` solo contiene rutas de Hito 1; no hay `GET /facturas/(:num)` ni rutas de pagos, mora, notificaciones o informes. |
| Esquema Hito 2 | No implementado | `transaccion` sin campos de validación; no existe migración `notificacion`; no hay índices nuevos en `factura`. |
| UI y assets | No implementado | `app/Views/layout/header.php` sin campanita; `public/assets/plugins/` sin `chart.js/`. |
| Documentación Hito 2 | Definida, no versionada en documentos base | `docs/decisiones.md` no contiene D2-01…D2-13; `docs/rutas.md` no contiene las rutas del Hito 2. H2-07 exige agregarlas antes de codificar. |
| Working tree | Con cambios sin commitear | Revisar y separar WIP antes de tocar archivos de Hito 2 (V-12). |

### 2.2 Alineación con el documento de verificación

| Punto de verificación | Lo que decía el reporte | Estado que rige para el Hito 2 |
|---|---|---|
| V-01 (throttle 10 s vs 15 min) | DESVÍO | **Cerrado en código**: ventana 900 s y mensajes alineados. No reintroducir el valor 10. |
| V-03 / V-04 / V-05 (HU-08/09/12) | PENDIENTE con placeholders | **Cerrado e integrado** según `resultado_por_prompt.md`. No reabrir ni duplicar esos trabajos. |
| V-02 (`max_allowed_packet`) | Desvío / no verificable | Sigue **no verificable**. Antes de migrar/probar comprobantes grandes, ejecutar `SHOW VARIABLES LIKE 'max_allowed_packet';` y dejar registro. |
| V-06 / V-07 (D2 y rutas en docs base) | DESVÍO | Sigue pendiente: este paquete entrega 9 documentos nuevos; **no** modifica `docs/decisiones.md` ni `docs/rutas.md`. El implementador debe registrar allí D2-01…D2-13 y las 15 rutas antes de escribir código (H2-07). |
| V-08 (HU y consolidado Hito 2) | “no localizados” en la verificación original | **Resuelto**: `docs/historias_de_usuario/hito2/` contiene README, consolidado y HU-16…HU-22. Esa carpeta es la fuente funcional. |
| V-09 (esquema Hito 2) | No implementado | Vigente. Diseñar y aplicar las tres migraciones de la sección 7 antes de codificar servicios. |
| V-10 (Chart.js y campanita) | No implementado | Vigente. Vendorizar Chart.js y agregar campanita según D2-06 y HU-20. |
| V-11 (BD/tests no verificables) | Bloqueante de entorno | Vigente para A3–A5/B1–B2. F4 tiene resultado histórico de suite OK, pero toda nueva prueba Hito 2 requiere `serviinmueble_test` activo. |
| V-12 (working tree sucio) | Riesgo | Vigente: sanear antes de aplicar migraciones. |

---

## 3. Decisiones aplicables

### 3.1 Decisiones aprobadas del Hito 2 (D2-01 … D2-13)

| ID | Decisión | HU que la aplica |
|---|---|---|
| D2-01 | Todo pago requiere validación del Administrador; mientras tanto queda “pendiente de validación” y la factura no cambia de estado. | HU-17, HU-18, HU-16 |
| D2-02 | `monto_abonado` debe ser igual a `monto_total`; sin pagos parciales. | HU-17 |
| D2-03 | Notificaciones internas; no se envían emails ni mensajes externos. | HU-20, HU-18 |
| D2-04 | Promedio agrupado por unidad de medida: kWh/m³/Gigas por consumo; Moneda fija por monto. | HU-22, HU-21 |
| D2-05 | Admin ve todo; Propietario solo lo propio; el alcance se filtra en servidor. | HU-16, HU-17, HU-18, HU-19, HU-20, HU-21, HU-22 |
| D2-06 | Chart.js se vendoriza en `public/assets/plugins/chart.js/`, sin CDN. | HU-16, HU-21, HU-22 |
| D2-07 | Comprobante obligatorio salvo efectivo; si se adjunta, PDF/JPG/JPEG ≤ 5 MB validado por contenido. | HU-17, HU-18 |
| D2-08 | Ventana “por vencer” de 7 días corridos por defecto, configurable en la aplicación. | HU-20 |
| D2-09 | El propietario recibe `pago_validado`, `pago_rechazado` (con motivo), `por_vencer` y `vencida`. | HU-18, HU-20 |
| D2-10 | Admin ve el listado global de notificaciones en lectura; sin campanita ni avisos propios por factura. | HU-20 |
| D2-11 | `numero_comprobante` único por factura (`id_factura`, `numero_comprobante`). | HU-17 |
| D2-12 | Informes mensuales usan solo `periodo = 'mes'`; las anuales van en resumen aparte. | HU-21, HU-22 |
| D2-13 | Recaudación = suma de `monto_abonado` de pagos validados por `fecha_pago`. | HU-16 |

### 3.2 Notas de trazabilidad H2-01 … H2-07

| Nota | Contenido | Aplica a |
|---|---|---|
| H2-01 | Estado intermedio en `transaccion`; la factura solo pasa a `pagada` al validar. | HU-17, HU-18, HU-16 |
| H2-02 | `en_mora` guarda pagos tardíos históricos; la mora de facturas pendientes se calcula por vencimiento aplicable. | HU-17, HU-19, HU-16 |
| H2-03 | Las notificaciones de pago se disparan en HU-18 y se entregan por HU-20; no modifican factura ni pago. | HU-18, HU-20 |
| H2-04 | Alcance por rol transversal, validado en servidor. | Todas |
| H2-05 | Chart.js local; la elección no altera los CA de gráficos. | HU-16, HU-21, HU-22 |
| H2-06 | Fuentes funcionales en `docs/historias_de_usuario/hito2/`. | Todas |
| H2-07 | Antes de codificar: registrar D2 en `docs/decisiones.md`, rutas en `docs/rutas.md` y diseñar migraciones. | Todas |

### 3.3 Decisiones de implementación (DI-01 … DI-09)

Son detalles técnicos que desarrollan las decisiones aprobadas; **no** son nuevas decisiones de producto y no modifican D2-01…D2-13.

| ID | Decisión de implementación |
|---|---|
| DI-01 | Idempotencia de notificaciones: columna derivada `idempotencia_key` VARCHAR(120) NOT NULL con `UNIQUE (id_usuario, idempotencia_key)`. Claves: `venc:{id_factura}:{tipo}` (sin transacción) y `pago:{id_transaccion}:{tipo}` (con transacción). Se conservan además índices simples (`id_usuario`,`id_factura`,`tipo`) e (`id_usuario`,`id_transaccion`,`tipo`). Así se evita el problema de NULL duplicables en índices únicos de MariaDB. |
| DI-02 | Comprobante: BLOB en `transaccion.comprobante_digital`, metadatos en `comprobante_nombre`/`comprobante_mime`; MIME detectado por contenido con `finfo` (`application/pdf`, `image/jpeg`); límite de aplicación 5 MiB (`5 * 1024 * 1024`); descarga con `Content-Type` guardado, `Content-Disposition: inline`, `X-Content-Type-Options: nosniff` y sin ejecución. |
| DI-03 | Ventana configurable: `app/Config/Hito2.php` con `public int $diasPorVencer = 7;`. El servicio la lee con `config('Hito2')->diasPorVencer`. |
| DI-04 | Backfill de la migración de `transaccion`: las filas existentes pasan a `estado = 'validada'` y `fecha_validacion = CONCAT(fecha_pago, ' 00:00:00')` porque en Hito 1 todo pago se consideraba confirmado. `DemoSeeder` debe insertar `estado` explícitamente. |
| DI-05 | Igualdad de montos: normalizar ambos valores con `number_format((float) $valor, 2, '.', '')` antes de comparar. |
| DI-06 | Chart.js v4.4.7 local: `public/assets/plugins/chart.js/chart.umd.min.js`, cargado en `app/Views/layout/main.php` antes de `renderSection('scripts')`. Los datos se pasan como JSON desde la vista, sin CDN. |
| DI-07 | Estado calculado: reutilizar `FacturaService::estadoCalculado()` y la expresión `COALESCE(fecha_segundo_vencimiento, fecha_primer_vencimiento)` de `FacturaRepository`; “vencida” nunca se persiste (D-16). |
| DI-08 | Autorización en servicio: Admin = alcance global; Propietario = `propiedad.id_propietario = session('id_usuario')`. Admin solo lectura en notificaciones globales; no marca como leídas las de terceros. |
| DI-09 | Informes: filtro `periodo = 'mes'` en gráficos y tablas; `periodo = 'anio'` solo en el resumen aparte. |

### 3.4 Reglas vigentes de Hito 1 que no se negocian

- `usuario.rol` ENUM(`propietario`,`admin`); no existe tabla `roles`.
- `usuario.estado` ENUM(`activo`,`bloqueado`,`baja`); bajas lógicas (excepto `tipo_servicio` sin uso).
- `factura.estado` ENUM(`pendiente`,`pagada`); “vencida” es calculada (D-16).
- `transaccion.medio_pago` exacto: `transferencia`, `debito automatico`, `efectivo`, `billetera virtual` (sin tilde en `debito automatico`). La UI puede rotularlo “débito automático”, pero el valor guardado y validado no lleva tilde (CA-17.5 usa el texto con tilde solo como etiqueta).
- Contraseña ≥ 8 con letra y número; email único case-insensitive; sesión 30 min; CSRF global en POST; PRG en mutaciones.
- 403 real con `app/Views/errors/html/error_403.php` y botón “Volver al dashboard”; 404 con `app/Views/errors/html/error_404.php`; nunca redirect silencioso por permisos.
- Arquitectura Controller → Service → Repository → Model/Entity; sin lógica de negocio en controladores ni modelos.
- Tests con MySQL/MariaDB sobre `serviinmueble_test`, nunca SQLite (D-23).
- `P-01` (Hito 1: sin acción de pago en HU-15) queda **superado solo en el Hito 2** por HU-17; `P-05` (sin pagos parciales) se mantiene y refuerza con D2-02.

---

## 4. Mapa canónico de rutas del Hito 2

Estas 15 rutas son las únicas rutas nuevas/ampliadas del Hito 2. Deben agregarse a `docs/rutas.md` antes de codificar (H2-07) y a `app/Config/Routes.php` con `autoRoute = false`.

| Método | URI | Nombre (`as`) | Controlador::método | Filtros | HU |
|---|---|---|---|---|---|
| GET | `/dashboard` | `dashboard` | `DashboardController::index` (ampliar) | `auth` | HU-16 |
| GET | `/facturas/(:num)/pagar` | `pagos.create` | `PagosController::create/$1` | `auth` | HU-17 |
| POST | `/facturas/(:num)/pagos` | `pagos.store` | `PagosController::store/$1` | `auth` | HU-17 |
| GET | `/pagos` | `pagos.index` | `PagosController::index` | `auth` | HU-17 |
| GET | `/pagos/(:num)/comprobante` | `pagos.comprobante` | `PagosController::comprobante/$1` | `auth` | HU-17/18 |
| GET | `/admin/pagos` | `admin.pagos.index` | `Admin\PagosController::index` | `auth`, `role:admin` | HU-18 |
| GET | `/admin/pagos/(:num)` | `admin.pagos.show` | `Admin\PagosController::show/$1` | `auth`, `role:admin` | HU-18 |
| POST | `/admin/pagos/(:num)/validar` | `admin.pagos.validar` | `Admin\PagosController::validar/$1` | `auth`, `role:admin` | HU-18 |
| POST | `/admin/pagos/(:num)/rechazar` | `admin.pagos.rechazar` | `Admin\PagosController::rechazar/$1` | `auth`, `role:admin` | HU-18 |
| GET | `/mora` | `mora.index` | `MoraController::index` | `auth` | HU-19 |
| GET | `/notificaciones` | `notificaciones.index` | `NotificacionesController::index` | `auth` | HU-20 |
| POST | `/notificaciones/(:num)/leer` | `notificaciones.leer` | `NotificacionesController::marcarLeida/$1` | `auth` | HU-20 |
| POST | `/notificaciones/leer-todas` | `notificaciones.leer-todas` | `NotificacionesController::marcarTodasLeidas` | `auth` | HU-20 |
| GET | `/informes/consumo-interanual` | `informes.consumo-interanual` | `InformesController::consumoInteranual` | `auth` | HU-21 |
| GET | `/informes/promedio-unidad` | `informes.promedio-unidad` | `InformesController::promedioUnidad` | `auth` | HU-22 |

**Orden en `Routes.php`:** declarar `notificaciones/leer-todas` antes de `notificaciones/(:num)/leer`; declarar las rutas estáticas (`/pagos`, `/mora`, `/notificaciones`, `/informes/*`) antes de las rutas con `(:num)`; mantener el grupo `admin` con `['auth','role:admin']`.

**Parámetros de query string:**

| Ruta | Parámetros |
|---|---|
| `/pagos` | `estado`, `desde`, `hasta`, `page` |
| `/admin/pagos` | `estado`, `propietario`, `factura`, `medio_pago`, `desde`, `hasta`, `page` |
| `/mora` | `propietario` (solo Admin), `inmueble`, `desde`, `hasta`, `dias_min`, `dias_max`, `page` |
| `/notificaciones` | `tipo`, `leida`, `page` |
| `/informes/consumo-interanual` | `inmueble` (obligatorio), `servicio`, `anio_base`, `anio_comparado`, `unidad` |
| `/informes/promedio-unidad` | `desde`, `hasta`, `inmueble`, `servicio`, `unidad` |

Los parámetros inválidos se ignoran en servidor; nunca se rompe la vista ni se amplía el alcance por parámetro manipulado.

---

## 5. Matriz maestro de trazabilidad

**RNF compartidos:** RNF-01 alcance por rol en servidor · RNF-02 403/404 · RNF-03 CSRF+PRG · RNF-04 formato ARS/fechas/porcentajes · RNF-05 historial sin borrado · RNF-06 transaccionalidad · RNF-07 idempotencia · RNF-08 Chart.js local · RNF-09 manejo seguro de comprobantes · RNF-10 tests MySQL · RNF-11 timezone · RNF-12 sin emails · RNF-13 período mensual/anual.

| HU | Requisitos funcionales | RNF | Componentes | Endpoints / servicios | Modelos de datos | Depende de |
|---|---|---|---|---|---|---|
| HU-16 | CA-16.1…16.8 | 01,03,04,08,11,13 | `DashboardController`, `DashboardService`, `DashboardRepository`, vista dashboard, layout | `GET /dashboard`; `DashboardService::metricas()` | `usuario`, `propiedad`, `factura`, `transaccion`, `notificacion` | HU-17, HU-18, HU-19, HU-20 (métricas); Hito 1 |
| HU-17 | CA-17.1…17.10 | 01,02,03,04,05,09,10,11 | `PagosController`, `PagoService`, `ComprobanteService`, `PagoRepository`, `TransaccionModel`, vistas pagos | `GET /facturas/(:num)/pagar`, `POST /facturas/(:num)/pagos`, `GET /pagos`, `GET /pagos/(:num)/comprobante`; `PagoService` | `transaccion` ampliada, `factura` | Hito 1 (HU-14/15), migración H2 |
| HU-18 | CA-18.1…18.10 | 01,02,03,05,06,09,10,12 | `Admin\PagosController`, `ValidacionPagoService`, `PagoRepository`, `NotificacionService`, vistas admin pagos | 4 rutas `/admin/pagos*`; `ValidacionPagoService::validar()/rechazar()` | `transaccion`, `factura`, `notificacion` | HU-17, HU-20 |
| HU-19 | CA-19.1…19.8 | 01,02,04,10,11,13 | `MoraController`, `MoraService`, `MoraRepository`, vista mora | `GET /mora`; `MoraService::vencimientoAplicable()/diasMora()` | `factura`, `transaccion`, `propiedad`, `propiedad_servicio` | Hito 1, HU-17 (`en_mora`), D-16, migración 000003; alimenta las tarjetas de HU-16 |
| HU-20 | CA-20.1…20.11 | 01,02,03,07,10,11,12 | `NotificacionesController`, `NotificacionService`, `NotificacionRepository`, `NotificacionModel`, header, vistas notificaciones | 3 rutas `/notificaciones*`; `NotificacionService::registrarVencimientos()/registrarPagoValidado()/registrarPagoRechazado()` | `notificacion`, `factura`, `transaccion`, `usuario` | Hito 1; HU-17 (tabla `transaccion`); HU-18 consume el servicio |
| HU-21 | CA-21.1…21.9 | 01,02,04,08,10,11,12,13 | `InformesController::consumoInteranual`, `InformeService`, `InformeRepository`, vista informe | `GET /informes/consumo-interanual`; `InformeService::consumoInteranual()` | `factura`, `propiedad`, `propiedad_servicio`, `tipo_servicio` | Hito 1 (HU-14/15), D2-06 |
| HU-22 | CA-22.1…22.8 | 01,02,04,08,10,11,12,13 | `InformesController::promedioUnidad`, `InformeService`, `InformeRepository`, vista informe | `GET /informes/promedio-unidad`; `InformeService::promedioPorUnidad()` | `factura`, `propiedad`, `propiedad_servicio` | Hito 1 (HU-14/15), D2-06 |

---

## 6. Detalle por historia de usuario

### HU-16 · Dashboard de métricas globales

- **RF:** CA-16.1 (métricas Admin), CA-16.2 (métricas Propietario + no leídas), CA-16.3 (12 meses: emitido vs cobrado), CA-16.4 (distribución de estado calculado del año), CA-16.5 (filtrado en servidor), CA-16.6 (tarjetas enlazadas), CA-16.7 (estado vacío), CA-16.8 (formatos).
- **RNF:** RNF-01, RNF-03, RNF-04, RNF-08, RNF-11, RNF-13.
- **Servicio:** `App\Services\DashboardService::metricas(int $idUsuario, string $rol): array` devuelve `tarjetas`, `serie_mensual`, `distribucion_estados`, `enlaces`.
- **Repositorio:** `App\Repositories\DashboardRepository` con consultas agregadas; el Propietario filtra por `propiedad.id_propietario`.
- **Cálculos:** usuarios activos total/admin/propietario; inmuebles con `estado <> 'inactivo'`; pendientes/vencidas por estado calculado DI-07; pagos pendientes `transaccion.estado = 'pendiente'`; recaudación `SUM(monto_abonado)` con `estado='validada'` y `fecha_pago` del mes en curso (D2-13); emitido `SUM(monto_total)` por `fecha_emision`; cobrado `SUM(monto_abonado)` validadas por `fecha_pago`.
- **Enlaces de tarjetas (CA-16.6):** pendientes → `/facturas?estado=pendiente`; vencidas → `/facturas?estado=vencida`; pagos pendientes → `/pagos?estado=pendiente` (Admin además `/admin/pagos?estado=pendiente`); mora → `/mora`; notificaciones → `/notificaciones?leida=0`.
- **Archivos existentes a modificar:** `app/Config/Routes.php` (sin cambios de URI, solo verificación), `app/Controllers/DashboardController.php`, `app/Views/dashboard/index.php`, `app/Views/layout/main.php`, `app/Views/layout/sidebar.php`, `app/Helpers/format_helper.php` (agregar `formato_porcentaje(): string`, 1 decimal), `docs/rutas.md`, `docs/decisiones.md`.
- **Archivos nuevos:** `app/Services/DashboardService.php`, `app/Repositories/DashboardRepository.php`, `tests/feature/DashboardMetricasTest.php`.
- **Verificación:** `tests/feature/DashboardMetricasTest.php` con Admin vs Propietario, manipulación de query string, series 12 meses, distribución, sin datos y formato; `spark routes`.

### HU-17 · Registro de pago con comprobante digital

- **RF:** CA-17.1…CA-17.10.
- **RNF:** RNF-01, RNF-02, RNF-03, RNF-04, RNF-05, RNF-09, RNF-10, RNF-11.
- **Servicios:** `PagoService::opcionesPago()`, `registrar()`, `listadoPropio()`, `comprobante()`; `ComprobanteService::validar()`; `MoraService::vencimientoAplicable()` compartido con HU-19.
- **Repositorio:** `PagoRepository` con `buscarFacturaParaPago($id, $bloquear = false)`, `existePendiente($idFactura)`, `existeNumeroComprobante($idFactura, $numero)`, `crear(array $datos)`, `listarPropio(...)`, `buscarComprobante($id)`.
- **Reglas:** `estado = 'pendiente'` al crear; `factura.estado` no cambia (H2-01); `en_mora` calculado al registrar (H2-02); unicidad D2-11; comprobante D2-07/DI-02; `id_propietario` se toma de `propiedad.id_propietario`, nunca del POST.
- **Archivos existentes a modificar:** `app/Config/Routes.php`, `app/Models/TransaccionModel.php` (`allowedFields`), `app/Database/Seeds/DemoSeeder.php` (`estado` explícito DI-04), `app/Views/layout/sidebar.php`, `app/Helpers/format_helper.php` (si hacen falta etiquetas de estado de pago), `docs/rutas.md`, `docs/decisiones.md`.
- **Archivos nuevos:** migración `2026-11-20-000001_AlterTransaccionHito2.php`, `app/Services/PagoService.php`, `app/Services/ComprobanteService.php`, `app/Services/MoraService.php`, `app/Repositories/PagoRepository.php`, `app/Controllers/PagosController.php`, `app/Views/pagos/create.php`, `app/Views/pagos/index.php`, `tests/database/Hito2SchemaTest.php`, `tests/unit/Services/ComprobanteServiceTest.php`, `tests/feature/PagosTest.php`.
- **Verificación:** `PagosTest` cubre CA-17.1 a CA-17.10; `ComprobanteServiceTest` valida MIME por contenido y límite; `Hito2SchemaTest` verifica columnas, índice único y backfill DI-04.

### HU-18 · Validación de pagos

- **RF:** CA-18.1…CA-18.10.
- **RNF:** RNF-01, RNF-02, RNF-03, RNF-05, RNF-06, RNF-09, RNF-10, RNF-12.
- **Servicio:** `App\Services\ValidacionPagoService::listado()`, `detalle()`, `validar(int $idTransaccion, int $idAdmin): array`, `rechazar(int $idTransaccion, int $idAdmin, string $motivo): array`.
- **Transacción (CA-18.4):** `transBegin()` → bloquear pago con `FOR UPDATE` → rechazar si `estado <> 'pendiente'` → actualizar `estado='validada'`, `id_admin_validador`, `fecha_validacion` → `factura.estado='pagada'` → notificación `pago_validado` → `transCommit()`. Cualquier fallo hace `transRollback()`.
- **Rechazo (CA-18.5):** motivo 1..255 caracteres; `estado='rechazada'`; factura intacta; notificación `pago_rechazado` con motivo; el pago rechazado se conserva.
- **Inmutabilidad (CA-18.7):** ninguna ruta edita/borra pagos validados; los rechazados tampoco se eliminan.
- **Archivos existentes a modificar:** `app/Config/Routes.php`, `app/Views/layout/sidebar.php`, `docs/rutas.md`, `docs/decisiones.md`.
- **Archivos nuevos:** `app/Services/ValidacionPagoService.php`, `app/Controllers/Admin/PagosController.php`, `app/Views/admin/pagos/index.php`, `app/Views/admin/pagos/show.php`, `tests/feature/AdminPagosTest.php`.
- **Verificación:** `AdminPagosTest` cubre filtros, detalle, comprobante con MIME, doble validación, motivo vacío, 403 de Propietario, inmutabilidad y notificaciones generadas; suite completa.

### HU-19 · Control de mora

- **RF:** CA-19.1…CA-19.8.
- **RNF:** RNF-01, RNF-02, RNF-04, RNF-10, RNF-11, RNF-13.
- **Servicios:** `MoraService::vencimientoAplicable(array $factura): string` = `fecha_segundo_vencimiento ?: fecha_primer_vencimiento`; `diasMora(string $vencimiento, ?string $hoy = null): int` = `max(0, días entre hoy y vencimiento)`; `listado(array $filtros, int $idUsuario, string $rol): array`.
- **Repositorio:** `MoraRepository` con filtros por propietario (Admin), inmueble, rango de vencimiento y rango de días; orden por días de mora descendente.
- **Archivos existentes a modificar:** `app/Config/Routes.php`, `app/Views/layout/sidebar.php`, `app/Views/dashboard/index.php` (solo enlaces de tarjeta, la lógica va en HU-16), `docs/rutas.md`, `docs/decisiones.md`.
- **Archivos nuevos:** `app/Repositories/MoraRepository.php`, `app/Controllers/MoraController.php`, `app/Views/mora/index.php`, `tests/feature/MoraTest.php`.
- **Verificación:** `MoraTest` cubre universo vencida=calculada, `en_mora` histórico, orden, filtros, 403 por inmueble ajeno, sin intereses y propagación a dashboard.

### HU-20 · Notificaciones internas

- **RF:** CA-20.1…CA-20.11.
- **RNF:** RNF-01, RNF-02, RNF-03, RNF-07, RNF-10, RNF-11, RNF-12.
- **Servicio:** `NotificacionService::registrarVencimientos(?string $hoy = null): int`, `registrarPagoValidado(array $transaccion, array $factura): void`, `registrarPagoRechazado(array $transaccion, array $factura, string $motivo): void`, `listado(array $filtros, int $idUsuario, string $rol): array`, `cantidadNoLeidas(int $idUsuario): int`, `marcarLeida(int $idNotificacion, int $idUsuario, string $rol): array`, `marcarTodasLeidas(int $idUsuario): int`.
- **Repositorio/modelo:** `NotificacionRepository` + `app/Models/NotificacionModel.php` (tabla `notificacion`, `useTimestamps = false`).
- **Generación:** `por_vencer` si vencimiento aplicable ∈ [hoy, hoy + `config('Hito2')->diasPorVencer`] inclusive; `vencida` si < hoy; ambas sobre `factura.estado='pendiente'`; disparo al ingresar `/dashboard` o `/facturas`, sin cron (CA-20.10). Pagos se disparan desde HU-18 (H2-03).
- **Destinatario:** `propiedad.id_propietario`. Admin: listado global en lectura, sin campanita ni conteo.
- **Archivos existentes a modificar:** `app/Config/Routes.php`, `app/Controllers/DashboardController.php` y `app/Controllers/FacturasController.php` (llamada idempotente a `registrarVencimientos()`), `app/Views/layout/header.php` (campanita + contador, solo Propietario), `app/Views/layout/sidebar.php`, `docs/rutas.md`, `docs/decisiones.md`.
- **Archivos nuevos:** `app/Config/Hito2.php`, migración `2026-11-20-000002_CreateNotificacion.php`, `app/Models/NotificacionModel.php`, `app/Services/NotificacionService.php`, `app/Repositories/NotificacionRepository.php`, `app/Controllers/NotificacionesController.php`, `app/Views/notificaciones/index.php`, `tests/unit/Services/NotificacionServiceTest.php`, `tests/feature/NotificacionesTest.php`.
- **Verificación:** `NotificacionServiceTest` cubre los cuatro tipos e idempotencia; `NotificacionesTest` cubre campanita, orden, paginación, marcar una/todas, 403/404, estado vacío, rol Admin y ausencia de email.

### HU-21 · Informe de consumo interanual

- **RF:** CA-21.1…CA-21.9.
- **RNF:** RNF-01, RNF-02, RNF-04, RNF-08, RNF-10, RNF-11, RNF-12, RNF-13.
- **Servicio:** `InformeService::consumoInteranual(array $filtros, int $idUsuario, string $rol): array` → `{serie: array<int, array>, tabla: list<array>, totales: array, resumen_anual: list<array>, sin_datos: bool}`.
- **Repositorio:** `InformeRepository::consumoInteranual()` agrupa por `periodo_anio`, `periodo_mes` sobre `periodo = 'mes'`, `id_propiedad_servicio` del inmueble/servicio elegido.
- **Reglas:** 12 puntos por año con 0 en meses sin factura; diferencia = base − comparado; variación = `(diferencia / base) * 100` con base 0 → “—”; unidad “Moneda fija” usa `monto_total` y etiqueta “Monto” (D2-04).
- **Archivos existentes a modificar:** `app/Config/Routes.php`, `app/Views/layout/main.php` (carga Chart.js DI-06), `app/Views/layout/sidebar.php`, `docs/rutas.md`, `docs/decisiones.md`.
- **Archivos nuevos:** `app/Services/InformeService.php`, `app/Repositories/InformeRepository.php`, `app/Controllers/InformesController.php` (método `consumoInteranual`), `app/Views/informes/consumo-interanual.php`, `tests/feature/InformesConsumoInteranualTest.php`. Los dos primeros se reutilizan en HU-22.
- **Verificación:** `InformesConsumoInteranualTest` cubre acceso, filtros, 12 puntos, meses 0, totales, resumen anual, Moneda fija, división por cero, sin datos y 403.

### HU-22 · Promedio de consumo por unidad de medida

- **RF:** CA-22.1…CA-22.8.
- **RNF:** RNF-01, RNF-02, RNF-04, RNF-08, RNF-10, RNF-11, RNF-12, RNF-13.
- **Servicio:** `InformeService::promedioPorUnidad(array $filtros, int $idUsuario, string $rol): array` → `{filas: list<array>, grafico: list<array>, resumen_anual: list<array>, sin_datos: bool}`.
- **Repositorio:** `InformeRepository::promedioPorUnidad()` agrupa por `factura.unidad_medida` (snapshot), con `AVG`, `MIN`, `MAX` y `COUNT(*)`; `periodo='mes'` (DI-09).
- **Reglas:** kWh/m³/Gigas promedian `consumo_del_periodo`; “Moneda fija” promedia `monto_total` y se rotula “Monto promedio” (D2-04); consumos y montos con 2 decimales.
- **Archivos existentes a modificar:** `app/Config/Routes.php`, `app/Views/layout/main.php`, `app/Views/layout/sidebar.php`, `docs/rutas.md`, `docs/decisiones.md`.
- **Archivos nuevos:** método `promedioUnidad` en `app/Controllers/InformesController.php` (archivo creado por HU-21; si se implementa HU-22 primero, crear el controlador con ambos métodos), `app/Views/informes/promedio-unidad.php`, `tests/feature/InformesPromedioUnidadTest.php`.
- **Verificación:** `InformesPromedioUnidadTest` cubre acceso por rol, filtros, las cuatro unidades, etiqueta “Monto promedio”, resumen anual, sin datos y formato.

---

## 7. Esquema y migraciones previstas

No se modifica `bd/serviinmueble (1).sql`. Las tres migraciones nuevas se aplican en este orden:

1. `app/Database/Migrations/2026-11-20-000001_AlterTransaccionHito2.php`
   - `estado` ENUM(`pendiente`,`validada`,`rechazada`) NOT NULL DEFAULT `pendiente`.
   - `id_admin_validador` INT NULL → `usuario(id_usuario)`.
   - `fecha_validacion` DATETIME NULL.
   - `motivo_rechazo` VARCHAR(255) NULL.
   - `comprobante_nombre` VARCHAR(255) NULL.
   - `comprobante_mime` VARCHAR(100) NULL.
   - `UNIQUE KEY uk_transaccion_factura_comprobante (id_factura, numero_comprobante)` (D2-11).
   - `KEY idx_transaccion_estado_pago (estado, fecha_pago)`.
   - Backfill DI-04 y `down()` que revierte columnas/índices.
2. `app/Database/Migrations/2026-11-20-000002_CreateNotificacion.php`
   - `id_notificacion`, `id_usuario` FK, `id_factura` FK, `id_transaccion` FK NULL, `tipo` ENUM(`por_vencer`,`vencida`,`pago_validado`,`pago_rechazado`), `mensaje` VARCHAR(255), `leida` TINYINT(1) DEFAULT 0, `fecha_creacion` DATETIME DEFAULT CURRENT_TIMESTAMP, `idempotencia_key` VARCHAR(120) NOT NULL (DI-01).
   - `UNIQUE (id_usuario, idempotencia_key)`; índices (`id_usuario`,`leida`,`fecha_creacion`), (`id_usuario`,`id_factura`,`tipo`), (`id_usuario`,`id_transaccion`,`tipo`).
3. `app/Database/Migrations/2026-11-20-000003_AddIndicesFacturaHito2.php`
   - `idx_factura_vencimientos (estado, fecha_primer_vencimiento, fecha_segundo_vencimiento)`.
   - `idx_factura_periodo_servicio (periodo_anio, periodo_mes, id_propiedad_servicio)`.

`factura.estado` no se modifica. `comprobante_digital` ya existe como MEDIUMBLOB; el límite de 5 MB se valida en aplicación (DI-02).

---

## 8. Catálogo de mensajes (literales)

Copiar exactamente; los tests deben verificar los mismos textos.

| Situación | Mensaje |
|---|---|
| Sin permiso | `No tiene permisos para esta acción` |
| Botón 403 | `Volver al dashboard` |
| Factura pagada al querer pagar | `La factura ya está pagada` |
| Monto distinto del total | `El monto debe ser igual al total de la factura` |
| Fecha de pago futura | `La fecha de pago no puede ser futura` |
| Número de comprobante vacío | `El número de comprobante es obligatorio` |
| Número de comprobante inválido | `El número de comprobante debe ser alfanumérico de 1 a 50 caracteres` |
| Comprobante duplicado | `Ya existe un comprobante con ese número para esta factura` |
| Formato de comprobante inválido | `El comprobante debe ser PDF, JPG o JPEG` |
| Comprobante > 5 MB | `El comprobante no puede superar los 5 MB` |
| Comprobante obligatorio ausente | `El comprobante es obligatorio para este medio de pago` |
| Pago pendiente duplicado | `Ya existe un pago pendiente de validación para esta factura` |
| Éxito de registro | `Pago registrado. Queda pendiente de validación.` |
| Pago ya procesado | `El pago ya fue procesado` |
| Motivo de rechazo vacío | `El motivo del rechazo es obligatorio` |
| Motivo > 255 caracteres | `El motivo del rechazo no puede superar los 255 caracteres` |
| Sin pagos pendientes | `No hay pagos pendientes de validación` |
| Sin notificaciones | `No tenés notificaciones` |
| Sin datos interanuales | `No hay datos de consumo para los filtros seleccionados` |
| Sin datos de promedio | `No hay datos para los filtros seleccionados` |
| Sin mora | `No hay facturas en mora con los filtros seleccionados` |
| Gráficos sin datos | `Sin datos` |
| Variación con base 0 | `—` |
| Etiqueta de monto (interanual) | `Monto` |
| Etiqueta de promedio Moneda fija | `Monto promedio` |
| Éxito de validación | `Pago validado correctamente.` |
| Éxito de rechazo | `Pago rechazado correctamente.` |

Mensajes 404: `No se encontró la factura solicitada.` y `No se encontró el pago solicitado.`

---

## 9. Dependencias y orden de ejecución

```
HU-17 (migración transaccion + registro)
  → HU-20 (migracion notificacion + NotificacionService + UI/campanita)
  → HU-18 (validación; consume NotificacionService)
  → HU-19 (mora; consume en_mora y vencimiento aplicable)
  → HU-16 (dashboard; consume todas las métricas)
  → HU-21 → HU-22 (informes; pueden ejecutarse en paralelo entre sí)
```

- HU-21 y HU-22 no dependen de pagos: requieren solo Hito 1 y Chart.js (D2-06/DI-06).
- HU-18 no puede cerrarse sin HU-20 por CA-18.10; HU-20 no requiere HU-18 para existir, pero sus notificaciones de pago se validan con HU-18.
- HU-16 se implementa al final para leer esquema y servicios ya estables; eso no impide que `/dashboard` siga existiendo desde Hito 1 (D-29).
- Antes de la primera HU: sanear el working tree (V-12), ejecutar `SHOW VARIABLES LIKE 'max_allowed_packet';` (V-02) y registrar D2-01…D2-13 + rutas en los documentos base (H2-07).

---

## 10. Pruebas transversales

- Base: `serviinmueble_test`, MySQL/MariaDB, `CIUnitTestCase` + `DatabaseTestTrait` + `FeatureTestTrait`, `$DBGroup = 'tests'`, `$seed = DatabaseSeeder::class`.
- Comandos Windows: `C:\wamp64\bin\php\php8.3.28\php.exe vendor\bin\phpunit` (guía WAMP) o `C:\xampp\php\php.exe vendor\bin\phpunit` (guía XAMPP).
- Comandos WSL: `/mnt/c/xampp/php/php.exe vendor/bin/phpunit`.
- Verificación de rutas: `php spark routes` con el mismo intérprete.
- Si MariaDB no conecta (`10061`), reportar `NO VERIFICABLE`; no sustituir por SQLite ni declarar pruebas aprobadas.
- Cierre por HU: matriz CA → archivo/test, notas D2/H2/DI aplicadas, comandos y resultado. No cerrar con CA faltante o parcial.

---

## 11. Checklist de coherencia de este paquete documental

- [ ] Los 9 documentos usan las mismas 15 rutas, nombres de ruta y controladores.
- [ ] Los 9 documentos usan los mismos nombres de migraciones, servicios, repositorios, modelos, vistas y tests.
- [ ] Los mensajes del catálogo de la sección 8 se copian literalmente.
- [ ] D2-01…D2-13, H2-01…H2-07 y DI-01…DI-09 se referencian con los mismos IDs.
- [ ] `factura.estado` sigue siendo `pendiente|pagada`; “vencida” es calculada.
- [ ] `debito automatico` se escribe sin tilde; `Moneda fija` con mayúscula inicial.
- [ ] No se menciona tabla `roles`, ni columnas `id_rol`, `password_hash`, `created_at`, `updated_at`, `deleted_at`.
- [ ] No se promete email, cron, pasarela de pago, intereses ni pagos parciales.
- [ ] El estado de Hito 1 refleja throttle 900 s, HU-08/09/12 integradas y suite 124/773 OK.
- [ ] Los puntos A3–A5/B1–B2 siguen marcados como no verificables hasta tener BD.
