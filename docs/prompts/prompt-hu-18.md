# Prompt de implementación · HU-18 · Validación de pagos

**Versión:** 1.0  
**Fecha:** 2026-11-20  
**Uso:** pegar primero el prompt general (`docs/prompts/prompt-general-hito-2.md`, sección 2) y después el bloque de la sección 2 de este archivo.  
**Matriz maestro:** [`../historias_de_usuario/hito2/trazabilidad-hito-2.md`](../historias_de_usuario/hito2/trazabilidad-hito-2.md).  
**Orden canónico:** 3.º de 7 (`HU-17 → HU-20 → HU-18 → HU-19 → HU-16 → HU-21 → HU-22`).  
**Estado:** sin implementar. Este documento no modifica código ni BD.

---

## 1. Precondiciones

- Leer íntegramente: `docs/historias_de_usuario/hito2/HU-18_compacto.md`, `README.md`, `HU_Hito2_compacto.md`, `trazabilidad-hito-2.md`, `docs/decisiones.md`, `docs/rutas.md`.
- Cumplir H2-07 antes de codificar: D2-01…D2-13 en `docs/decisiones.md` y las 15 rutas en `docs/rutas.md`.
- Dependencias: HU-17 (pagos `pendiente` y comprobantes) y HU-20 (`NotificacionService`). **No se puede cerrar CA-18.10 sin HU-20**.
- Migración `2026-11-20-000001_AlterTransaccionHito2.php` aplicada; `transaccion.estado = 'pendiente'` para los pagos nuevos.
- No editar ni eliminar pagos validados ni rechazados.

---

## 2. Prompt específico (copiar y pegar)

