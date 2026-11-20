# Prompt de implementación · HU-16 · Dashboard de métricas globales

**Versión:** 1.0  
**Fecha:** 2026-11-20  
**Uso:** pegar primero el prompt general (`docs/prompts/prompt-general-hito-2.md`, sección 2) y después el bloque de la sección 2 de este archivo.  
**Matriz maestro:** [`../historias_de_usuario/hito2/trazabilidad-hito-2.md`](../historias_de_usuario/hito2/trazabilidad-hito-2.md).  
**Orden canónico:** 6.º de 7 (`HU-17 → HU-20 → HU-18 → HU-19 → HU-16 → HU-21 → HU-22`).  
**Estado:** sin implementar. Este documento no modifica código ni BD.

---

## 1. Precondiciones

- Leer íntegramente: `docs/historias_de_usuario/hito2/HU-16_compacto.md`, `README.md`, `HU_Hito2_compacto.md`, `trazabilidad-hito-2.md`, `docs/decisiones.md`, `docs/rutas.md`.
- Cumplir H2-07 antes de codificar: D2-01…D2-13 en `docs/decisiones.md` y las 15 rutas en `docs/rutas.md`.
- Dependencias ya implementadas: HU-17 (`transaccion.estado`), HU-18 (validación), HU-19 (`en_mora`/vencimientos), HU-20 (`notificacion`).
- Migraciones aplicadas: `2026-11-20-000001`, `000002` y `000003`.
- Chart.js local ya vendorizado: `public/assets/plugins/chart.js/chart.umd.min.js` (DI-06).
- La ruta `GET /dashboard` ya existe desde Hito 1; **se amplía**, no se crea una nueva.
- Working tree saneado y `serviinmueble_test` disponible; si MariaDB no conecta, reportar `NO VERIFICABLE`.

---

## 2. Prompt específico (copiar y pegar)

