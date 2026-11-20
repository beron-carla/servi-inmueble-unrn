# Prompt de implementación · HU-19 · Control de mora

**Versión:** 1.0  
**Fecha:** 2026-11-20  
**Uso:** pegar primero el prompt general (`docs/prompts/prompt-general-hito-2.md`, sección 2) y después el bloque de la sección 2 de este archivo.  
**Matriz maestro:** [`../historias_de_usuario/hito2/trazabilidad-hito-2.md`](../historias_de_usuario/hito2/trazabilidad-hito-2.md).  
**Orden canónico:** 4.º de 7 (`HU-17 → HU-20 → HU-18 → HU-19 → HU-16 → HU-21 → HU-22`).  
**Estado:** sin implementar. Este documento no modifica código ni BD.

---

## 1. Precondiciones

- Leer íntegramente: `docs/historias_de_usuario/hito2/HU-19_compacto.md`, `README.md`, `HU_Hito2_compacto.md`, `trazabilidad-hito-2.md`, `docs/decisiones.md`, `docs/rutas.md`.
- Cumplir H2-07 antes de codificar: D2-01…D2-13 en `docs/decisiones.md` y las 15 rutas en `docs/rutas.md`.
- HU-17 implementada: `transaccion.en_mora` se graba al registrar pagos. `MoraService::vencimientoAplicable()` ya existe y se reutiliza.
- Migración `2026-11-20-000003_AddIndicesFacturaHito2.php` aplicada antes de las pruebas de consulta.
- Este HU alimenta las tarjetas de HU-16; no implementa el dashboard.

---

## 2. Prompt específico (copiar y pegar)

```text
Ejecutá ÚNICAMENTE HU-19 (Control de mora) del Hito 2 de ServiInmueble 2026.
Respetá el prompt general ya pegado. No implementes HU-16 ni calculadores de intereses.

RUTA PROPIA (única)
GET /mora | mora.index | MoraController::index | auth
Parámetros: propietario (solo Admin), inmueble, desde, hasta, dias_min, dias_max, page.

CRITERIOS DE ACEPTACIÓN LITERALES (copiar tal cual en la matriz de cierre)
- CA-19.1 Una factura está en mora si sigue pendiente y su vencimiento aplicable es anterior a hoy. Es el mismo universo que el estado calculado "vencida".
- CA-19.2 Un pago está en mora si fecha_pago es posterior al vencimiento aplicable; se guarda en_mora = 1 al registrarse y no cambia después.
- CA-19.3 El reporte muestra propietario, inmueble, servicio, período, vencimiento, días de mora y monto, ordenado por días de mora descendente.
- CA-19.4 Filtros: propietario (solo Administrador), inmueble, rango de vencimiento y rango de días de mora. El Propietario solo ve sus facturas.
- CA-19.5 El dashboard muestra cantidad y monto de facturas en mora y cantidad de pagos tardíos.
- CA-19.6 Si un pago tardío se valida, la factura pasa a pagada pero el pago conserva en_mora = 1 para el historial.
- CA-19.7 Los días de mora se calculan como hoy menos el vencimiento aplicable, con mínimo 0.
- CA-19.8 El Hito 2 no aplica intereses, recargos ni bloqueos automáticos por mora.

CONTRATOS (nombres exactos)
- App\Services\MoraService::vencimientoAplicable(array $factura): string  (ya creado en HU-17; reutilizar)
- App\Services\MoraService::diasMora(string $vencimiento, ?string $hoy = null): int
- App\Services\MoraService::listado(array $filtros, int $idUsuario, string $rol): array
- App\Repositories\MoraRepository con filtros por propietario (Admin), inmueble, rango de vencimiento y rango de días; orden por días de mora descendente.

REGLAS
- vencimiento aplicable = COALESCE(fecha_segundo_vencimiento, fecha_primer_vencimiento) (DI-07, D-16).
- diasMora = max(0, hoy - vencimiento); fecha_negocio en Y-m-d; zona America/Argentina/Buenos_Aires.
- Universo de facturas en mora = factura.estado='pendiente' AND vencimiento_aplicable < hoy (igual a estado calculado "vencida"). Nunca guardar "vencida".
- Pagos tardíos = transaccion.en_mora = 1; se conservan aunque la factura ya esté pagada (CA-19.6). No se recalculan ni se limpian.
- Admin ve toda la mora; Propietario solo propiedad.id_propietario = session('id_usuario') (D2-05, DI-08), inclusive si manipula ?propietario= ajeno.
- Parámetros inválidos se ignoran en servidor; nunca se amplía el alcance.
- Sin intereses, recargos, punitorios ni bloqueos automáticos (CA-19.8).
- CA-19.5 se resuelve en HU-16: este HU solo expone los valores para el dashboard; no modificar el dashboard.

ARCHIVOS EXACTOS
Modificar: app/Config/Routes.php, app/Views/layout/sidebar.php,
app/Views/dashboard/index.php (solo enlaces de tarjeta; la lógica de métricas va en HU-16),
docs/decisiones.md, docs/rutas.md.
Ampliar: app/Services/MoraService.php (creado en HU-17) con diasMora() y listado().
Nuevos: app/Repositories/MoraRepository.php, app/Controllers/MoraController.php,
app/Views/mora/index.php, tests/feature/MoraTest.php.
Aplicar antes de probar: app/Database/Migrations/2026-11-20-000003_AddIndicesFacturaHito2.php.

DECISIONES APLICABLES
D2-05 · H2-02, H2-04, H2-07 · DI-07, DI-08 · D-16.

MENSAJES LITERALES
"No hay facturas en mora con los filtros seleccionados"
"No tiene permisos para esta acción" / "Volver al dashboard"
Formato de fechas visibles: dd/mm/aaaa; importes ARS con 2 decimales.

VERIFICACIÓN (tests/feature/MoraTest.php)
- Factura pendiente vencida aparece con días de mora; pendiente no vencida no aparece.
- Pago tardío en_mora=1 se conserva tras validarse y la factura pasa a pagada.
- Orden por días de mora descendente y columnas del reporte.
- Filtros por propietario (Admin), inmueble, vencimiento y días; propietario no ve ajenos aunque manipule query string.
- diasMora con mínimo 0 y segundo vencimiento preferido.
- Sin mora: mensaje literal. Sin intereses/recargos/bloqueos en la respuesta.
- spark routes: GET /mora con nombre mora.index y filtro auth.

ENTREGA
Matriz CA-19.1..CA-19.8 -> archivo/test, decisiones aplicadas, comandos y resultado (o NO VERIFICABLE).
```

