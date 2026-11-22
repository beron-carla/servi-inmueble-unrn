# Resultado consolidado de implementación · ServiInmueble 2026

**Actualizado:** 2026-11-20
**Alcance consolidado:** HU-00 a HU-15
**Fuentes funcionales:** `docs/historias_de_usuario/HU-XX_compacto.md` y `HU_Hito1_compacto.txt`
**Fuentes técnicas:** `docs/decisiones.md`, `docs/rutas.md` y `docs/00_bootstrap.md`

Este documento reemplaza el registro cronológico de conversaciones y salidas repetidas de herramientas por un resumen organizado de implementación, evidencia y pendientes. El estado se distingue entre **implementado/verificado**, **integración pendiente** y **fuera de alcance**; tener una ruta en el mapa o un resultado histórico no se toma por sí solo como prueba de que una acción está completa.

## 1. Estado general

| Historia | Estado revisado | Evidencia principal / alcance pendiente |
|---|---|---|
| HU-00 | Implementada | BC-01 a BC-20; ver `docs/00_bootstrap.md`, `BootstrapConfigTest.php` y `BootstrapDatabaseTest.php`. |
| HU-01 | Implementada | Registro, validación, unicidad y hash; `AuthServiceTest.php`, `AuthRegistrationTest.php`. |
| HU-02 | Implementada | Login, throttling, sesión e ingreso restringido por contraseña temporal; pruebas Auth, sesión y throttle. |
| HU-03 | Implementada | Logout, expiración de cookie e invalidación; `AuthLogoutTest.php`. |
| HU-04 | Implementada | Alta de usuarios Admin; pruebas de Usuario y `AdminUsuariosAccessTest.php`. |
| HU-05 | Implementada | Edición, estados, auditoría, sesiones y contraseña temporal; `UsuarioGestionTest.php`, `AdminUsuariosAccessTest.php`. |
| HU-06 | Implementada | Listado, búsqueda, filtros y detalle base; `UsuarioListadoTest.php`, `UsuarioListadoViewTest.php`. |
| HU-07 | Implementada | Alta y validaciones de tipos; `TipoServicioServiceTest.php`, `AdminTiposServicioAccessTest.php`. |
| HU-08 | Implementada e integrada | CA-08.2/08.4 conservan snapshots y lecturas; CA-08.3 elimina solo tipos sin vínculos y desactiva tipos con uso. |
| HU-09 | Implementada e integrada | CA-09.5 cuenta inmuebles distintos vinculados al tipo en el detalle administrativo; verificado con prueba de integración. |
| HU-10 | Implementada | Alta, validaciones y asignación del propietario; `InmueblesTest.php`. |
| HU-11 | Implementada | Edición, baja lógica, reactivación y permisos; CA-11.4 se verifica junto a HU-13/HU-14. |
| HU-12 | Implementada e integrada | CA-12.5 incluye servicios vinculados y resumen de facturación pendiente, pagado y general; verificado con `InmueblesTest.php`. |
| HU-13 | Implementada | Asociación, estados, permisos, duplicados, baja lógica y disponibilidad para facturación; `ServiciosInmuebleTest.php`. |
| HU-14 | Implementada | Alta mensual/anual, snapshot, validaciones y acceso; `FacturasTest.php`. |
| HU-15 | Implementada | Listado, filtros, totales recalculados, orden por vencimiento, control de propiedad y estado vacío; `FacturasTest.php` (12 pruebas, 146 aserciones). |

### Integración cerrada

1. **HU-13 / CA-13.5:** la prueba confirma que la baja conserva la fila de factura, la transacción y las lecturas. El cierre de esta integración quedó verificado el 2026-11-20.

Los pendientes restantes se informan sin declarar cierres no demostrados. HU-08, HU-09 y HU-12 quedaron integradas y documentadas.

## 2. Matriz de trazabilidad y evidencia

Las referencias a pruebas corresponden a `tests/`. Cada fila indica el conjunto de criterios cubierto por la evidencia citada; los estados de integración pendientes se detallan en la sección 1.