```text
Ejecutá ÚNICAMENTE HU-16 (Dashboard de métricas globales) del Hito 2 de ServiInmueble 2026.
Respetá el prompt general ya pegado. No implementes otras HUs ni reabras Hito 1.

RUTA PROPIA (única)
GET /dashboard | nombre: dashboard | DashboardController::index | filtro: auth
La URI ya existe desde Hito 1: ampliar el controlador y la vista, no agregar otra ruta.

CRITERIOS DE ACEPTACIÓN LITERALES (copiar tal cual en la matriz de cierre)
- CA-16.1 El panel del Administrador muestra: usuarios activos (total, administradores y propietarios), inmuebles activos, facturas pendientes (cantidad y monto), facturas vencidas (cantidad y monto), pagos pendientes de validación (cantidad) y recaudación del mes en curso (suma de pagos validados con fecha de pago del mes).
- CA-16.2 El panel del Propietario muestra las mismas métricas, excepto usuarios, limitadas a sus inmuebles: inmuebles activos, facturas pendientes y vencidas (cantidad y monto), pagos pendientes de validación, recaudación del mes y notificaciones sin leer.
- CA-16.3 Gráfico 1: evolución de los últimos 12 meses con dos series, facturación emitida (suma de monto_total por fecha_emision) y cobranza (suma de monto_abonado de pagos validados por fecha_pago).
- CA-16.4 Gráfico 2: distribución de facturas por estado calculado (pendiente, vencida, pagada) del año en curso.
- CA-16.5 Todo cálculo se filtra en el servidor por rol: el Propietario nunca recibe datos de inmuebles ajenos, aunque manipule parámetros de la URL.
- CA-16.6 Cada tarjeta enlaza al listado correspondiente con el filtro aplicado (facturas pendientes, vencidas, pagos pendientes, etc.).
- CA-16.7 Si no hay datos, las tarjetas muestran 0 y los gráficos muestran "Sin datos" sin errores.
- CA-16.8 Los importes se muestran con separador de miles y 2 decimales; los porcentajes con 1 decimal.

CONTRATOS (nombres exactos)
- App\Services\DashboardService::metricas(int $idUsuario, string $rol): array
  Devuelve: tarjetas, serie_mensual, distribucion_estados, enlaces.
- App\Repositories\DashboardRepository con:
  usuariosActivos(): array ['total'=>int,'admin'=>int,'propietario'=>int]
  inmueblesActivos(?int $idPropietario): int
  facturasPorEstadoCalculado(?int $idPropietario, int $anio): array
  pagosPendientes(?int $idPropietario): int
  recaudacionMes(?int $idPropietario, string $desde, string $hasta): float
  serieMensual(?int $idPropietario, string $desde, string $hasta): array
  noLeidas(int $idUsuario): int

REGLAS DE CÁLCULO
- Admin: $idPropietario = null (alcance global). Propietario: $idPropietario = $idUsuario; filtrar siempre por propiedad.id_propietario desde el servidor (D2-05, DI-08, CA-16.5).
- "inmuebles activos" = propiedad.estado <> 'inactivo'.
- Estado calculado con FacturaService::estadoCalculado() y COALESCE(fecha_segundo_vencimiento, fecha_primer_vencimiento) (DI-07, D-16). Nunca guardar "vencida"; factura.estado sigue pendiente|pagada.
- pagos pendientes = transaccion.estado = 'pendiente'.
- recaudación = SUM(monto_abonado) WHERE transaccion.estado = 'validada' AND fecha_pago en el mes en curso (D2-13).
- serie 12 meses: emitido = SUM(factura.monto_total) por fecha_emision; cobrado = SUM(transaccion.monto_abonado) validadas por fecha_pago. Meses sin datos valen 0.
- distribución: pendiente, vencida, pagada sobre facturas del año en curso (periodo_anio = año actual) con el estado calculado.
- tarjetas sin datos = 0; gráficos sin datos = "Sin datos".

ENLACES DE TARJETAS (CA-16.6, rutas existentes del Hito 1/2)
- pendientes → /facturas?estado=pendiente
- vencidas → /facturas?estado=vencida
- pagos pendientes → /pagos?estado=pendiente (Admin además /admin/pagos?estado=pendiente)
- mora → /mora
- notificaciones → /notificaciones?leida=0

FORMATOS (CA-16.8)
- formato_moneda(): ARS con separador de miles y 2 decimales.
- formato_fecha(): dd/mm/aaaa.
- Agregar formato_porcentaje(float $valor): string con 1 decimal en app/Helpers/format_helper.php.

ARCHIVOS EXACTOS
Modificar: app/Config/Routes.php (solo verificar la ruta existente), app/Controllers/DashboardController.php,
app/Views/dashboard/index.php, app/Views/layout/main.php, app/Views/layout/sidebar.php,
app/Helpers/format_helper.php, docs/decisiones.md, docs/rutas.md.
Nuevos: app/Services/DashboardService.php, app/Repositories/DashboardRepository.php,
tests/feature/DashboardMetricasTest.php.

DECISIONES APLICABLES
D2-01, D2-05, D2-06, D2-13 · H2-01, H2-02, H2-04, H2-05, H2-07 · DI-06, DI-07, DI-08 · D-16.
Chart.js se carga desde public/assets/plugins/chart.js/chart.umd.min.js en layout/main.php antes de renderSection('scripts'); sin CDN.

MENSAJES LITERALES
"Sin datos" para gráficos vacíos. Sin permiso: "No tiene permisos para esta acción", botón "Volver al dashboard".

VERIFICACIÓN (tests/feature/DashboardMetricasTest.php)
- Admin ve métricas globales; Propietario ve solo sus inmuebles.
- Manipular ?inmueble= ajeno: el service ignora el parámetro y no filtra datos ajenos (CA-16.5).
- Serie de 12 meses con emitido/cobrado y meses en 0; distribución del año.
- Sin datos: tarjetas 0 y gráficos "Sin datos".
- Formatos: moneda con 2 decimales, porcentaje con 1 decimal.
- spark routes: GET /dashboard con nombre dashboard y filtro auth.

ENTREGA
Matriz CA-16.1..CA-16.8 -> archivo/test, decisiones aplicadas, comandos y resultado (o NO VERIFICABLE).
```

---

## 3. Trazabilidad por criterio de aceptación

| CA | Implementación | Verificación |
|---|---|---|
| CA-16.1 | `DashboardService::metricas()` + `DashboardRepository` (usuarios, inmuebles, facturas, pagos, recaudación) | `DashboardMetricasTest::testAdminVeMetricasGlobales()` |
| CA-16.2 | Id., con `$idPropietario = $idUsuario` y `noLeidas()` | `DashboardMetricasTest::testPropietarioVeSoloSusMetricas()` |
| CA-16.3 | `serieMensual()` + gráfico 1 en `app/Views/dashboard/index.php` | `DashboardMetricasTest::testSerieDoceMesesEmitidoVsCobrado()` |
| CA-16.4 | `facturasPorEstadoCalculado()` + gráfico 2 | `DashboardMetricasTest::testDistribucionEstadosAnioActual()` |
| CA-16.5 | DI-08 en el servicio; la vista no decide alcance | `DashboardMetricasTest::testManipulacionQueryNoAmpliaAlcance()` |
| CA-16.6 | Clave `enlaces` del servicio + tarjetas en la vista | `DashboardMetricasTest::testTarjetasEnlazanConFiltro()` |
| CA-16.7 | Valores 0 y texto `Sin datos` | `DashboardMetricasTest::testEstadoVacio()` |
| CA-16.8 | `formato_moneda()`, `formato_fecha()`, `formato_porcentaje()` | `DashboardMetricasTest::testFormatos()` |

