# Prompt de implementación · HU-17 · Registro de pago con comprobante digital

**Versión:** 1.0  
**Fecha:** 2026-11-20  
**Uso:** pegar primero el prompt general (`docs/prompts/prompt-general-hito-2.md`, sección 2) y después el bloque de la sección 2 de este archivo.  
**Matriz maestro:** [`../historias_de_usuario/hito2/trazabilidad-hito-2.md`](../historias_de_usuario/hito2/trazabilidad-hito-2.md).  
**Orden canónico:** 1.º de 7 (`HU-17 → HU-20 → HU-18 → HU-19 → HU-16 → HU-21 → HU-22`).  
**Estado:** sin implementar. Este documento no modifica código ni BD.

---

## 1. Precondiciones

- Leer íntegramente: `docs/historias_de_usuario/hito2/HU-17_compacto.md`, `README.md`, `HU_Hito2_compacto.md`, `trazabilidad-hito-2.md`, `docs/decisiones.md`, `docs/rutas.md`.
- Cumplir H2-07 antes de codificar: D2-01…D2-13 en `docs/decisiones.md` y las 15 rutas en `docs/rutas.md`.
- Sanear el working tree (V-12) y ejecutar `SHOW VARIABLES LIKE 'max_allowed_packet';` con BD activa (V-02) antes de probar comprobantes; si no conecta, reportar `NO VERIFICABLE`.
- Base Hito 1 vigente: `transaccion` sin campos de validación; `factura.estado` es `pendiente|pagada`; HU-08/09/12 integradas; suite `124 tests, 773 assertions; OK`; throttle 900 s.
- No modificar `bd/serviinmueble (1).sql`: la extensión se hace solo con la migración `2026-11-20-000001_AlterTransaccionHito2.php`.

---

## 2. Prompt específico (copiar y pegar)