| HU / CA | Implementación y evidencia | Estado |
|---|---|---|
| HU-00 · BC-01 a BC-20 | Configuración, esquema, migraciones, seeds, filtros, rutas y base de pruebas; `docs/00_bootstrap.md`, `BootstrapConfigTest.php`, `BootstrapDatabaseTest.php`. | Verificado |
| HU-01 · CA-01.1, CA-01.3, CA-01.5 | Hash de contraseña, política y alta activa; `AuthServiceTest.php`. | Verificado |
| HU-01 · CA-01.2, CA-01.4, CA-01.6 | Normalización/unicidad del email, rol de propietario y aceptación requerida; `AuthServiceTest.php`, `AuthRegistrationTest.php`. | Verificado |
| HU-02 · CA-02.1 | Regeneración de sesión y token; `SesionServiceTest.php`. | Verificado |
| HU-02 · CA-02.2 | Expiración a 30 minutos y control de sesión; `BootstrapDatabaseTest.php`, `SesionServiceTest.php`. | Verificado |
| HU-02 · CA-02.3 | Mensajes de login, estados de usuario y límite de intentos; `AuthServiceLoginTest.php`, `LoginThrottleServiceTest.php`. | Verificado |
| HU-02 · CA-02.4 | Rutas protegidas, login y acceso a dashboard; `AuthLoginTest.php`, `spark routes`. | Verificado |
| HU-03 · CA-03.1 | Invalida sesión persistida, destruye sesión local, expira cookie y bloquea regreso al dashboard; `AuthLogoutTest.php`. | Verificado |
| HU-04 · CA-04.1 a CA-04.6 | Validación, email único, hash, roles, acceso Admin y alta activa; `UsuarioServiceTest.php`, `AdminUsuariosAccessTest.php`. | Verificado |
| HU-05 · CA-05.1 a CA-05.8 | Modificación, baja/bloqueo, protección propia, auditoría, invalidación de sesiones y reset; `UsuarioGestionTest.php`, `AdminUsuariosAccessTest.php`, `UsuarioServiceTest.php`. | Verificado |
| HU-06 · CA-06.1 a CA-06.5 | Listado, búsqueda sin distinguir mayúsculas, filtros, permisos y estado vacío; `UsuarioListadoTest.php`, `UsuarioListadoViewTest.php`, `AdminUsuariosAccessTest.php`. | Verificado |
| HU-07 · CA-07.1 a CA-07.5 | Campos, categorías, unidades compatibles, unicidad tipo/proveedor y autorización Admin; `TipoServicioServiceTest.php`, `AdminTiposServicioAccessTest.php`. | Verificado |
| HU-07 · CA-07.6 | El alta guarda `activo = 1`; visibilidad en catálogo confirmada junto con HU-09; `AdminTiposServicioAccessTest.php`. | Verificado |
| HU-08 · CA-08.1, CA-08.5, CA-08.6 | Edición base, acceso Admin y activar/desactivar; `TipoServicioServiceTest.php`, `AdminTiposServicioAccessTest.php`. | Verificado |
| HU-08 · CA-08.2, CA-08.4 | Editar el catálogo no altera proveedor, categoría, unidad, lecturas ni consumo de facturas existentes; la factura creada conserva el snapshot. | Verificado |
| HU-08 · CA-08.3 | Elimina físicamente solo tipos sin `propiedad_servicio`; rechaza con el mensaje exacto y conserva tipos con vínculos. | Verificado |
| HU-09 · CA-09.1 a CA-09.4 | Listados Admin, filtros, edición accesible y catálogo de solo lectura para Propietario con tipos activos; `AdminTiposServicioAccessTest.php`. | Verificado |
| HU-09 · CA-09.5 | El detalle muestra categoría, unidad, proveedor y la cantidad de inmuebles distintos vinculados mediante `COUNT(DISTINCT id_propiedad)`; `TipoServicioRepository::contarPropiedadesVinculadas()`, `TipoServicioService::buscarPorId()` y `testAdministratorCanViewTypeDetails`. | Verificado |
| HU-10 · CA-10.1 a CA-10.6 | Alta, campos, catálogo, superficie, asignación de propietario y acceso; `InmueblesTest.php`. | Verificado |
| HU-11 · CA-11.1 a CA-11.3, CA-11.5, CA-11.6 | Edición, estados, baja lógica, preservación de registros, reactivación y autorización; `InmueblesTest.php`. | Verificado |
| HU-11 · CA-11.4 | Propiedad inactiva rechaza nuevos servicios y facturas; `ServiciosInmuebleTest.php`, `FacturasTest.php`. | Verificado en integración |
| HU-12 · CA-12.1 a CA-12.4 | Listados y filtros, exclusión por defecto de inactivos y control Admin/Propietario; `InmueblesTest.php`. | Verificado |
| HU-12 · CA-12.5 | El detalle incluye servicios vinculados y calcula por propiedad los totales pendiente, pagado y general mediante `FacturaRepository::resumenPorPropiedad()`; la vista muestra los tres totales. | Verificado |
| HU-13 · CA-13.1, CA-13.6, CA-13.7 | Estados/transiciones y regla de solo facturar con servicios activos; `ServiciosInmuebleTest.php`, `FacturasTest.php`. | Verificado |
| HU-13 · CA-13.2, CA-13.8, CA-13.9 | Campos contractuales, catálogo activo vacío y bloqueo de inmueble inactivo; `ServiciosInmuebleTest.php`. | Verificado |
| HU-13 · CA-13.3, CA-13.4 | Dueño/Admin, 403 para propietario ajeno, duplicado vigente y revinculación tras inactivar; `ServiciosInmuebleTest.php`. | Verificado |
| HU-13 · CA-13.5 | Baja lógica conserva factura, pago y lecturas, sin borrado físico; `testDeactivationPreservesExistingInvoiceAndPaymentHistory`. | Verificado |
| HU-14 · CA-14.1 | Calcula `vencida` desde el vencimiento aplicable sin guardarla; `testCalculatedInvoiceStateUsesTheApplicableDueDate`, `testOwnerCreatesMonthlyMeteredInvoiceWithSnapshotAndCalculatedStatus`. | Verificado |
| HU-14 · CA-14.2 | Campos y snapshot proveedor/categoría/unidad; pruebas de alta mensual y anual en `FacturasTest.php`. | Verificado |
| HU-14 · CA-14.3 | Duplicado mensual rechazado; período anual diferenciado; `testDuplicatePeriodsAreRejectedButAnAnnualPeriodIsDistinct`. | Verificado |
| HU-14 · CA-14.4 | Consumo por lecturas o carga manual sin medidor; altas mensual/anual y `testNegativeManualConsumptionIsRejected`. | Verificado |
| HU-14 · CA-14.5 | Fecha de emisión futura, vencimientos y lecturas incoherentes rechazados; `testReadingsDueDatesAndFutureIssueDateAreValidated`. | Verificado |
| HU-14 · CA-14.6 | Dueño/Admin, 403 por propiedad ajena, estados de servicio y propiedad inactiva; `testOwnerIsForbiddenForOtherOwnersAndAdminCanCreateInvoices`, `testPausedReviewAndInactivePropertiesRejectInvoiceCreation`. | Verificado |
| HU-15 · CA-15.1 | Tabla con inmueble, servicio, período, vencimiento aplicable, monto y estado calculado; `testOwnerInvoiceListIsScopedSortedAndShowsCalculatedStatusAndTotals`. | Verificado |
| HU-15 · CA-15.2 | Total pendiente incluye facturas pendientes y vencidas; total pagado suma montos completos de facturas pagadas; total general suma ambos. Verificado en `testOwnerInvoiceListIsScopedSortedAndShowsCalculatedStatusAndTotals`. | Verificado |
| HU-15 · CA-15.3 | Filtros de inmueble, servicio, estado y rango de fechas sobre vencimiento aplicable; `testInvoiceFiltersApplyToRowsAndAllSummaryTotals`. | Verificado |
| HU-15 · CA-15.4 | Los tres totales se recalculan con los filtros aplicados; `testInvoiceFiltersApplyToRowsAndAllSummaryTotals`. | Verificado |
| HU-15 · CA-15.5 | Listado ordenado por vencimiento aplicable descendente; `testOwnerInvoiceListIsScopedSortedAndShowsCalculatedStatusAndTotals`. | Verificado |
| HU-15 · permisos y estado vacío | Propietario limitado a sus facturas, Admin con listado global y mensaje cuando no hay resultados; `testOwnerInvoiceListIsScopedSortedAndShowsCalculatedStatusAndTotals`, `testAdminSeesAllInvoiceOwnersAndEmptyFilterShowsEmptyState`. | Verificado |

