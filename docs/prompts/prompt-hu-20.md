# Prompt de implementación · HU-20 · Notificaciones internas

**Versión:** 1.0  
**Fecha:** 2026-11-20  
**Uso:** pegar primero el prompt general (`docs/prompts/prompt-general-hito-2.md`, sección 2) y después el bloque de la sección 2 de este archivo.  
**Matriz maestro:** [`../historias_de_usuario/hito2/trazabilidad-hito-2.md`](../historias_de_usuario/hito2/trazabilidad-hito-2.md).  
**Orden canónico:** 2.º de 7 (`HU-17 → HU-20 → HU-18 → HU-19 → HU-16 → HU-21 → HU-22`).  
**Estado:** sin implementar. Este documento no modifica código ni BD.

---

## 1. Precondiciones

- Leer íntegramente: `docs/historias_de_usuario/hito2/HU-20_compacto.md`, `README.md`, `HU_Hito2_compacto.md`, `trazabilidad-hito-2.md`, `docs/decisiones.md`, `docs/rutas.md`.
- Cumplir H2-07 antes de codificar: D2-01…D2-13 en `docs/decisiones.md` y las 15 rutas en `docs/rutas.md`.
- HU-17 implementada: `transaccion` con `estado`, `id_transaccion` y comprobantes. HU-18 consumirá `NotificacionService` una vez creado.
- Aplicar la migración `2026-11-20-000002_CreateNotificacion.php` antes de las pruebas.
- No existe sistema de notificaciones ni campanita en el código actual: `app/Views/layout/header.php` está sin campanita.

---

## 2. Prompt específico (copiar y pegar)