---

## 3. Trazabilidad por criterio de aceptación

| CA | Implementación | Verificación |
|---|---|---|
| CA-19.1 | `MoraService::listado()` con estado calculado DI-07 | `MoraTest::testUniversoVencidaCalculada()` |
| CA-19.2 | `en_mora` grabado en HU-17, conservado aquí | `MoraTest::testEnMoraHistoricoInmutable()` |
| CA-19.3 | `MoraRepository` con `ORDER BY dias_mora DESC` | `MoraTest::testColumnasYOrden()` |
| CA-19.4 | Filtros + DI-08 en el servicio | `MoraTest::testFiltrosYAlcancePorRol()` |
| CA-19.5 | Valores expuestos para `DashboardService` (HU-16) | `MoraTest::testValoresParaDashboard()` |
| CA-19.6 | Pago validado mantiene `en_mora = 1` | `MoraTest::testPagoTardioValidadoConservaMarca()` |
| CA-19.7 | `MoraService::diasMora()` | `MoraTest::testDiasMoraMinimoCero()` |
| CA-19.8 | Sin cálculo de intereses ni bloqueos | `MoraTest::testSinInteresesNiBloqueos()` |

---

## 4. Contratos de servicios y repositorios

```php
namespace App\Services;

final class MoraService
{
    public function vencimientoAplicable(array $factura): string {}       // de HU-17
    public function diasMora(string $vencimiento, ?string $hoy = null): int {}
    public function listado(array $filtros, int $idUsuario, string $rol): array {}
}
```

```php
namespace App\Repositories;

final class MoraRepository
{
    /** @return list<array<string, mixed>> */
    public function listar(array $filtros, ?int $idPropietario): array {}

    /** @return array{cantidad: int, monto: float, pagos_tardios: int} */
    public function metricas(?int $idPropietario): array {}
}
```

---

## 5. Lista exacta de archivos

**Existentes a modificar**

- `app/Config/Routes.php`
- `app/Views/layout/sidebar.php`
- `app/Views/dashboard/index.php`
- `docs/decisiones.md`
- `docs/rutas.md`

**Ampliado**

- `app/Services/MoraService.php` (creado en HU-17)

**Nuevos**

- `app/Repositories/MoraRepository.php`
- `app/Controllers/MoraController.php`
- `app/Views/mora/index.php`
- `tests/feature/MoraTest.php`

**Migración previa a las pruebas**

- `app/Database/Migrations/2026-11-20-000003_AddIndicesFacturaHito2.php`

---

## 6. Decisiones aplicables

| ID | Aplicación en HU-19 |
|---|---|
| D2-05 | Admin ve toda la mora; Propietario solo la propia, en servidor. |
| H2-02 | `en_mora` histórico vs. mora calculada de facturas pendientes. |
| H2-04 | Autorización transversal por rol. |
| H2-07 | Rutas y decisiones registradas antes de codificar. |
| DI-07 | `COALESCE` y `FacturaService::estadoCalculado()`. |
| DI-08 | Alcance por `propiedad.id_propietario` desde el servicio. |
| D-16 | “Vencida” calculada, nunca persistida. |

---

## 7. Mensajes literales

- `No hay facturas en mora con los filtros seleccionados`
- `No tiene permisos para esta acción` / `Volver al dashboard`

---

## 8. Checklist por CA

- [ ] CA-19.1: mora = pendiente + vencimiento aplicable < hoy; mismo universo que “vencida”.
- [ ] CA-19.2: `en_mora = 1` grabado al registrar y nunca modificado.
- [ ] CA-19.3: columnas requeridas y orden por días de mora descendente.
- [ ] CA-19.4: filtros y alcance por rol en servidor; parámetros ajenos ignorados.
- [ ] CA-19.5: valores disponibles para las tarjetas de HU-16.
- [ ] CA-19.6: pago tardío validado conserva `en_mora = 1`.
- [ ] CA-19.7: días = hoy − vencimiento, mínimo 0, con segundo vencimiento preferido.
- [ ] CA-19.8: sin intereses, recargos ni bloqueos automáticos.
- [ ] Sin emails, sin cron; Hito 1 intacto.

---

## 9. Formato de cierre

- Archivos creados/modificados con ruta completa desde la raíz del repo.
- Ruta implementada: `GET /mora` → `mora.index` → `MoraController::index` → `auth`.
- Matriz `CA-19.1…CA-19.8` → archivo/test/verificación.
- Decisiones D2/H2/DI aplicadas.
- Comandos ejecutados y resultado (o `NO VERIFICABLE`).
- Pendientes o riesgos.