## 3. Rutas comprobadas y alcance de rutas

Las rutas de alta HU-14 estaban en `app/Config/Routes.php` y en `docs/rutas.md`; se verificó su registro con `spark routes`:

| Método | URI | Controlador | Estado |
|---|---|---|---|
| GET | `/servicios-inmueble/(:num)/facturas/nuevo` | `FacturasController::create` | Implementada |
| POST | `/servicios-inmueble/(:num)/facturas` | `FacturasController::store` | Implementada; CSRF global y PRG |
| GET | `/facturas` | `FacturasController::index` | Implementada; filtro `auth`, listado con filtros, sumarios y paginación |

La ruta coincide con `docs/rutas.md` y no se agregó ninguna ruta de pago. Las rutas HU-13 y demás rutas previas conservan el mapa y filtros documentados; no se habilitó auto-routing.

## 4. Decisiones y notas aplicadas

- **D-01 / esquema canónico:** no se agregaron tablas ni columnas; el SQL canónico no se modificó.
- **D-16 / P-03:** se aprobó habilitar períodos mensuales y anuales. Anual guarda `periodo = 'anio'` y `periodo_mes = '00'`; la decisión está registrada en `docs/decisiones.md`.
- **D-17 / H-27:** proveedor, categoría y unidad se copian al crear la factura.
- **D-19, D-20 / H-31, H-32, H-33:** autorización en el servicio, control de pertenencia y 403 amigable; acciones de interfaz ocultas según rol/estado.
- **D-28 / H-25, H-26:** se distinguen cierres base e integración. Los diferimientos HU-08/HU-09/HU-12 se cerraron al completarse sus HU dependientes; cualquier integración restante se identifica explícitamente.
- **H-24 / D-29:** `/dashboard` sigue siendo el destino genérico para validar logout.
- **H-28:** el estado Vencida se calcula con el segundo vencimiento si existe; si no, con el primero. Se incluye en el total pendiente.
- **P-01:** resuelta por el usuario el 2026-11-09 con opción (b): no agregar acción para registrar pagos; se usan facturas pagadas existentes o sembradas.
- **P-05:** resuelta por el usuario el 2026-11-09 con opción (a): no contemplar pagos parciales; Total Pagado suma el monto completo de las facturas pagadas.
- No se resolvieron otras decisiones pendientes fuera de las aprobaciones explícitas de P-01, P-03 y P-05.

## 5. Validación reproducible de este estado

Validaciones históricas del **2026-11-09**:

| Comando / comprobación | Resultado |
|---|---|
| `C:\xampp\php\php.exe vendor\bin\phpunit` | **117 tests, 693 assertions; OK**. PHPUnit configurado para MySQLi y `serviinmueble_test`; no SQLite. |
| `php -l` sobre `app/` y `tests/` | **177 archivos PHP; sin errores de sintaxis**. |
| `C:\xampp\php\php.exe vendor\bin\phpunit tests\feature\FacturasTest.php` | **12 tests, 146 assertions; OK** para HU-14/HU-15. La primera ejecución no conectó porque MariaDB estaba detenida; se inició temporalmente el servicio local, se repitió la suite y se detuvo el proceso iniciado. |
| `php -l` sobre archivos PHP modificados de HU-15 | **6 archivos; sin errores de sintaxis**: repositorio, servicio, controlador, vista, sidebar y prueba. |
| `C:\xampp\php\php.exe spark routes` | Confirma `GET /facturas` → `FacturasController::index`, filtro `auth`; rutas de alta HU-14 presentes. |
| `git diff --check` | Sin errores de whitespace en los archivos revisados. |
| Versiones observadas | PHP CLI **8.2.12** y MariaDB **10.4.32**. No son las versiones objetivo PHP 8.3 y MariaDB 10.11. |

La instancia MariaDB que se inició exclusivamente para ejecutar las pruebas de facturas fue apagada al terminar. Las pruebas actuales certifican el comportamiento en la versión local observada, no en MariaDB 10.11/PHP 8.3.

Validación de integración HU-12 del **2026-11-20**:

| Comando / comprobación | Resultado |
|---|---|
| PHP 8.3.28 `php -l` sobre cuatro archivos modificados | Sin errores de sintaxis. |
| `C:\wamp64\bin\php\php8.3.28\php.exe vendor\bin\phpunit tests\feature\InmueblesTest.php` | **12 tests, 110 assertions; OK**. |
| `C:\wamp64\bin\php\php8.3.28\php.exe spark routes` | Rutas explícitas y filtros `auth`/`role:admin` sin cambios incorrectos. |
| `git diff --check` | Sin errores de whitespace en el diff. |

Validación completa del **2026-11-20**:

| Comando / comprobación | Resultado |
|---|---|
| `C:\wamp64\bin\php\php8.3.28\php.exe vendor\bin\phpunit tests\unit\Services\LoginThrottleServiceTest.php` | **1 test, 7 assertions; OK**. |
| `C:\wamp64\bin\php\php8.3.28\php.exe vendor\bin\phpunit` | **124 tests, 773 assertions; OK**. |

## 6. Pendientes

No quedan fallos conocidos en la suite completa ejecutada.

---

## 7. Registro del 2026-11-22 · Implementación de HU-17

### Alcance implementado

- Se implementó exclusivamente **HU-17 · Registro de pago con comprobante digital**.
- Se cumplió H2-07 antes de codificar: se registraron D2-01 a D2-13 y las rutas del Hito 2 en `docs/decisiones.md` y `docs/rutas.md`.
- Se creó y aplicó la migración `2026-11-20-000001_AlterTransaccionHito2.php`.
- Se verificó el working tree antes de comenzar; no existían cambios pendientes.
- La migración agregó validación de pagos, comprobantes y índices a `transaccion`, y aplicó el backfill DI-04 a pagos históricos.
- No se modificó `bd/serviinmueble (1).sql`.
- No se implementaron HU-18, HU-19, HU-20, HU-16, HU-21 ni HU-22.

### Archivos creados

| Tipo | Archivo |
|---|---|
| Controlador | `app/Controllers/PagosController.php` |
| Servicio | `app/Services/PagoService.php` |
| Servicio | `app/Services/ComprobanteService.php` |
| Servicio | `app/Services/MoraService.php` |
| Repositorio | `app/Repositories/PagoRepository.php` |
| Migración | `app/Database/Migrations/2026-11-20-000001_AlterTransaccionHito2.php` |
| Vistas | `app/Views/pagos/create.php`, `app/Views/pagos/index.php` |
| Pruebas unitarias | `tests/unit/Services/ComprobanteServiceTest.php` |
| Pruebas de esquema | `tests/database/Hito2SchemaTest.php` |
| Pruebas feature | `tests/feature/PagosTest.php` |

### Archivos modificados

- `app/Config/Routes.php`
- `app/Models/TransaccionModel.php`
- `app/Database/Seeds/DemoSeeder.php`
- `app/Views/layout/sidebar.php`
- `app/Views/facturas/index.php`
- `app/Views/errors/html/error_403.php`
- `app/Helpers/format_helper.php`
- `docs/decisiones.md`
- `docs/rutas.md`

### Rutas verificadas

| Método | URI | Nombre | Controlador | Filtros |
|---|---|---|---|---|
| GET | `/facturas/(:num)/pagar` | `pagos.create` | `PagosController::create/$1` | `auth` |
| POST | `/facturas/(:num)/pagos` | `pagos.store` | `PagosController::store/$1` | `auth` |
| GET | `/pagos` | `pagos.index` | `PagosController::index` | `auth` |
| GET | `/pagos/(:num)/comprobante` | `pagos.comprobante` | `PagosController::comprobante/$1` | `auth` |

Las rutas quedaron registradas y confirmadas con `C:\xampp\php\php.exe spark routes`.

### Matriz CA-17