```text
Ejecutá ÚNICAMENTE HU-20 (Notificaciones internas) del Hito 2 de ServiInmueble 2026.
Respetá el prompt general ya pegado. No envíes emails ni dependas de un cron.

RUTAS PROPIAS (únicas)
GET  /notificaciones            | notificaciones.index      | NotificacionesController::index             | auth
POST /notificaciones/(:num)/leer| notificaciones.leer       | NotificacionesController::marcarLeida/$1     | auth
POST /notificaciones/leer-todas | notificaciones.leer-todas | NotificacionesController::marcarTodasLeidas  | auth
Declarar /notificaciones/leer-todas ANTES de /notificaciones/(:num)/leer.
Parámetros: tipo, leida, page.

CRITERIOS DE ACEPTACIÓN LITERALES (copiar tal cual en la matriz de cierre)
- CA-20.1 Existen cuatro tipos de notificación: por_vencer, vencida, pago_validado y pago_rechazado.
- CA-20.2 por_vencer: factura pendiente con vencimiento aplicable entre hoy y hoy + 7 días inclusive (ventana configurable, 7 días por defecto). vencida: factura pendiente con vencimiento aplicable anterior a hoy.
- CA-20.3 pago_validado: se genera al validar un pago (HU-18). pago_rechazado: se genera al rechazar un pago (HU-18) e incluye el motivo registrado.
- CA-20.4 La generación es idempotente y sin duplicados: como máximo una notificación por usuario, factura y tipo para vencimientos; y como máximo una por usuario, transacción y tipo para pagos.
- CA-20.5 El destinatario es el propietario de la factura. El Administrador puede consultar el listado global en modo lectura para soporte, sin recibir notificaciones personales ni contador de campanita por cada evento.
- CA-20.6 La barra superior muestra una campanita con el contador de notificaciones no leídas del usuario destinatario. El listado se ordena por fecha de creación descendente y pagina.
- CA-20.7 El destinatario puede marcar una notificación o todas como leídas. El Administrador no marca como leídas las notificaciones de terceros en el listado global.
- CA-20.8 Acceder, marcar o consultar una notificación ajena fuera del alcance permitido devuelve 403; una inexistente devuelve 404.
- CA-20.9 Sin notificaciones: "No tenés notificaciones".
- CA-20.10 Las notificaciones de vencimiento se generan al ingresar al dashboard o al listado de facturas, sin depender de un cron. Las de pago se generan al validar o rechazar en HU-18. En el Hito 2 no se envían emails.
- CA-20.11 Cada notificación enlaza a la factura correspondiente; las de pago muestran el estado del pago y, en caso de rechazo, el motivo.

MIGRACIÓN (crear y aplicar)
2026-11-20-000002_CreateNotificacion.php:
- id_notificacion INT AUTO_INCREMENT PK; id_usuario INT NOT NULL FK usuario(id_usuario)
- id_factura INT NOT NULL FK factura(id_factura)
- id_transaccion INT NULL FK transaccion(id_transaccion)
- tipo ENUM('por_vencer','vencida','pago_validado','pago_rechazado') NOT NULL
- mensaje VARCHAR(255) NOT NULL; leida TINYINT(1) NOT NULL DEFAULT 0
- fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
- idempotencia_key VARCHAR(120) NOT NULL; UNIQUE KEY uk_notificacion_idempotencia (id_usuario, idempotencia_key)
- KEY idx_notificacion_usuario_leida (id_usuario, leida, fecha_creacion)
- KEY (id_usuario, id_factura, tipo); KEY (id_usuario, id_transaccion, tipo)
- down() revierte la tabla.
No modificar bd/serviinmueble (1).sql.

CONFIGURACIÓN
app/Config/Hito2.php: public int $diasPorVencer = 7; (DI-03). El servicio lee config('Hito2')->diasPorVencer.

CONTRATOS (nombres exactos)
- App\Services\NotificacionService::registrarVencimientos(?string $hoy = null): int
- App\Services\NotificacionService::registrarPagoValidado(array $transaccion, array $factura): void
- App\Services\NotificacionService::registrarPagoRechazado(array $transaccion, array $factura, string $motivo): void
- App\Services\NotificacionService::listado(array $filtros, int $idUsuario, string $rol): array
- App\Services\NotificacionService::cantidadNoLeidas(int $idUsuario): int
- App\Services\NotificacionService::marcarLeida(int $idNotificacion, int $idUsuario, string $rol): array
- App\Services\NotificacionService::marcarTodasLeidas(int $idUsuario): int
- App\Repositories\NotificacionRepository + App\Models\NotificacionModel (tabla notificacion, useTimestamps=false)

REGLAS
- Claves de idempotencia (DI-01): venc:{id_factura}:{tipo} para vencimientos (id_transaccion NULL);
  pago:{id_transaccion}:{tipo} para pagos. INSERT ... ON DUPLICATE KEY UPDATE no duplica.
- vencimiento aplicable = COALESCE(fecha_segundo_vencimiento, fecha_primer_vencimiento) (DI-07).
- por_vencer: vencimiento en [hoy, hoy + diasPorVencer] inclusive; vencida: vencimiento < hoy. Solo factura.estado='pendiente'.
- Destinatario = propiedad.id_propietario de la factura; nunca session del Admin (D2-09, CA-20.5).
- Disparo idempotente de registrarVencimientos() en DashboardController::index() y FacturasController::index(); sin cron, sin email (CA-20.10).
- Admin: listado global SOLO LECTURA; sin campanita, sin contador, no marca como leídas las de terceros (D2-10, DI-08).
- Propietario: solo sus notificaciones; ajena -> 403; inexistente -> 404 con la vista estándar error_404.php (no se agrega un mensaje nuevo al catálogo).
- POST con CSRF y PRG. listado ordenado por fecha_creacion DESC y paginado.
- Una notificación nunca cambia factura ni pago (H2-03).

ARCHIVOS EXACTOS
Modificar: app/Config/Routes.php, app/Controllers/DashboardController.php, app/Controllers/FacturasController.php,
app/Views/layout/header.php (campanita + contador SOLO Propietario), app/Views/layout/sidebar.php,
docs/decisiones.md, docs/rutas.md.
Nuevos: app/Config/Hito2.php, app/Database/Migrations/2026-11-20-000002_CreateNotificacion.php,
app/Models/NotificacionModel.php, app/Services/NotificacionService.php,
app/Repositories/NotificacionRepository.php, app/Controllers/NotificacionesController.php,
app/Views/notificaciones/index.php, tests/unit/Services/NotificacionServiceTest.php,
tests/feature/NotificacionesTest.php.

DECISIONES APLICABLES
D2-03, D2-05, D2-08, D2-09, D2-10 · H2-03, H2-04, H2-07 · DI-01, DI-03, DI-07, DI-08.

MENSAJES LITERALES
"No tenés notificaciones"
"No tiene permisos para esta acción" / "Volver al dashboard"
Los textos de cada aviso (por_vencer, vencida, pago_validado con estado y pago_rechazado con motivo) se generan en español desde el servicio; no se inventan mensajes de email.

VERIFICACIÓN
tests/unit/Services/NotificacionServiceTest.php: los cuatro tipos, idempotencia por clave, ventana de 7 días configurable y destinatario correcto.
tests/feature/NotificacionesTest.php: campanita/contador solo Propietario; orden DESC y paginación; marcar una y todas;
ajena 403 e inexistente 404; estado vacío con mensaje literal; Admin en modo lectura sin campanita; ningún email enviado.
spark routes: 3 rutas con nombre, controlador y filtro auth; leer-todas declarada antes que (:num)/leer.

ENTREGA
Matriz CA-20.1..CA-20.11 -> archivo/test, decisiones aplicadas, comandos y resultado (o NO VERIFICABLE).
```