```text
Ejecutá ÚNICAMENTE HU-17 (Registro de pago con comprobante digital) del Hito 2 de ServiInmueble 2026.
Respetá el prompt general ya pegado. No implementes HU-18 (validación) ni HUs fuera de alcance.

RUTAS PROPIAS (únicas)
GET  /facturas/(:num)/pagar    | pagos.create      | PagosController::create/$1      | auth
POST /facturas/(:num)/pagos    | pagos.store       | PagosController::store/$1        | auth
GET  /pagos                    | pagos.index       | PagosController::index           | auth
GET  /pagos/(:num)/comprobante | pagos.comprobante | PagosController::comprobante/$1  | auth
Parámetros de /pagos: estado, desde, hasta, page. Declarar /pagos antes de /pagos/(:num)/comprobante.

CRITERIOS DE ACEPTACIÓN LITERALES (copiar tal cual en la matriz de cierre)
- CA-17.1 Puede registrar el pago el propietario de la factura o un Administrador; cualquier otro usuario recibe 403.
- CA-17.2 Solo se admite sobre facturas en estado pendiente o vencida (calculado). Si la factura está pagada: "La factura ya está pagada".
- CA-17.3 monto_abonado debe ser igual a monto_total; si no: "El monto debe ser igual al total de la factura". No se admiten pagos parciales.
- CA-17.4 fecha_pago es obligatoria, válida y no futura: "La fecha de pago no puede ser futura".
- CA-17.5 medio_pago es obligatorio y debe ser uno de {transferencia, débito automático, efectivo, billetera virtual}. numero_comprobante es obligatorio, alfanumérico, de 1 a 50 caracteres y único por factura.
- CA-17.6 El comprobante es obligatorio para transferencia, débito automático y billetera virtual; es opcional para efectivo. Si se adjunta, el formato debe ser PDF, JPG o JPEG, máximo 5 MB, validado por contenido. Errores: "El comprobante debe ser PDF, JPG o JPEG" y "El comprobante no puede superar los 5 MB".
- CA-17.7 Se guarda en_mora = 1 si fecha_pago es posterior al vencimiento aplicable (2.º si existe; si no, 1.º); en caso contrario, 0.
- CA-17.8 El pago se crea en estado "pendiente de validación" y la factura sigue pendiente. Si ya existe un pago pendiente para la factura: "Ya existe un pago pendiente de validación para esta factura".
- CA-17.9 La operación usa CSRF y patrón PRG; éxito: "Pago registrado. Queda pendiente de validación."
- CA-17.10 El comprobante no es accesible públicamente; solo el propietario de la factura o un Administrador pueden verlo o descargarlo.

MIGRACIÓN (crear y aplicar; no tocar el SQL canónico)
2026-11-20-000001_AlterTransaccionHito2.php:
- estado ENUM('pendiente','validada','rechazada') NOT NULL DEFAULT 'pendiente'
- id_admin_validador INT NULL FK usuario(id_usuario)
- fecha_validacion DATETIME NULL, motivo_rechazo VARCHAR(255) NULL
- comprobante_nombre VARCHAR(255) NULL, comprobante_mime VARCHAR(100) NULL
- UNIQUE KEY uk_transaccion_factura_comprobante (id_factura, numero_comprobante)
- KEY idx_transaccion_estado_pago (estado, fecha_pago)
- Backfill DI-04: filas existentes -> estado='validada', fecha_validacion=CONCAT(fecha_pago,' 00:00:00')
- down() revierte columnas e índices.
Comprobante digital ya existe como MEDIUMBLOB; no agrandar columnas.

CONTRATOS (nombres exactos)
- App\Services\PagoService::opcionesPago(int $idFactura, int $idUsuario, string $rol): array
- App\Services\PagoService::registrar(int $idFactura, array $entrada, int $idUsuario, string $rol): array
- App\Services\PagoService::listadoPropio(array $filtros, int $idUsuario): array
- App\Services\PagoService::comprobante(int $idTransaccion, int $idUsuario, string $rol): array
- App\Services\ComprobanteService::validar(?array $archivo, string $medioPago): array
  Devuelve ['nombre'=>string,'mime'=>string,'contenido'=>string]|null segun corresponda.
- App\Services\MoraService::vencimientoAplicable(array $factura): string
- App\Services\MoraService::esPagoTardio(array $factura, string $fechaPago): bool
- App\Repositories\PagoRepository::buscarFacturaParaPago(int $id, bool $bloquear = false): ?array
- App\Repositories\PagoRepository::existePendiente(int $idFactura): bool
- App\Repositories\PagoRepository::existeNumeroComprobante(int $idFactura, string $numero): bool
- App\Repositories\PagoRepository::crear(array $datos): int
- App\Repositories\PagoRepository::listarPropio(array $filtros, int $idPropietario): array
- App\Repositories\PagoRepository::buscarComprobante(int $id): ?array

REGLAS DE NEGOCIO
- id_propietario se toma de propiedad.id_propietario, NUNCA del POST (DI-08).
- Propietario solo sus facturas; Admin puede pagar cualquier factura; otro rol/ajeno -> 403 (CA-17.1).
- Estado calculado con COALESCE(fecha_segundo_vencimiento, fecha_primer_vencimiento) (DI-07, D-16).
- Igualdad de montos con number_format((float)$valor, 2, '.', '') en ambos lados (DI-05).
- medio_pago guardado SIN tilde: transferencia | debito automatico | efectivo | billetera virtual. La etiqueta de UI puede decir "débito automático".
- Comprobante obligatorio salvo efectivo. MIME detectado por contenido con finfo: application/pdf, image/jpeg. Límite 5 * 1024 * 1024 bytes (DI-02).
- numero_comprobante: alfanumérico de 1 a 50 caracteres, único por factura (D2-11).
- Alta con estado='pendiente'; la factura NO cambia de estado (D2-01, H2-01). en_mora calculado al registrar (H2-02).
- Un pago pendiente por factura; rechazados y validados no se editan ni borran.
- Servir comprobante con Content-Type guardado, Content-Disposition: inline, X-Content-Type-Options: nosniff y sin ejecución (DI-02). Nunca acceso público.

ARCHIVOS EXACTOS
Modificar: app/Config/Routes.php, app/Models/TransaccionModel.php (allowedFields con estado,
id_admin_validador, fecha_validacion, motivo_rechazo, comprobante_nombre, comprobante_mime),
app/Database/Seeds/DemoSeeder.php (estado explícito, DI-04), app/Views/layout/sidebar.php,
app/Helpers/format_helper.php (etiquetas de estado de pago), docs/decisiones.md, docs/rutas.md.
Nuevos: app/Database/Migrations/2026-11-20-000001_AlterTransaccionHito2.php,
app/Services/PagoService.php, app/Services/ComprobanteService.php, app/Services/MoraService.php,
app/Repositories/PagoRepository.php, app/Controllers/PagosController.php,
app/Views/pagos/create.php, app/Views/pagos/index.php,
tests/database/Hito2SchemaTest.php, tests/unit/Services/ComprobanteServiceTest.php,
tests/feature/PagosTest.php.

DECISIONES APLICABLES
D2-01, D2-02, D2-05, D2-07, D2-11 · H2-01, H2-02, H2-04, H2-07 · DI-02, DI-04, DI-05, DI-07, DI-08 · D-16.
P-01 del Hito 1 queda superado solo aquí; P-05 se mantiene: no hay pagos parciales.

MENSAJES LITERALES (copiar sin cambiar tildes ni puntos)
"La factura ya está pagada"
"El monto debe ser igual al total de la factura"
"La fecha de pago no puede ser futura"
"El número de comprobante es obligatorio"
"El número de comprobante debe ser alfanumérico de 1 a 50 caracteres"
"Ya existe un comprobante con ese número para esta factura"
"El comprobante debe ser PDF, JPG o JPEG"
"El comprobante no puede superar los 5 MB"
"El comprobante es obligatorio para este medio de pago"
"Ya existe un pago pendiente de validación para esta factura"
"Pago registrado. Queda pendiente de validación."
"No tiene permisos para esta acción" / "Volver al dashboard"
404: "No se encontró la factura solicitada." / "No se encontró el pago solicitado."

VERIFICACIÓN
tests/database/Hito2SchemaTest.php: columnas, índice único y backfill DI-04.
tests/unit/Services/ComprobanteServiceTest.php: MIME por contenido, extensión engañosa rechazada, límite 5 MiB, efectivo sin archivo.
tests/feature/PagosTest.php: CA-17.1 a CA-17.10, CSRF y PRG, factura ajena 403, factura pagada, monto distinto, fecha futura, duplicados, en_mora, descarga autorizada y sin ejecución.
spark routes: las 4 rutas con nombre, controlador y filtro auth.

ENTREGA
Matriz CA-17.1..CA-17.10 -> archivo/test, decisiones aplicadas, comandos y resultado (o NO VERIFICABLE).
```