| CA | Implementación | Evidencia |
|---|---|---|
| CA-17.1 | Propietario propio y Administrador autorizados; otros roles o propietarios ajenos reciben 403 real. | `PagoService`, `PagosController`, `PagosTest::testOwnerCannotPayOtherOwnersInvoice`. |
| CA-17.2 | Solo factura pendiente o vencida calculada; pagada rechazada con mensaje literal. | `PagoService::opcionesPago()` y `registrar()`, pruebas feature. |
| CA-17.3 | Pago por el total con comparación normalizada a dos decimales y sin parciales. | `PagoService`, `DI-05`. |
| CA-17.4 | Fecha obligatoria, válida y no futura. | `PagosTest::testFutureDateDuplicatePaymentAndMandatoryReceiptAreRejected`. |
| CA-17.5 | Medio exacto, número alfanumérico de 1 a 50 y unicidad por factura. | `PagoService::registrar()`, índice único de migración. |
| CA-17.6 | Comprobante obligatorio salvo efectivo; PDF/JPG/JPEG hasta 5 MiB, detectado por contenido. | `ComprobanteService`, `ComprobanteServiceTest`. |
| CA-17.7 | `en_mora = 1` posterior al vencimiento aplicable; en caso contrario `0`. | `MoraService::esPagoTardio()`, prueba feature de pago tardío. |
| CA-17.8 | Pago creado como pendiente; factura permanece pendiente; duplicados pendientes rechazados. | `PagoRepository::existePendiente()`, prueba feature de duplicado. |
| CA-17.9 | CSRF activo y PRG; mensaje de éxito literal. | `PagosController::store()`, prueba feature. |
| CA-17.10 | Comprobante privado para propietario/Admin con `inline`, `nosniff` y nombre seguro. | `PagoService::comprobante()`, `PagosController::comprobante()`, prueba feature de descarga. |

### Decisiones aplicadas

- D2-01, D2-02, D2-05, D2-07 y D2-11.
- H2-01, H2-02, H2-04 y H2-07.
- DI-02, DI-04, DI-05, DI-07 y DI-08.
- D-16: “Vencida” permanece calculada y no se guarda en `factura.estado`.

### Validación ejecutada

| Comando / comprobación | Resultado |
|---|---|
| `C:\xampp\php\php.exe spark migrate` | Migración HU-17 aplicada correctamente. |
| `C:\xampp\php\php.exe spark migrate:status` | Migración `2026-11-20-000001` registrada en batch 2. |
| `C:\xampp\php\php.exe spark routes` | Las cuatro rutas están registradas con `auth`. |
| `php -l` sobre archivos HU-17 | Sin errores de sintaxis. |
| `tests/unit/Services/ComprobanteServiceTest.php` | MIME por contenido, extensión engañosa, límite y efectivo cubiertos. |
| `tests/database/Hito2SchemaTest.php` | Columnas, índices y backfill verificados. |
| `tests/feature/PagosTest.php` | 5 pruebas, 37 aserciones; OK. |
| Suite PHPUnit completa | **135 tests, 826 assertions; OK**. |

### Base de datos y entorno

- MariaDB local: `10.4.32-MariaDB`.
- Conexión a `serviinmueble` exitosa.
- `max_allowed_packet`: `1048576` bytes, es decir, 1 MiB.
- Riesgo: el límite local es inferior al máximo solicitado de 5 MiB para comprobantes; requiere ajuste o no es posible conservar los adjuntos sin modificar la configuración de MariaDB.

### Pendientes o riesgos

- La instancia XAMPP usa MariaDB 10.4.32, no MariaDB 10.11; el resultado no certifica el entorno objetivo completo.
- El `max_allowed_packet` local no confirmado por el equipo superior a 1 MiB requiere configuración para adjuntos de hasta 5 MiB.
- No se implementó HU-18; por ello las notificaciones de pago deben integrarse al validar o rechazar pagos cuando se complete esa historia.

---

## 8. Registro del 2026-11-22 · Implementación de HU-20

### Alcance implementado

- Se implementó exclusivamente **HU-20 · Notificaciones internas**.
- Se creó y aplicó la migración `2026-11-20-000002_CreateNotificacion.php`.
- Se agregaron los cuatro tipos: `por_vencer`, `vencida`, `pago_validado` y `pago_rechazado`.
- Las vencimientos se generan al ingresar al dashboard o al listado de facturas.
- El destinatario se obtiene del propietario de la factura; no se toma del Administrador.
- Se implementaron listado paginado ordenado DESC, filtros, campanita y contador solo para Propietarios.
- Los Propietarios pueden marcar una notificación o todas como leídas.
- El Administrador dispone de lectura global sin campanita ni marcado de terceros.
- Se respetaron CSRF, PRG, 403 real y 404 estándar.
- No se enviaron correos, mensajes externos ni se modificaron estados de factura o pago.

### Archivos creados

| Tipo | Archivo |
|---|---|
| Configuración | `app/Config/Hito2.php` |
| Migración | `app/Database/Migrations/2026-11-20-000002_CreateNotificacion.php` |
| Modelo | `app/Models/NotificacionModel.php` |
| Repositorio | `app/Repositories/NotificacionRepository.php` |
| Servicio | `app/Services/NotificacionService.php` |
| Controlador | `app/Controllers/NotificacionesController.php` |
| Vista | `app/Views/notificaciones/index.php` |
| Pruebas unitarias | `tests/unit/Services/NotificacionServiceTest.php` |
| Pruebas feature | `tests/feature/NotificacionesTest.php` |