---

## 3. Trazabilidad por criterio de aceptación

| CA | Implementación | Verificación |
|---|---|---|
| CA-20.1 | `tipo` ENUM en la migración 000002 | `NotificacionServiceTest::testCuatroTipos()` |
| CA-20.2 | `registrarVencimientos()` + `config('Hito2')->diasPorVencer` | `NotificacionServiceTest::testVentanaPorVencer()/Vencida()` |
| CA-20.3 | `registrarPagoValidado()/Rechazado()` consumidos por HU-18 | `NotificacionServiceTest::testNotificacionesDePago()` |
| CA-20.4 | DI-01 con `idempotencia_key` única | `NotificacionServiceTest::testIdempotencia()` |
| CA-20.5 | Destinatario desde `propiedad.id_propietario` | `NotificacionesTest::testAdminSinCampanita()` |
| CA-20.6 | Contador en `layout/header.php` + listado paginado DESC | `NotificacionesTest::testCampanitaYPaginacion()` |
| CA-20.7 | `marcarLeida()/marcarTodasLeidas()` con DI-08 | `NotificacionesTest::testMarcarLeidas()` |
| CA-20.8 | 403/404 en el servicio y controlador | `NotificacionesTest::testAjena403Inexistente404()` |
| CA-20.9 | Estado vacío del listado | `NotificacionesTest::testSinNotificaciones()` |
| CA-20.10 | Disparo en Dashboard/Facturas; sin cron ni email | `NotificacionesTest::testDisparoSinCron()` |
| CA-20.11 | Enlace a la factura y detalle de pago/motivo en la vista | `NotificacionesTest::testEnlaceYDetalle()` |

---

## 4. Contratos de servicios y repositorios

```php
namespace App\Services;

final class NotificacionService
{
    public function registrarVencimientos(?string $hoy = null): int {}
    public function registrarPagoValidado(array $transaccion, array $factura): void {}
    public function registrarPagoRechazado(array $transaccion, array $factura, string $motivo): void {}
    public function listado(array $filtros, int $idUsuario, string $rol): array {}
    public function cantidadNoLeidas(int $idUsuario): int {}
    public function marcarLeida(int $idNotificacion, int $idUsuario, string $rol): array {}
    public function marcarTodasLeidas(int $idUsuario): int {}
}
```

