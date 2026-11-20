# Prompt de implementación · HU-21 · Informe de consumo interanual

**Versión:** 1.0  
**Fecha:** 2026-11-20  
**Uso:** pegar primero el prompt general (`docs/prompts/prompt-general-hito-2.md`, sección 2) y después el bloque de la sección 2 de este archivo.  
**Matriz maestro:** [`../historias_de_usuario/hito2/trazabilidad-hito-2.md`](../historias_de_usuario/hito2/trazabilidad-hito-2.md).  
**Orden canónico:** 6.º de 7 (`HU-17 → HU-20 → HU-18 → HU-19 → HU-16 → HU-21 → HU-22`); HU-21 y HU-22 pueden ejecutarse en paralelo entre sí.  
**Estado:** sin implementar. Este documento no modifica código ni BD.

---

## 1. Precondiciones

- Leer íntegramente: `docs/historias_de_usuario/hito2/HU-21_compacto.md`, `README.md`, `HU_Hito2_compacto.md`, `trazabilidad-hito-2.md`, `docs/decisiones.md`, `docs/rutas.md`.
- Cumplir H2-07 antes de codificar: D2-01…D2-13 en `docs/decisiones.md` y las 15 rutas en `docs/rutas.md`.
- No depende de pagos: alcanza con Hito 1 (HU-14/15) y Chart.js local (D2-06/DI-06).
- Chart.js v4.4.7 en `public/assets/plugins/chart.js/chart.umd.min.js`, cargado en `app/Views/layout/main.php` antes de `renderSection('scripts')`.
- Migración `2026-11-20-000003_AddIndicesFacturaHito2.php` aplicada antes de las pruebas.
- HU-21 crea `InformeService` e `InformeRepository`; HU-22 los reutiliza. Si se ejecutan en paralelo, crear los archivos una sola vez con ambos métodos.

---

## 2. Prompt específico (copiar y pegar)