### Archivos modificados

- `app/Config/Routes.php`
- `app/Controllers/DashboardController.php`
- `app/Controllers/FacturasController.php`
- `app/Views/layout/header.php`
- `app/Views/layout/sidebar.php`
- `app/Services/FacturaService.php`
- `app/Repositories/FacturaRepository.php`

### Rutas verificadas

| Método | URI | Nombre | Controlador | Filtros |
|---|---|---|---|---|
| GET | `/notificaciones` | `notificaciones.index` | `NotificacionesController::index` | `auth` |
| POST | `/notificaciones/leer-todas` | `notificaciones.leer-todas` | `NotificacionesController::marcarTodasLeidas` | `auth` |
| POST | `/notificaciones/(:num)/leer` | `notificaciones.leer` | `NotificacionesController::marcarLeida/$1` | `auth` |

### Matriz CA-20

| CA | Implementación | Evidencia |
|---|---|---|
| CA-20.1 | Cuatro tipos definidos en ENUM y servicio. | `NotificacionService`, migración. |
| CA-20.2 | Ventana configurable de por vencer y vencimiento anterior a hoy. | `NotificacionService::registrarVencimientos()`. |
| CA-20.3 | Contratos para pagos validados y rechazados. | `registrarPagoValidado()`, `registrarPagoRechazado()`. |
| CA-20.4 | Idempotencia por clave única de usuario y evento. | `idempotencia_key`, `uk_notificacion_idempotencia`. |
| CA-20.5 | Destinatario propietario y listado global de lectura para Admin. | `NotificacionService::listado()`. |
| CA-20.6 | Campanita Propietario y listado DESC paginado. | Header, repositorio y vista. |
| CA-20.7 | Marcar una o todas; Admin sin marcado ajeno. | Métodos de lectura y pruebas feature. |
| CA-20.8 | Ajena 403 e inexistente 404. | `NotificacionesController`, servicio. |
| CA-20.9 | Estado vacío literal. | Vista de notificaciones. |
| CA-20.10 | Disparo en dashboard/facturas y sin cron ni correo. | Controladores Dashboard y Facturas. |
| CA-20.11 | Enlace a factura y detalle de estado/motivo de pago. | Vista de notificaciones. |

### Decisiones aplicadas

- D2-03, D2-05, D2-08, D2-09 y D2-10.
- H2-03, H2-04 y H2-07.
- DI-01, DI-03, DI-07 y DI-08.

### Validación ejecutada

| Comando / comprobación | Resultado |
|---|---|
| `C:\xampp\php\php.exe spark migrate` | Migración HU-20 aplicada correctamente. |
| `C:\xampp\php\php.exe spark routes` | Tres rutas HU-20 registradas y ordenadas correctamente. |
| `C:\xampp\php\php.exe spark db:table notificacion` | Tabla `notificacion` existente y accesible. |
| `php -l` sobre archivos HU-20 | Sin errores de sintaxis. |
| `tests/unit/Services/NotificacionServiceTest.php` | Tipos, ventana, vencimiento, idempotencia y alcance cubiertos. |
| `tests/feature/NotificacionesTest.php` | 5 pruebas específicas; 29 aserciones; OK. |
| Suite PHPUnit completa | **140 tests, 855 assertions; OK**. |

---

## 9. Registro del 2026-11-22 · Implementación de HU-18

### Alcance implementado

- Se implementó **HU-18 · Validación de pagos**.
- El Administrador puede listar pagos con filtros por estado, propietario, factura, medio de pago y rango de fechas.
- El listado muestra fecha, propietario, inmueble, factura, monto, medio, comprobante y estado.
- El detalle muestra los datos del pago y permite previsualizar o descargar el comprobante.
- Validar o rechazar ejecuta una transición protegida por transacción, bloqueo `FOR UPDATE` y comprobación de estado.
- Validar cambia el pago a `validada`, registra Administrador y fecha, marca la factura como `pagada` y genera `pago_validado`.
- Rechazar exige motivo de 1 a 255 caracteres, cambia el pago a `rechazada`, conserva la factura pendiente y genera `pago_rechazado` con el motivo.
- Los pagos validados/rechazados quedan inmutables y conservados como historial.
- Solo el Administrador accede al panel; un Propietario recibe 403 real.

### Archivos creados

| Tipo | Archivo |
|---|---|
| Servicio | `app/Services/ValidacionPagoService.php` |
| Controlador | `app/Controllers/Admin/PagosController.php` |
| Vistas | `app/Views/admin/pagos/index.php`, `app/Views/admin/pagos/show.php` |
| Pruebas feature | `tests/feature/AdminPagosTest.php` |

### Archivos modificados

- `app/Repositories/PagoRepository.php`
- `app/Config/Routes.php`
- `app/Views/layout/sidebar.php`

### Rutas verificadas

| Método | URI | Nombre | Controlador | Filtros |
|---|---|---|---|---|
| GET | `/admin/pagos` | `admin.pagos.index` | `Admin\PagosController::index` | `auth`, `role:admin` |
| GET | `/admin/pagos/(:num)` | `admin.pagos.show` | `Admin\PagosController::show/$1` | `auth`, `role:admin` |
| POST | `/admin/pagos/(:num)/validar` | `admin.pagos.validar` | `Admin\PagosController::validar/$1` | `auth`, `role:admin` |
| POST | `/admin/pagos/(:num)/rechazar` | `admin.pagos.rechazar` | `Admin\PagosController::rechazar/$1` | `auth`, `role:admin` |