---

## 3. Trazabilidad por criterio de aceptación

| CA | Implementación | Verificación |
|---|---|---|
| CA-17.1 | `PagoService::registrar()` + DI-08; filtro `auth` | `PagosTest::testSoloPropietarioOAdminRegistra()` |
| CA-17.2 | `PagoRepository::buscarFacturaParaPago()` + `estadoCalculado()` | `PagosTest::testFacturaPagadaRechazaPago()` |
| CA-17.3 | `PagoService::registrar()` + DI-05 | `PagosTest::testMontoDebeSerTotal()` |
| CA-17.4 | Validación de `fecha_pago` en el servicio | `PagosTest::testFechaFuturaRechazada()` |
| CA-17.5 | Validación de `medio_pago` y `numero_comprobante` + D2-11 | `PagosTest::testMedioYNumeroComprobante()` |
| CA-17.6 | `ComprobanteService::validar()` + DI-02 | `ComprobanteServiceTest`, `PagosTest::testComprobanteInvalido()` |
| CA-17.7 | `MoraService::esPagoTardio()` + H2-02 | `PagosTest::testPagoTardioMarcaEnMora()` |
| CA-17.8 | `PagoRepository::existePendiente()` + H2-01 | `PagosTest::testPagoPendienteDuplicado()` |
| CA-17.9 | `PagosController::store()` con CSRF y PRG | `PagosTest::testCsrfPrgYMensajeExito()` |
| CA-17.10 | `PagoService::comprobante()` con control de propietario/Admin | `PagosTest::testComprobanteSoloAutorizado()` |

---

## 4. Contratos de servicios y repositorios

```php
namespace App\Services;

final class PagoService
{
    public function __construct(
        private ?\App\Repositories\PagoRepository $repo = null,
        private ?ComprobanteService $comprobantes = null,
        private ?MoraService $mora = null,
    ) {}

    public function opcionesPago(int $idFactura, int $idUsuario, string $rol): array {}
    public function registrar(int $idFactura, array $entrada, int $idUsuario, string $rol): array {}
    public function listadoPropio(array $filtros, int $idUsuario): array {}
    public function comprobante(int $idTransaccion, int $idUsuario, string $rol): array {}
}

final class ComprobanteService
{
    /** @return array{nombre: string, mime: string, contenido: string}|null */
    public function validar(?array $archivo, string $medioPago): array {}
}

final class MoraService
{
    public function vencimientoAplicable(array $factura): string {}
    public function esPagoTardio(array $factura, string $fechaPago): bool {}
}
```

```php
namespace App\Repositories;

final class PagoRepository
{
    public function buscarFacturaParaPago(int $id, bool $bloquear = false): ?array {}
    public function existePendiente(int $idFactura): bool {}
    public function existeNumeroComprobante(int $idFactura, string $numero): bool {}
    public function crear(array $datos): int {}
    public function listarPropio(array $filtros, int $idPropietario): array {}
    public function buscarComprobante(int $id): ?array {}
}
```