```text
Ejecutá ÚNICAMENTE HU-21 (Informe de consumo interanual) del Hito 2 de ServiInmueble 2026.
Respetá el prompt general ya pegado. No implementes HU-22 ni pagos.

RUTA PROPIA (única)
GET /informes/consumo-interanual | informes.consumo-interanual | InformesController::consumoInteranual | auth
Parámetros: inmueble (obligatorio), servicio (opcional), anio_base (por defecto el actual),
anio_comparado (por defecto el anterior), unidad.

CRITERIOS DE ACEPTACIÓN LITERALES (copiar tal cual en la matriz de cierre)
- CA-21.1 El Administrador accede a cualquier inmueble; el Propietario solo a los propios; cualquier otro caso devuelve 403.
- CA-21.2 Filtros: inmueble (obligatorio), servicio (opcional), año base (por defecto el actual), año comparado (por defecto el anterior) y unidad de medida.
- CA-21.3 El gráfico muestra 12 puntos por año con el consumo mensual de cada uno; los meses sin factura valen 0.
- CA-21.4 La tabla muestra mes, consumo del año base, consumo del año comparado, diferencia y variación porcentual, e incluye los totales anuales.
- CA-21.5 Solo se incluyen facturas mensuales (periodo = 'mes'); las anuales se muestran en un resumen aparte.
- CA-21.6 El agrupamiento usa periodo_anio y periodo_mes; no se mezclan inmuebles ni servicios no seleccionados.
- CA-21.7 Sin datos: "No hay datos de consumo para los filtros seleccionados".
- CA-21.8 Para servicios con unidad "Moneda fija" el gráfico muestra montos y la etiqueta "Monto", no consumo.
- CA-21.9 Si el valor del año base es 0, la variación se muestra como "—" y no se divide por cero.

CONTRATOS (nombres exactos)
- App\Services\InformeService::consumoInteranual(array $filtros, int $idUsuario, string $rol): array
  Devuelve: serie, tabla, totales, resumen_anual, sin_datos.
- App\Repositories\InformeRepository::consumoInteranual(array $filtros, ?int $idPropietario): array
- App\Repositories\InformeRepository::opcionesInmuebles(?int $idPropietario): array
- App\Repositories\InformeRepository::opcionesServicios(?int $idPropietario): array
- App\Controllers\InformesController::consumoInteranual()

REGLAS
- Admin = alcance global; Propietario = propiedad.id_propietario = session('id_usuario') (D2-05, DI-08).
  Inmueble ajeno o rol inválido -> 403 con la vista amigable; inmueble obligatorio (CA-21.1, CA-21.2).
- Parámetros inválidos se ignoran; nunca amplían el alcance. Servicio y unidad se aplican en servidor.
- Consulta sobre factura JOIN propiedad_servicio JOIN propiedad, filtrada por periodo='mes' (DI-09) y agrupada
  por periodo_anio, periodo_mes (CA-21.5, CA-21.6). Las facturas periodo='anio' van en resumen_anual aparte.
- Serie: 12 puntos por año (meses 01..12), meses sin factura = 0 (CA-21.3).
- tabla: mes, base, comparado, diferencia = base - comparado, variacion = (diferencia / base) * 100,
  totales anuales. Si base = 0 -> variacion "—" sin dividir por cero (CA-21.4, CA-21.9).
- Unidad "Moneda fija": usar factura.monto_total y etiqueta "Monto"; resto de unidades: consumo_del_periodo (D2-04).
- factura.unidad_medida es snapshot histórico (D-17): no leer el catálogo en vivo.
- Formatos: ARS con 2 decimales; porcentajes con 1 decimal (formato_porcentaje); fechas dd/mm/aaaa.
- Gráfico con Chart.js local (DI-06); sin CDN. Sin emails, sin pagos.

ARCHIVOS EXACTOS
Modificar: app/Config/Routes.php, app/Views/layout/main.php (carga local de Chart.js),
app/Views/layout/sidebar.php, docs/decisiones.md, docs/rutas.md.
Nuevos: app/Services/InformeService.php, app/Repositories/InformeRepository.php,
app/Controllers/InformesController.php (método consumoInteranual),
app/Views/informes/consumo-interanual.php, tests/feature/InformesConsumoInteranualTest.php.

DECISIONES APLICABLES
D2-04, D2-05, D2-06, D2-12 · H2-04, H2-05, H2-07 · DI-06, DI-08, DI-09 · D-17.

MENSAJES LITERALES
"No hay datos de consumo para los filtros seleccionados"
"Sin datos" / "—" / "Monto"
"No tiene permisos para esta acción" / "Volver al dashboard"

VERIFICACIÓN (tests/feature/InformesConsumoInteranualTest.php)
- Acceso Admin vs Propietario; inmueble ajeno 403; inmueble obligatorio.
- Filtros de servicio, años y unidad; agrupamiento por periodo_anio/periodo_mes sin mezclas.
- 12 puntos por año con 0 en meses sin factura; totales anuales y resumen de anuales.
- Moneda fija usa monto_total y etiqueta "Monto"; base 0 muestra "—".
- Sin datos: mensaje literal y "Sin datos" en el gráfico.
- spark routes: GET /informes/consumo-interanual con nombre y filtro auth.

ENTREGA
Matriz CA-21.1..CA-21.9 -> archivo/test, decisiones aplicadas, comandos y resultado (o NO VERIFICABLE).
```

---

## 3. Trazabilidad por criterio de aceptación

| CA | Implementación | Verificación |
|---|---|---|
| CA-21.1 | DI-08 en `InformeService::consumoInteranual()` + filtro `auth` | `InformesConsumoInteranualTest::testAccesoPorRol()` |
| CA-21.2 | Filtros del servicio y opciones del repositorio | `InformesConsumoInteranualTest::testFiltros()` |
| CA-21.3 | `serie` con meses 01..12 y valor 0 | `InformesConsumoInteranualTest::testDocePuntos()` |
| CA-21.4 | `tabla` con diferencia, variación y `totales` | `InformesConsumoInteranualTest::testTablaYTotales()` |
| CA-21.5 | `periodo='mes'` + `resumen_anual` | `InformesConsumoInteranualTest::testAnualesAparte()` |
| CA-21.6 | `GROUP BY periodo_anio, periodo_mes` con filtros de inmueble/servicio | `InformesConsumoInteranualTest::testAgrupamiento()` |
| CA-21.7 | `sin_datos = true` y mensaje literal | `InformesConsumoInteranualTest::testSinDatos()` |
| CA-21.8 | `Moneda fija` → `monto_total` y etiqueta `Monto` | `InformesConsumoInteranualTest::testMonedaFija()` |
| CA-21.9 | Base 0 → `—`, sin división por cero | `InformesConsumoInteranualTest::testBaseCero()` |