Las cuatro rutas fueron confirmadas con `C:\xampp\php\php.exe spark routes`.

### Matriz CA-18

| CA | Implementación | Evidencia |
|---|---|---|
| CA-18.1 | Acceso restringido al panel administrativo por filtro de rol y comprobación en servicio. | `ValidacionPagoService::listado/detalle/validar/rechazar`, `AdminPagosTest::testOwnerCannotAccessAdminPaymentValidation`. |
| CA-18.2 | Filtros y columnas administrativas completas. | `PagoRepository::listarAdmin()`, vista `admin/pagos/index.php`. |
| CA-18.3 | Detalle con pago, factura, propietario, inmueble y comprobante. | `PagoRepository::buscarParaValidacion()`, vista `admin/pagos/show.php`. |
| CA-18.4 | Validación atomizada: bloqueo, estado, administrador, fecha y factura pagada; doble validación rechazada con mensaje literal. | `ValidacionPagoService::validar()`, `AdminPagosTest::testAdminCanValidatePaymentAndNotifyOwner`. |
| CA-18.5 | Motivo obligatorio y de hasta 255 caracteres; rechazo conserva factura y habilita otro pago futuro. | `ValidacionPagoService::rechazar()`, `AdminPagosTest::testRejectRequiresReasonAndKeepsInvoicePending`. |
| CA-18.6 | El comprobante se sirve con MIME por contenido, nombre original seguro, `inline` y `nosniff`; propietario propio y Admin autorizados. | Reuso de `PagoService::comprobante()` y `PagosController::comprobante()`. |
| CA-18.7 | No existen rutas de edición o eliminación; pagos validados/rechazados quedan históricos e inmutables. | Controlador administrativo y estados de `transaccion`. |
| CA-18.8 | El listado muestra el estado vacío literal cuando el filtro es `pendiente`. | Vista `admin/pagos/index.php`. |
| CA-18.9 | Las validaciones y rechazos dejan transiciones consistentes para que HU-16 pueda actualizar métricas. | CA-18.9 queda integrado por transición; el dashboard Hito 2 no forma parte de esta HU. |
| CA-18.10 | Validar genera `pago_validado`; rechazar genera `pago_rechazado` con el motivo al propietario. | `NotificacionService::registrarPagoValidado/Rechazado`, prueba de validación y código de rechazo. |

### Decisiones aplicadas

- D2-01: no se paga la factura hasta la validación del Administrador.
- D2-05: administrador global y propietario limitado en la vista y el servicio.
- D2-09: notificaciones internas de pago validado/rechazado al propietario.
- H2-01: estado intermedio de `transaccion` y actualización posterior de `factura`.
- H2-03: las acciones de pago generan notificaciones sin modificar factura o pago por sí mismas.
- H2-04: alcance por rol controlado en servidor.
- DI-01: claves idempotentes `pago:{id_transaccion}:{tipo}`.
- DI-07: estado calculado de factura no se usa para modificar estados históricos.
- DI-08: autorización de alcance en el servicio.

### Validación ejecutada

| Comando / comprobación | Resultado |
|---|---|
| `C:\xampp\php\php.exe -l app\Services\ValidacionPagoService.php` | Sin errores de sintaxis. |
| `C:\xampp\php\php.exe -l app\Controllers\Admin\PagosController.php` | Sin errores de sintaxis. |
| `C:\xampp\php\php.exe -l app\Repositories\PagoRepository.php` | Sin errores de sintaxis. |
| `C:\xampp\php\php.exe spark routes` | Cuatro rutas HU-18 registradas con filtros. |
| `C:\xampp\php\php.exe vendor\bin\phpunit --filter AdminPagosTest` | 4 pruebas, 29 assertions; OK. |
| `C:\xampp\php\php.exe vendor\bin\phpunit --filter '/(AdminPagosTest\|PagosTest\|NotificacionesTest)/'` | 11 pruebas, 81 assertions; OK. |
| `C:\xampp\php\php.exe vendor\bin\phpunit --filter 'Hito2SchemaTest\|ComprobanteServiceTest'` | 6 pruebas, 16 assertions; OK. |

### Pendientes o riesgos

- CA-18.9: la actualización efectiva de las métricas del dashboard depende de HU-16.
- La suite completa posterior a HU-18 no fue ejecutada; se verificaron las suites dirigidas a pagos, notificaciones y esquema Hito 2.

---

## 10. Registro del 2026-11-22 · Implementación de HU-19

### Alcance implementado

