# Resultado consolidado de implementación · ServiInmueble 2026

**Actualizado:** 2026-11-09
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
| HU-08 | Base implementada; integración incompleta | CA-08.3 continúa expresamente diferido aunque HU-13 y HU-14 ya existen; CA-08.2/08.4 requieren verificar preservación tras editar el catálogo. |
| HU-09 | Base implementada; CA-09.5 pendiente | El detalle no muestra la cantidad de inmuebles vinculados; la integración asignada a HU-13 debe completarse. |
| HU-10 | Implementada | Alta, validaciones y asignación del propietario; `InmueblesTest.php`. |
| HU-11 | Implementada | Edición, baja lógica, reactivación y permisos; CA-11.4 se verifica junto a HU-13/HU-14. |
| HU-12 | Parcial en integración | Listados y detalle base implementados; falta integrar los totales HU-15 en el resumen de facturación de CA-12.5. |
| HU-13 | Implementada | Asociación, estados, permisos, duplicados, baja lógica y disponibilidad para facturación; `ServiciosInmuebleTest.php`. |
| HU-14 | Implementada | Alta mensual/anual, snapshot, validaciones y acceso; `FacturasTest.php`. |
| HU-15 | Implementada | Listado, filtros, totales recalculados, orden por vencimiento, control de propiedad y estado vacío; `FacturasTest.php` (12 pruebas, 146 aserciones). |

### Integración que queda abierta

1. **HU-08 / CA-08.3:** la acción de eliminación sigue mostrando “La eliminación se habilitará al completar la integración con HU-13 y HU-14”. Ambas HU ya están implementadas; el mensaje quedó obsoleto y el CA debe completarse conforme a H-25: eliminar físicamente solo si no hay vínculos; si hay vínculos, conservar y desactivar.
2. **HU-08 / CA-08.2 y CA-08.4:** existe prueba de que la factura nueva guarda el snapshot. Falta prueba de regresión que edite el tipo de servicio y compruebe que una factura preexistente conserva proveedor, categoría, unidad y lecturas.
3. **HU-09 / CA-09.5:** el detalle de tipo no cuenta los inmuebles vinculados. HU-13, responsable de la integración diferida, ya está implementada.
4. **HU-12 / CA-12.5:** el detalle muestra los servicios vinculados, pero el bloque de resumen sigue siendo un aviso. HU-15 ya implementa el listado y los totales; falta conectar ese resumen en la vista de detalle.
5. **HU-13 / CA-13.5:** la prueba confirma que la baja conserva la fila de factura y la transacción. No carga ni afirma valores de lectura; conviene añadir esa aserción explícita en la prueba de preservación.
6. La integración pendiente de HU-12 CA-12.5 no bloquea el cierre base de HU-15; su integración debe reutilizar el servicio de listado/totales de facturas.

Los pendientes se informan en vez de declarar cierres de integración no demostrados. La implementación de HU-15 queda documentada abajo; no se modificaron las implementaciones de HU-08, HU-09 ni la vista de detalle de HU-12.

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
| HU-08 · CA-08.2, CA-08.4 | HU-14 persiste snapshot y HU-13 conserva registros al dar de baja un vínculo; falta comprobar que una edición posterior del catálogo no altera facturas/lecturas existentes. | Integración pendiente |
| HU-08 · CA-08.3 | La ruta informa que borrar está diferido; no implementa eliminación segura ni aviso condicional por asociaciones. Prueba actual: `testPhysicalDeletionRouteClearlyReportsItsDeferredIntegration`. | Pendiente |
| HU-09 · CA-09.1 a CA-09.4 | Listados Admin, filtros, edición accesible y catálogo de solo lectura para Propietario con tipos activos; `AdminTiposServicioAccessTest.php`. | Verificado |
| HU-09 · CA-09.5 | El detalle muestra categoría, unidad y proveedor, pero no la cantidad de inmuebles vinculados; `TiposServicioController::show()` y `AdminTiposServicioAccessTest.php`. | Integración pendiente |
| HU-10 · CA-10.1 a CA-10.6 | Alta, campos, catálogo, superficie, asignación de propietario y acceso; `InmueblesTest.php`. | Verificado |
| HU-11 · CA-11.1 a CA-11.3, CA-11.5, CA-11.6 | Edición, estados, baja lógica, preservación de registros, reactivación y autorización; `InmueblesTest.php`. | Verificado |
| HU-11 · CA-11.4 | Propiedad inactiva rechaza nuevos servicios y facturas; `ServiciosInmuebleTest.php`, `FacturasTest.php`. | Verificado en integración |
| HU-12 · CA-12.1 a CA-12.4 | Listados y filtros, exclusión por defecto de inactivos y control Admin/Propietario; `InmueblesTest.php`. | Verificado |
| HU-12 · CA-12.5 | Detalle incluye servicios vinculados. El resumen de facturación aún es texto informativo; HU-15 ya provee los totales para integrarlo. | Integración pendiente |
| HU-13 · CA-13.1, CA-13.6, CA-13.7 | Estados/transiciones y regla de solo facturar con servicios activos; `ServiciosInmuebleTest.php`, `FacturasTest.php`. | Verificado |
| HU-13 · CA-13.2, CA-13.8, CA-13.9 | Campos contractuales, catálogo activo vacío y bloqueo de inmueble inactivo; `ServiciosInmuebleTest.php`. | Verificado |
| HU-13 · CA-13.3, CA-13.4 | Dueño/Admin, 403 para propietario ajeno, duplicado vigente y revinculación tras inactivar; `ServiciosInmuebleTest.php`. | Verificado |
| HU-13 · CA-13.5 | Baja lógica conserva factura y pago, sin borrado físico; `testDeactivationPreservesExistingInvoiceAndPaymentHistory`. La lectura no está afirmada explícitamente en ese test. | Implementado; aserción de lectura pendiente |
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
- **D-28 / H-25, H-26:** se distinguen cierres base e integración. Los diferimientos HU-08/HU-09/HU-12 están identificados arriba; al completarse las HU dependientes se deben cerrar las integraciones vencidas.
- **H-24 / D-29:** `/dashboard` sigue siendo el destino genérico para validar logout.
- **H-28:** el estado Vencida se calcula con el segundo vencimiento si existe; si no, con el primero. Se incluye en el total pendiente.
- **P-01:** resuelta por el usuario el 2026-11-09 con opción (b): no agregar acción para registrar pagos; se usan facturas pagadas existentes o sembradas.
- **P-05:** resuelta por el usuario el 2026-11-09 con opción (a): no contemplar pagos parciales; Total Pagado suma el monto completo de las facturas pagadas.
- No se resolvieron otras decisiones pendientes fuera de las aprobaciones explícitas de P-01, P-03 y P-05.

## 5. Validación reproducible de este estado

Ejecución del **2026-11-09** en el entorno local:

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

## 6. Pendientes para el próximo cierre

1. Completar HU-08 CA-08.3 y retirar el aviso de integración cuando la eliminación segura esté implementada y probada.
2. Agregar pruebas de integración HU-08 CA-08.2/08.4 sobre una factura existente después de editar el catálogo.
3. Implementar el conteo de inmuebles vinculados de HU-09 CA-09.5.
4. Integrar el listado/totales de HU-15 al resumen de facturación de HU-12 CA-12.5; HU-15 base ya está implementada y probada.
5. Extender el test de baja HU-13 para registrar y afirmar explícitamente la conservación de lecturas.
6. Repetir la suite con PHP 8.3 y MariaDB 10.11 antes de certificar esas versiones objetivo.