---

## 4. Contratos de servicios y repositorios

```php
namespace App\Services;

final class InformeService
{
    public function consumoInteranual(array $filtros, int $idUsuario, string $rol): array {}
    public function promedioPorUnidad(array $filtros, int $idUsuario, string $rol): array {} // HU-22
}
```

```php
namespace App\Repositories;

final class InformeRepository
{
    public function consumoInteranual(array $filtros, ?int $idPropietario): array {}
    public function promedioPorUnidad(array $filtros, ?int $idPropietario): array {} // HU-22
    public function opcionesInmuebles(?int $idPropietario): array {}
    public function opcionesServicios(?int $idPropietario): array {}
}
```

---

## 5. Lista exacta de archivos

**Existentes a modificar**

- `app/Config/Routes.php`
- `app/Views/layout/main.php`
- `app/Views/layout/sidebar.php`
- `docs/decisiones.md`
- `docs/rutas.md`

**Nuevos**

- `app/Services/InformeService.php`
- `app/Repositories/InformeRepository.php`
- `app/Controllers/InformesController.php`
- `app/Views/informes/consumo-interanual.php`
- `tests/feature/InformesConsumoInteranualTest.php`

**Migración previa a las pruebas**

- `app/Database/Migrations/2026-11-20-000003_AddIndicesFacturaHito2.php`

---

## 6. Decisiones aplicables

| ID | Aplicación en HU-21 |
|---|---|
| D2-04 | `Moneda fija` se mide por monto; el resto por consumo. |
| D2-05 | Admin global / Propietario propio en servidor. |
| D2-06 | Chart.js local, sin CDN. |
| D2-12 | Gráficos y tabla solo `periodo='mes'`; anuales en resumen aparte. |
| H2-04 | Autorización transversal por rol. |
| H2-05 | Chart.js no altera los CA del gráfico. |
| H2-07 | Rutas y decisiones registradas antes de codificar. |
| DI-06 | `chart.umd.min.js` v4.4.7 en `layout/main.php`. |
| DI-08 | Alcance por `propiedad.id_propietario` desde el servicio. |
| DI-09 | Filtro `periodo='mes'`. |
| D-17 | `factura.unidad_medida` es snapshot. |

---

## 7. Mensajes literales

- `No hay datos de consumo para los filtros seleccionados`
- `Sin datos`
- `—`
- `Monto`
- `No tiene permisos para esta acción` / `Volver al dashboard`

---

## 8. Checklist por CA

- [ ] CA-21.1: Admin cualquier inmueble; Propietario solo propios; otro caso 403.
- [ ] CA-21.2: filtros con defaults de año base y comparado.
- [ ] CA-21.3: 12 puntos por año; meses sin factura en 0.
- [ ] CA-21.4: tabla con mes, base, comparado, diferencia, variación y totales.
- [ ] CA-21.5: solo mensuales; anuales en resumen aparte.
- [ ] CA-21.6: agrupamiento por `periodo_anio`/`periodo_mes`, sin mezclas.
- [ ] CA-21.7: mensaje literal sin datos.
- [ ] CA-21.8: `Moneda fija` con `monto_total` y etiqueta `Monto`.
- [ ] CA-21.9: base 0 → `—`, sin división por cero.
- [ ] Sin CDN, sin emails, sin SQLite; Hito 1 intacto.

---

## 9. Formato de cierre

- Archivos creados/modificados con ruta completa desde la raíz del repo.
- Ruta implementada: `GET /informes/consumo-interanual` → `informes.consumo-interanual` → `InformesController::consumoInteranual` → `auth`.
- Matriz `CA-21.1…CA-21.9` → archivo/test/verificación.
- Decisiones D2/H2/DI aplicadas.
- Comandos ejecutados y resultado (o `NO VERIFICABLE`).
- Pendientes o riesgos.