```text
Ejecutá ÚNICAMENTE HU-18 (Validación de pagos) del Hito 2 de ServiInmueble 2026.
Respetá el prompt general ya pegado. No implementes HU-17 ni HU-20: consumís sus resultados.

RUTAS PROPIAS
GET  /admin/pagos                | admin.pagos.index   | Admin\PagosController::index        | auth, role:admin
GET  /admin/pagos/(:num)         | admin.pagos.show    | Admin\PagosController::show/$1     | auth, role:admin
POST /admin/pagos/(:num)/validar | admin.pagos.validar | Admin\PagosController::validar/$1   | auth, role:admin
POST /admin/pagos/(:num)/rechazar | admin.pagos.rechazar | Admin\PagosController::rechazar/$1 | auth, role:admin
Ruta compartida con HU-17: GET /pagos/(:num)/comprobante | pagos.comprobante | PagosController::comprobante/$1 | auth.
Parámetros de /admin/pagos: estado, propietario, factura, medio_pago, desde, hasta, page.
Grupo admin con filtros ['auth','role:admin']. Declarar /admin/pagos antes de /admin/pagos/(:num).

CRITERIOS DE ACEPTACIÓN LITERALES (copiar tal cual en la matriz de cierre)
- CA-18.1 Solo el Administrador accede al listado y a las acciones de validación; cualquier otro rol recibe 403.
- CA-18.2 El listado permite filtrar por estado, propietario, factura, medio de pago y rango de fechas. Columnas: fecha de pago, propietario, inmueble, factura, monto, medio, comprobante y estado.
- CA-18.3 El detalle muestra pago, factura, propietario, inmueble y permite previsualizar o descargar el comprobante.
- CA-18.4 Validar: en una transacción, el pago pasa a "validada", se registran el Administrador validador y la fecha, y la factura pasa a "pagada". Si el pago ya fue procesado: "El pago ya fue procesado".
- CA-18.5 Rechazar: exige un motivo de 1 a 255 caracteres; el pago pasa a "rechazada", la factura conserva su estado y el propietario puede cargar un nuevo pago. Sin motivo: "El motivo del rechazo es obligatorio".
- CA-18.6 El comprobante solo lo ven el propietario de la factura y los Administradores; se sirve con el Content-Type y el nombre original, sin ejecución.
- CA-18.7 Los pagos validados son inmutables: no se editan ni se eliminan. Los rechazados se conservan como historial.
- CA-18.8 Sin pagos pendientes: "No hay pagos pendientes de validación".
- CA-18.9 Al validar o rechazar se actualizan las métricas del dashboard.
- CA-18.10 Al validar se genera una notificación interna pago_validado al propietario. Al rechazar se genera pago_rechazado al propietario incluyendo el motivo registrado (HU-20).

CONTRATOS (nombres exactos)
- App\Services\ValidacionPagoService::listado(array $filtros): array
- App\Services\ValidacionPagoService::detalle(int $idTransaccion): ?array
- App\Services\ValidacionPagoService::validar(int $idTransaccion, int $idAdmin): array
- App\Services\ValidacionPagoService::rechazar(int $idTransaccion, int $idAdmin, string $motivo): array
- App\Repositories\PagoRepository (HU-17) reutilizado; agregar métodos de consulta admin y actualización de estado.
- App\Services\NotificacionService (HU-20): registrarPagoValidado(array $transaccion, array $factura): void
  y registrarPagoRechazado(array $transaccion, array $factura, string $motivo): void.

TRANSACCIÓN OBLIGATORIA (CA-18.4/CA-18.5)
transBegin() -> bloquear pago con FOR UPDATE -> si estado <> 'pendiente': transRollback() y "El pago ya fue procesado"
-> actualizar estado='validada', id_admin_validador=$idAdmin, fecha_validacion=ahora (Y-m-d H:i:s)
-> factura.estado='pagada' -> NotificacionService::registrarPagoValidado() -> transCommit().
Rechazo: motivo trim de 1..255 caracteres (si no: "El motivo del rechazo es obligatorio" / si >255: "El motivo del rechazo no puede superar los 255 caracteres");
estado='rechazada', motivo_rechazo=$motivo; factura intacta; registrarPagoRechazado() con el motivo; transCommit().
Cualquier excepción hace transRollback(). Actualizar métricas leyendo el estado real (CA-18.9): no cachear valores del dashboard.

REGLAS
- Solo Admin: filtro role:admin + verificación en el servicio (CA-18.1, DI-08).
- Servir comprobante con Content-Type guardado, Content-Disposition: inline, X-Content-Type-Options: nosniff; nombre original; sin ejecución (CA-18.6, DI-02).
- Ninguna ruta edita/borra pagos validados ni rechazados (CA-18.7).
- Las notificaciones no modifican factura ni pago (H2-03, D2-03). Sin emails.
-medio_pago guardado sin tilde: debito automatico. La UI puede mostrar "débito automático".

ARCHIVOS EXACTOS
Modificar: app/Config/Routes.php, app/Views/layout/sidebar.php, docs/decisiones.md, docs/rutas.md.
Reutilizar sin cambiar su contrato: app/Services/NotificacionService.php y app/Repositories/PagoRepository.php (HU-17/HU-20).
Nuevos: app/Services/ValidacionPagoService.php, app/Controllers/Admin/PagosController.php,
app/Views/admin/pagos/index.php, app/Views/admin/pagos/show.php, tests/feature/AdminPagosTest.php.

DECISIONES APLICABLES
D2-01, D2-03, D2-05, D2-07, D2-09, D2-13 · H2-01, H2-03, H2-04, H2-07 · DI-01, DI-02, DI-08.

MENSAJES LITERALES
"El pago ya fue procesado"
"El motivo del rechazo es obligatorio"
"El motivo del rechazo no puede superar los 255 caracteres"
"No hay pagos pendientes de validación"
"Pago validado correctamente."
"Pago rechazado correctamente."
"No tiene permisos para esta acción" / "Volver al dashboard"
404: "No se encontró el pago solicitado."

VERIFICACIÓN (tests/feature/AdminPagosTest.php)
- Propietario recibe 403 en listado, detalle, validar y rechazar.
- Filtros del listado y columnas requeridas; estado vacío con mensaje literal.
- Detalle con previsualización y descarga del comprobante con headers seguros.
- Validación: transacción, administrador y fecha registrados, factura pagada, notificación pago_validado.
- Doble validación y rechazo posterior de un pago validado: "El pago ya fue procesado".
- Rechazo sin motivo y con motivo >255; factura conserva estado; notificación pago_rechazado con motivo.
- Inmutabilidad: no hay rutas de edición/borrado; rechazados permanecen.
- Dashboard refleja el cambio (CA-18.9) leyendo estado real.
spark routes: 4 rutas admin con nombre, controlador y filtros auth, role:admin.

ENTREGA
Matriz CA-18.1..CA-18.10 -> archivo/test, decisiones aplicadas, comandos y resultado (o NO VERIFICABLE).
```

---

## 3. Trazabilidad por criterio de aceptación

| CA | Implementación | Verificación |
|---|---|---|
| CA-18.1 | Filtro `role:admin` + validación en `ValidacionPagoService` | `AdminPagosTest::testPropietarioRecibe403()` |
| CA-18.2 | `ValidacionPagoService::listado()` + vistas `admin/pagos/index.php` | `AdminPagosTest::testFiltrosYColumnas()` |
| CA-18.3 | `detalle()` + `admin/pagos/show.php` + comprobante compartido | `AdminPagosTest::testDetalleConComprobante()` |
| CA-18.4 | `validar()` con `transBegin/FOR UPDATE/transCommit` | `AdminPagosTest::testValidacionTransaccional()` |
| CA-18.5 | `rechazar()` con motivo 1–255 y estado `rechazada` | `AdminPagosTest::testRechazoConMotivo()/SinMotivo()` |
| CA-18.6 | Headers seguros en `PagoService::comprobante()` | `AdminPagosTest::testComprobanteSeguro()` |
| CA-18.7 | Sin rutas de edición/borrado; rechazados conservados | `AdminPagosTest::testInmutabilidad()` |
| CA-18.8 | Estado vacío del listado | `AdminPagosTest::testSinPendientes()` |
| CA-18.9 | Dashboard lee estado real tras la mutación | `AdminPagosTest::testMetricasSeActualizan()` |
| CA-18.10 | `NotificacionService::registrarPagoValidado/Rechazado()` | `AdminPagosTest::testNotificacionesDePago()` |