```php
namespace App\Repositories;

final class NotificacionRepository
{
    public function insertarIdempotente(array $datos): bool {}
    public function listar(array $filtros, ?int $idPropietario): array {}
    public function contarNoLeidas(int $idUsuario): int {}
    public function marcarLeida(int $idNotificacion, int $idUsuario): bool {}
    public function marcarTodasLeidas(int $idUsuario): int {}
}
```

---

## 5. Lista exacta de archivos

**Existentes a modificar**

- `app/Config/Routes.php`
- `app/Controllers/DashboardController.php`
- `app/Controllers/FacturasController.php`
- `app/Views/layout/header.php`
- `app/Views/layout/sidebar.php`
- `docs/decisiones.md`
- `docs/rutas.md`

**Nuevos**

- `app/Config/Hito2.php`
- `app/Database/Migrations/2026-11-20-000002_CreateNotificacion.php`
- `app/Models/NotificacionModel.php`
- `app/Services/NotificacionService.php`
- `app/Repositories/NotificacionRepository.php`
- `app/Controllers/NotificacionesController.php`
- `app/Views/notificaciones/index.php`
- `tests/unit/Services/NotificacionServiceTest.php`
- `tests/feature/NotificacionesTest.php`

---

## 6. Decisiones aplicables

| ID | Aplicación en HU-20 |
|---|---|
| D2-03 | Notificaciones internas, sin emails ni mensajes externos. |
| D2-05 | Destinatario y alcance por rol en servidor. |
| D2-08 | Ventana de 7 días corridos configurable. |
| D2-09 | Cuatro tipos; rechazo incluye motivo. |
| D2-10 | Admin en lectura, sin campanita ni avisos propios. |
| H2-03 | Las notificaciones de pago se disparan en HU-18, se entregan acá. |
| H2-04 | Autorización transversal por rol. |
| H2-07 | D2 y rutas registradas antes de codificar. |
| DI-01 | `idempotencia_key` con claves `venc:` y `pago:`. |
| DI-03 | `app/Config/Hito2.php` con `diasPorVencer = 7`. |
| DI-07 | Vencimiento aplicable con `COALESCE`. |
| DI-08 | Admin global en lectura; Propietario solo lo propio. |

---

## 7. Mensajes literales

- `No tenés notificaciones`
- `No tiene permisos para esta acción` / `Volver al dashboard`
- 404: se usa la vista estándar `app/Views/errors/html/error_404.php`; no se incorpora un mensaje nuevo al catálogo.

---

## 8. Checklist por CA

- [ ] CA-20.1: cuatro tipos definidos en ENUM y servicio.
- [ ] CA-20.2: `por_vencer` en [hoy, hoy+7] inclusive y `vencida` anterior a hoy; configurable.
- [ ] CA-20.3: pagos validados/rechazados generan aviso; rechazo con motivo.
- [ ] CA-20.4: sin duplicados por usuario+factura+tipo y usuario+transacción+tipo.
- [ ] CA-20.5: destinatario es el propietario; Admin sin avisos personales.
- [ ] CA-20.6: campanita con contador y listado DESC paginado.
- [ ] CA-20.7: marcar una/todas; Admin no marca las de terceros.
- [ ] CA-20.8: ajena 403; inexistente 404 con vista estándar.
- [ ] CA-20.9: `No tenés notificaciones`.
- [ ] CA-20.10: disparo en dashboard/facturas; sin cron ni email.
- [ ] CA-20.11: enlace a la factura; pagos muestran estado y motivo de rechazo.
- [ ] Sin emails, sin push, sin modificar estado de factura/pago.

---

## 9. Formato de cierre

- Archivos creados/modificados con ruta completa desde la raíz del repo.
- Rutas implementadas: método + URI + nombre + controlador + filtros.
- Matriz `CA-20.1…CA-20.11` → archivo/test/verificación.
- Decisiones D2/H2/DI aplicadas.
- Comandos ejecutados y resultado (o `NO VERIFICABLE`).
- Pendientes o riesgos.