- Se implementó exclusivamente **HU-19 · Control de mora**.
- Se creó y aplicó la migración `2026-11-20-000003_AddIndicesFacturaHito2.php`.
- Se agregó `GET /mora` con nombre `mora.index`, controlador `MoraController::index` y filtro `auth`.
- Se implementó el reporte de facturas pendientes cuyo vencimiento aplicable ya pasó.
- El vencimiento aplicable usa el segundo vencimiento si existe y el primero como alternativa.
- El reporte muestra propietario, inmueble, servicio, período, vencimiento, días de mora y monto.
- Las filas se ordenan por días de mora descendentes.
- El reporte admite filtros por propietario, inmueble, rango de vencimientos y rango de días de mora.
- El Administrador puede ver toda la mora; el Propietario queda limitado a sus inmuebles.
- Los parámetros manipulados se normalizan y no pueden ampliar el alcance.
- Se expuso `MoraRepository::metricas()` para la cantidad y monto de facturas en mora y pagos tardíos de HU-16.
- Se agregó un enlace de navegación a Mora y un enlace desde el dashboard.
- No se calcularon intereses, recargos, punitorios ni bloqueos automáticos.

### Archivos creados

| Tipo | Archivo |
|---|---|
| Migración | `app/Database/Migrations/2026-11-20-000003_AddIndicesFacturaHito2.php` |
| Repositorio | `app/Repositories/MoraRepository.php` |
| Controlador | `app/Controllers/MoraController.php` |
| Vista | `app/Views/mora/index.php` |
| Pruebas feature | `tests/feature/MoraTest.php` |

### Archivos ampliados o modificados

- `app/Services/MoraService.php`
- `app/Config/Routes.php`
- `app/Views/layout/sidebar.php`
- `app/Views/dashboard/index.php`

### Ruta verificada

| Método | URI | Nombre | Controlador | Filtro |
|---|---|---|---|---|
| GET | `/mora` | `mora.index` | `MoraController::index` | `auth` |

La ruta fue confirmada con `C:\xampp\php\php.exe spark routes`.

### Matriz CA-19

| CA | Implementación | Evidencia |
|---|---|---|
| CA-19.1 | Mora calculada para facturas pendientes cuyo vencimiento aplicable es anterior a hoy. No se persiste el estado `vencida`. | `MoraRepository::listar()`, universo DI-07. |
| CA-19.2 | El pago tardío se marca al registrarse mediante `en_mora = 1` y conserva la marca, también después de validarse. | `MoraService::esPagoTardio()`, migración Hito 2 y modelo existente. |
| CA-19.3 | Reporte con propietario, inmueble, servicio, período, vencimiento, días de mora y monto; orden descendente. | `MoraRepository::listar()`, `app/Views/mora/index.php`. |
| CA-19.4 | Filtros por propietario, inmueble, rango de vencimiento y rango de días; propietario limitado en servidor. | `MoraService::listado()`, `MoraRepository::listar()`. |
| CA-19.5 | El repositorio expone cantidad y monto de facturas vencidas y cantidad de pagos tardíos para HU-16. | `MoraRepository::metricas()`. |
| CA-19.6 | Un pago validado conserva la marca histórica `en_mora = 1` y la factura pasa a pagada sin borrar el pago. | Estado de `transaccion` conservado por HU-18. |
| CA-19.7 | `diasMora = max(0, hoy - vencimientoAplicable)` y preferencia del segundo vencimiento. | `MoraService::diasMora()`, `MoraService::vencimientoAplicable()`. |
| CA-19.8 | Sin columnas ni cálculos de intereses, recargos, punitorios o bloqueos automáticos. | Alcance HU-19 y respuesta sin esas acciones. |

### Decisiones aplicadas

- D2-05: alcance global para Administrador y propio para Propietario.
- H2-02: mora calculada separada del histórico `en_mora` de pagos.
- H2-04: autorización de alcance en el servicio.
- H2-07: migración, ruta y documentación de Hito 2 mantenidas.
- DI-07: vencimiento aplicable con `COALESCE(segundo, primero)` y estado calculado.
- DI-08: autorización siempre aplicada en `MoraService`.
- D-16: `vencida` sigue siendo un estado calculado y nunca se persiste.

### Validación ejecutada

| Comando / comprobación | Resultado |
|---|---|
| `C:\xampp\php\php.exe spark migrate` | Migración `2026-11-20-000003` aplicada correctamente. |
| `C:\xampp\php\php.exe spark routes` | `GET /mora`, nombre `mora.index`, filtro `auth`. |
| `php -l app\Services\MoraService.php` | Sin errores de sintaxis. |
| `php -l app\Repositories\MoraRepository.php` | Sin errores de sintaxis. |
| `php -l app\Controllers\MoraController.php` | Sin errores de sintaxis. |
| `php -l app\Database\Migrations\2026-11-20-000003_AddIndicesFacturaHito2.php` | Sin errores de sintaxis. |
| `php -l tests\feature\MoraTest.php` | Sin errores de sintaxis. |
| `C:\xampp\php\php.exe vendor\bin\phpunit --filter MoraTest` | 3 pruebas, 11 assertions; OK. |
| `C:\xampp\php\php.exe vendor\bin\phpunit --filter 'MoraTest\|PagosTest\|Hito2SchemaTest'` | 14 pruebas, 88 assertions; OK. |

### Pendientes o riesgos

- CA-19.5 quedó expuesto para HU-16; el dashboard todavía depende de implementar o ampliar esa HU.
- La integración visual de paginación completa no fue agregada en este cierre; el repositorio calcula páginas y el reporte muestra las filas.
- No se modificó el SQL canónico ni el ENUM `factura.estado`.