---

## 4. Contratos de servicios y repositorios

```php
namespace App\Services;

final class DashboardService
{
    public function __construct(
        private ?\App\Repositories\DashboardRepository $repo = null,
    ) {}

    /** @return array{tarjetas: array, serie_mensual: array, distribucion_estados: array, enlaces: array} */
    public function metricas(int $idUsuario, string $rol): array {}
}
```

```php
namespace App\Repositories;

final class DashboardRepository
{
    /** @return array{total: int, admin: int, propietario: int} */
    public function usuariosActivos(): array {}

    public function inmueblesActivos(?int $idPropietario): int {}

    /** @return array<string, array{cantidad: int, monto: float}> */
    public function facturasPorEstadoCalculado(?int $idPropietario, int $anio): array {}

    public function pagosPendientes(?int $idPropietario): int {}

    public function recaudacionMes(?int $idPropietario, string $desde, string $hasta): float {}

    /** @return list<array{mes: string, emitido: float, cobrado: float}> */
    public function serieMensual(?int $idPropietario, string $desde, string $hasta): array {}

    public function noLeidas(int $idUsuario): int {}
}
```

---

## 5. Lista exacta de archivos

**Existentes a modificar**

- `app/Config/Routes.php` (verificar `GET /dashboard`, sin cambios de URI)
- `app/Controllers/DashboardController.php`
- `app/Views/dashboard/index.php`
- `app/Views/layout/main.php`
- `app/Views/layout/sidebar.php`
- `app/Helpers/format_helper.php`
- `docs/decisiones.md`
- `docs/rutas.md`

**Nuevos**

- `app/Services/DashboardService.php`
- `app/Repositories/DashboardRepository.php`
- `tests/feature/DashboardMetricasTest.php`

---

## 6. Decisiones aplicables

| ID | Aplicación en HU-16 |
|---|---|
| D2-01 | Los pagos pendientes de validación cuentan como pendientes; no suman a recaudación. |
| D2-05 | Alcance Admin global / Propietario propio en servidor. |
| D2-06 | Chart.js local, sin CDN. |
| D2-13 | Recaudación por `fecha_pago` de pagos validados. |
| H2-01 | Estado intermedio de `transaccion` reflejado en las tarjetas. |
| H2-02 | Mora de facturas pendientes calculada; `en_mora` es histórico de pagos. |
| H2-04 | Autorización transversal por rol. |
| H2-05 | Chart.js no altera los CA de los gráficos. |
| H2-07 | Rutas y decisiones registradas antes de codificar. |
| DI-06 | Chart.js v4.4.7 en `public/assets/plugins/chart.js/chart.umd.min.js`. |
| DI-07 | Reutilizar `FacturaService::estadoCalculado()`. |
| DI-08 | Alcance resuelto en el servicio. |
| D-16 | “Vencida” calculada, nunca persistida. |

---

## 7. Mensajes literales

- `No tiene permisos para esta acción`
- `Volver al dashboard`
- `Sin datos`

---

## 8. Checklist por CA

- [ ] CA-16.1: tarjetas Admin con usuarios activos (total/admin/propietario), inmuebles, pendientes, vencidas, pagos pendientes y recaudación del mes.
- [ ] CA-16.2: tarjetas Propietario sin usuarios, con notificaciones sin leer y alcance propio.
- [ ] CA-16.3: gráfico 1 con 12 meses, emitido por `fecha_emision` y cobrado validado por `fecha_pago`.
- [ ] CA-16.4: gráfico 2 con pendiente/vencida/pagada del año en curso.
- [ ] CA-16.5: parámetros de URL manipulados no amplían el alcance.
- [ ] CA-16.6: cada tarjeta enlaza al listado con filtro aplicado.
- [ ] CA-16.7: sin datos → tarjetas 0 y `Sin datos`.
- [ ] CA-16.8: moneda con 2 decimales y porcentaje con 1 decimal.
- [ ] Sin emails, sin cron, sin CDN, sin SQLite; Hito 1 intacto (throttle 900, HU-08/09/12 integradas).

---

## 9. Formato de cierre

- Archivos creados/modificados con ruta completa desde la raíz del repo.
- Ruta implementada: `GET /dashboard` → `dashboard` → `DashboardController::index` → `auth`.
- Matriz `CA-16.1…CA-16.8` → archivo/test/verificación.
- Decisiones D2/H2/DI aplicadas.
- Comandos ejecutados y resultado (o `NO VERIFICABLE`).
- Pendientes o riesgos.