---

## 5. Lista exacta de archivos

**Existentes a modificar**

- `app/Config/Routes.php`
- `app/Models/TransaccionModel.php`
- `app/Database/Seeds/DemoSeeder.php`
- `app/Views/layout/sidebar.php`
- `app/Helpers/format_helper.php`
- `docs/decisiones.md`
- `docs/rutas.md`

**Nuevos**

- `app/Database/Migrations/2026-11-20-000001_AlterTransaccionHito2.php`
- `app/Services/PagoService.php`
- `app/Services/ComprobanteService.php`
- `app/Services/MoraService.php`
- `app/Repositories/PagoRepository.php`
- `app/Controllers/PagosController.php`
- `app/Views/pagos/create.php`
- `app/Views/pagos/index.php`
- `tests/database/Hito2SchemaTest.php`
- `tests/unit/Services/ComprobanteServiceTest.php`
- `tests/feature/PagosTest.php`

---

## 6. Decisiones aplicables

| ID | Aplicación en HU-17 |
|---|---|
| D2-01 | Pago queda pendiente de validación; factura no cambia. |
| D2-02 | Pago exacto por el total; sin parciales. |
| D2-05 | Admin global / Propietario propio, en servidor. |
| D2-07 | Comprobante salvo efectivo; PDF/JPG/JPEG ≤ 5 MB por contenido. |
| D2-11 | `numero_comprobante` único por factura. |
| H2-01 | Estado intermedio en `transaccion`. |
| H2-02 | `en_mora` calculado al registrar. |
| H2-04 | Autorización transversal por rol. |
| H2-07 | D2 y rutas registradas antes de codificar. |
| DI-02 | BLOB + MIME por contenido + descarga segura. |
| DI-04 | Backfill de pagos históricos a `validada`. |
| DI-05 | Comparación de montos normalizada a 2 decimales. |
| DI-07 | Vencimiento aplicable con `COALESCE`. |
| DI-08 | `id_propietario` desde `propiedad`, nunca del POST. |
| D-16 | “Vencida” calculada; `factura.estado` sin cambios. |

---

## 7. Mensajes literales

- `La factura ya está pagada`
- `El monto debe ser igual al total de la factura`
- `La fecha de pago no puede ser futura`
- `El número de comprobante es obligatorio`
- `El número de comprobante debe ser alfanumérico de 1 a 50 caracteres`
- `Ya existe un comprobante con ese número para esta factura`
- `El comprobante debe ser PDF, JPG o JPEG`
- `El comprobante no puede superar los 5 MB`
- `El comprobante es obligatorio para este medio de pago`
- `Ya existe un pago pendiente de validación para esta factura`
- `Pago registrado. Queda pendiente de validación.`
- `No tiene permisos para esta acción` / `Volver al dashboard`
- `No se encontró la factura solicitada.` / `No se encontró el pago solicitado.`

---

## 8. Checklist por CA

- [ ] CA-17.1: propietario de la factura o Admin; cualquier otro, 403.
- [ ] CA-17.2: solo pendiente/vencida calculada; pagada rechaza con mensaje literal.
- [ ] CA-17.3: `monto_abonado == monto_total` con normalización DI-05; sin parciales.
- [ ] CA-17.4: `fecha_pago` válida y no futura.
- [ ] CA-17.5: medio de pago exacto (sin tilde en BD) y número alfanumérico 1–50 único por factura.
- [ ] CA-17.6: obligatorio salvo efectivo; MIME por contenido y límite 5 MiB.
- [ ] CA-17.7: `en_mora = 1` si `fecha_pago` posterior al vencimiento aplicable.
- [ ] CA-17.8: alta `pendiente`, factura intacta, un solo pago pendiente por factura.
- [ ] CA-17.9: CSRF + PRG + mensaje de éxito literal.
- [ ] CA-17.10: comprobante solo para propietario/Admin, con headers seguros.
- [ ] Sin emails, sin pasarela real, sin pagos parciales; Hito 1 intacto.

---

## 9. Formato de cierre

- Archivos creados/modificados con ruta completa desde la raíz del repo.
- Rutas implementadas: método + URI + nombre + controlador + filtros.
- Matriz `CA-17.1…CA-17.10` → archivo/test/verificación.
- Decisiones D2/H2/DI aplicadas.
- Comandos ejecutados y resultado (o `NO VERIFICABLE`).
- Pendientes o riesgos.