---

## 4. Contratos de servicios

```php
namespace App\Services;

final class ValidacionPagoService
{
    public function __construct(
        private ?\App\Repositories\PagoRepository $repo = null,
        private ?NotificacionService $notificaciones = null,
    ) {}

    public function listado(array $filtros): array {}
    public function detalle(int $idTransaccion): ?array {}
    public function validar(int $idTransaccion, int $idAdmin): array {}
    public function rechazar(int $idTransaccion, int $idAdmin, string $motivo): array {}
}
```

---

## 5. Lista exacta de archivos

**Existentes a modificar**

- `app/Config/Routes.php`
- `app/Views/layout/sidebar.php`
- `docs/decisiones.md`
- `docs/rutas.md`

**Reutilizados (contrato sin cambios)**

- `app/Services/NotificacionService.php` (creado en HU-20)
- `app/Repositories/PagoRepository.php` (creado en HU-17)

**Nuevos**

- `app/Services/ValidacionPagoService.php`
- `app/Controllers/Admin/PagosController.php`
- `app/Views/admin/pagos/index.php`
- `app/Views/admin/pagos/show.php`
- `tests/feature/AdminPagosTest.php`

---

## 6. Decisiones aplicables

| ID | Aplicación en HU-18 |
|---|---|
| D2-01 | Solo el Admin valida; hasta entonces el pago es pendiente. |
| D2-03 | Notificaciones internas; nunca emails/push. |
| D2-05 | Alcance global del Admin verificado en servidor. |
| D2-07 | El comprobante se sirve con MIME guardado y nombre original. |
| D2-09 | `pago_validado` y `pago_rechazado` con motivo al Propietario. |
| D2-13 | La validación habilita el cómputo de recaudación del dashboard. |
| H2-01 | La factura pasa a `pagada` solo al validar. |
| H2-03 | Las notificaciones de pago se disparan aquí y se entregan en HU-20. |
| H2-04 | Autorización por rol transversal. |
| H2-07 | D2 y rutas registradas antes de codificar. |
| DI-01 | Idempotencia `pago:{id_transaccion}:{tipo}` en notificaciones. |
| DI-02 | MIME por contenido, `inline` y `nosniff`. |
| DI-08 | Admin global; sin alcance por parámetros manipulados. |

---

## 7. Mensajes literales

- `El pago ya fue procesado`
- `El motivo del rechazo es obligatorio`
- `El motivo del rechazo no puede superar los 255 caracteres`
- `No hay pagos pendientes de validación`
- `Pago validado correctamente.`
- `Pago rechazado correctamente.`
- `No tiene permisos para esta acción` / `Volver al dashboard`
- `No se encontró el pago solicitado.`

---

## 8. Checklist por CA

- [ ] CA-18.1: solo Admin en listado y acciones; otros roles 403.
- [ ] CA-18.2: filtros de estado, propietario, factura, medio y fechas; columnas exactas.
- [ ] CA-18.3: detalle completo y comprobante previsualizable/descargable.
- [ ] CA-18.4: transacción con bloqueo; `validada`, administrador y fecha; factura `pagada`; doble procesamiento rechazado.
- [ ] CA-18.5: motivo 1–255; factura intacta; nuevo pago posible.
- [ ] CA-18.6: comprobante solo propietario/Admin; MIME guardado, nombre original, sin ejecución.
- [ ] CA-18.7: validados inmutables; rechazados conservados.
- [ ] CA-18.8: mensaje literal sin pagos pendientes.
- [ ] CA-18.9: métricas del dashboard actualizadas.
- [ ] CA-18.10: notificaciones `pago_validado`/`pago_rechazado` con motivo; sin emails.
- [ ] Sin intereses, sin pasarela, sin borrado de historial; Hito 1 intacto.

---

## 9. Formato de cierre

- Archivos creados/modificados con ruta completa desde la raíz del repo.
- Rutas implementadas: método + URI + nombre + controlador + filtros.
- Matriz `CA-18.1…CA-18.10` → archivo/test/verificación.
- Decisiones D2/H2/DI aplicadas.
- Comandos ejecutados y resultado (o `NO VERIFICABLE`).
- Pendientes o riesgos.
