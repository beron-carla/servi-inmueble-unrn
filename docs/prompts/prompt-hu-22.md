# Prompt de implementación · HU-22 · Promedio de consumo por unidad de medida

**Versión:** 1.0  
**Fecha:** 2026-11-20  
**Uso:** pegar primero el prompt general (`docs/prompts/prompt-general-hito-2.md`, sección 2) y después el bloque de la sección 2 de este archivo.  
**Matriz maestro:** [`../historias_de_usuario/hito2/trazabilidad-hito-2.md`](../historias_de_usuario/hito2/trazabilidad-hito-2.md).  
**Orden canónico:** 7.º de 7, en paralelo con HU-21 (`HU-17 → HU-20 → HU-18 → HU-19 → HU-16 → HU-21 → HU-22`).  
**Estado:** sin implementar. Este documento no modifica código ni BD.

---

## 1. Precondiciones

- Leer íntegramente: `docs/historias_de_usuario/hito2/HU-22_compacto.md`, `README.md`, `HU_Hito2_compacto.md`, `trazabilidad-hito-2.md`, `docs/decisiones.md`, `docs/rutas.md`.
- Cumplir H2-07 antes de codificar: D2-01…D2-13 en `docs/decisiones.md` y las 15 rutas en `docs/rutas.md`.
- No depende de pagos: alcanza con Hito 1 (HU-14/15) y Chart.js local (D2-06/DI-06).
- HU-21 crea `InformeService`, `InformeRepository` e `InformesController`; HU-22 agrega métodos. Si se ejecutan en paralelo, crear esos archivos una sola vez con ambos métodos.
- Migración `2026-11-20-000003_AddIndicesFacturaHito2.php` aplicada antes de las pruebas.

---

## 2. Prompt específico (copiar y pegar)

```text
Ejecutá ÚNICAMENTE HU-22 (Promedio de consumo por unidad de medida) del Hito 2 de ServiInmueble 2026.
Respetá el prompt general ya pegado. No implementes HU-21 ni pagos.

RUTA PROPIA (única)
GET /informes/promedio-unidad | informes.promedio-unidad | InformesController::promedioUnidad | auth
Parámetros: desde, hasta, inmueble (opcional; por defecto todos los permitidos por el rol), servicio, unidad.

CRITERIOS DE ACEPTACIÓN LITERALES (copiar tal cual en la matriz de cierre)
- CA-22.1 El Administrador ve todos los inmuebles; el Propietario solo ve los propios; cualquier otro caso devuelve 403.
- CA-22.2 Filtros: rango de períodos (desde y hasta), inmueble (opcional; por defecto todos los permitidos por el rol), tipo de servicio y unidad de medida.
- CA-22.3 Para kWh, m³ y Gigas se promedia consumo_del_periodo por unidad de medida, informando cantidad de facturas, promedio, mínimo y máximo.
- CA-22.4 Para "Moneda fija" se promedia monto_total y se rotula "Monto promedio", porque el consumo no aplica.
- CA-22.5 El gráfico de barras muestra el promedio por unidad de medida y la tabla informa unidad, cantidad de facturas, promedio, mínimo y máximo.
- CA-22.6 Solo se incluyen facturas mensuales (periodo = 'mes') para que los promedios sean comparables; las facturas anuales se informan en un resumen aparte.
- CA-22.7 Sin datos: "No hay datos para los filtros seleccionados".
- CA-22.8 Los consumos se muestran con 2 decimales y los montos en ARS con 2 decimales.

CONTRATOS (nombres exactos)
- App\Services\InformeService::promedioPorUnidad(array $filtros, int $idUsuario, string $rol): array
  Devuelve: filas, grafico, resumen_anual, sin_datos.
- App\Repositories\InformeRepository::promedioPorUnidad(array $filtros, ?int $idPropietario): array
- App\Controllers\InformesController::promedioUnidad()
Ampliando los archivos creados en HU-21; no crear un segundo controlador, servicio ni repositorio.

REGLAS
- Admin = alcance global; Propietario = propiedad.id_propietario = session('id_usuario') (D2-05, DI-08).
  Rol inválido -> 403 con vista amigable. Inmueble ajeno manipulado por query string no amplía el alcance.
- Agrupar por factura.unidad_medida (snapshot histórico, D-17), con AVG, MIN, MAX y COUNT(*) (CA-22.3).
- kWh, m³ y Gigas: promediar consumo_del_periodo. "Moneda fija": promediar monto_total y rotular "Monto promedio" (D2-04, CA-22.4).
- Solo periodo='mes' en gráfico y tabla (DI-09); periodo='anio' va en resumen_anual aparte (D2-12, CA-22.6).
- Gráfico de barras con Chart.js local (DI-06); sin CDN.
- Consumos con 2 decimales; montos en ARS con 2 decimales (formato_moneda); fechas dd/mm/aaaa.
- Sin datos: sin_datos=true, mensaje literal y "Sin datos" en el gráfico. Sin emails, sin pagos.

ARCHIVOS EXACTOS
Modificar: app/Config/Routes.php, app/Views/layout/main.php (carga local de Chart.js),
app/Views/layout/sidebar.php, docs/decisiones.md, docs/rutas.md.
Ampliar (creados en HU-21): app/Controllers/InformesController.php con promedioUnidad(),
app/Services/InformeService.php con promedioPorUnidad(),
app/Repositories/InformeRepository.php con promedioPorUnidad().
Si HU-22 se ejecuta antes o en paralelo, crear esos tres archivos con ambos métodos de una sola vez.
Nuevos propios: app/Views/informes/promedio-unidad.php, tests/feature/InformesPromedioUnidadTest.php.

DECISIONES APLICABLES
D2-04, D2-05, D2-06, D2-12 · H2-04, H2-05, H2-07 · DI-06, DI-08, DI-09 · D-17.

MENSAJES LITERALES
"No hay datos para los filtros seleccionados"
"Sin datos" / "Monto promedio"
"No tiene permisos para esta acción" / "Volver al dashboard"

VERIFICACIÓN (tests/feature/InformesPromedioUnidadTest.php)
- Acceso Admin vs Propietario; rol inválido 403; inmueble ajeno no amplía alcance.
- Filtros de desde/hasta, inmueble, servicio y unidad.
- kWh, m³ y Gigas: cantidad, promedio, mínimo y máximo sobre consumo_del_periodo.
- "Moneda fija": promedio de monto_total con etiqueta "Monto promedio".
- Gráfico de barras y tabla con las columnas requeridas; solo mensuales; anuales en resumen aparte.
- Sin datos: mensaje literal y "Sin datos". Formatos con 2 decimales.
- spark routes: GET /informes/promedio-unidad con nombre y filtro auth.

ENTREGA
Matriz CA-22.1..CA-22.8 -> archivo/test, decisiones aplicadas, comandos y resultado (o NO VERIFICABLE).
```

---

## 3. Trazabilidad por criterio de aceptación

| CA | Implementación | Verificación |
|---|---|---|
| CA-22.1 | DI-08 en `InformeService::promedioPorUnidad()` + filtro `auth` | `InformesPromedioUnidadTest::testAccesoPorRol()` |
| CA-22.2 | Filtros `desde`, `hasta`, `inmueble`, `servicio`, `unidad` | `InformesPromedioUnidadTest::testFiltros()` |
| CA-22.3 | `AVG/MIN/MAX/COUNT` sobre `consumo_del_periodo` | `InformesPromedioUnidadTest::testConsumosPorUnidad()` |
| CA-22.4 | `Moneda fija` → `monto_total`, etiqueta `Monto promedio` | `InformesPromedioUnidadTest::testMonedaFija()` |
| CA-22.5 | Clave `grafico` + vista `informes/promedio-unidad.php` | `InformesPromedioUnidadTest::testGraficoYTabla()` |
| CA-22.6 | `periodo='mes'` + `resumen_anual` | `InformesPromedioUnidadTest::testAnualesAparte()` |
| CA-22.7 | `sin_datos = true` y mensaje literal | `InformesPromedioUnidadTest::testSinDatos()` |
| CA-22.8 | Consumos y montos con 2 decimales | `InformesPromedioUnidadTest::testFormatos()` |

---

## 4. Contratos de servicios y repositorios

```php
namespace App\Services;

final class InformeService
{
    public function consumoInteranual(array $filtros, int $idUsuario, string $rol): array {} // HU-21
    public function promedioPorUnidad(array $filtros, int $idUsuario, string $rol): array {}
}
```

```php
namespace App\Repositories;

final class InformeRepository
{
    public function consumoInteranual(array $filtros, ?int $idPropietario): array {} // HU-21
    public function promedioPorUnidad(array $filtros, ?int $idPropietario): array {}
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

**Ampliados (creados en HU-21)**

- `app/Controllers/InformesController.php`
- `app/Services/InformeService.php`
- `app/Repositories/InformeRepository.php`

**Nuevos propios**

- `app/Views/informes/promedio-unidad.php`
- `tests/feature/InformesPromedioUnidadTest.php`

**Migración previa a las pruebas**

- `app/Database/Migrations/2026-11-20-000003_AddIndicesFacturaHito2.php`

---

## 6. Decisiones aplicables

| ID | Aplicación en HU-22 |
|---|---|
| D2-04 | Agrupación por unidad de medida; `Moneda fija` por monto. |
| D2-05 | Admin global / Propietario propio en servidor. |
| D2-06 | Chart.js local, sin CDN. |
| D2-12 | Solo mensuales; anuales en resumen aparte. |
| H2-04 | Autorización transversal por rol. |
| H2-05 | Chart.js no altera los CA del gráfico. |
| H2-07 | Rutas y decisiones registradas antes de codificar. |
| DI-06 | `chart.umd.min.js` v4.4.7 en `layout/main.php`. |
| DI-08 | Alcance por `propiedad.id_propietario` desde el servicio. |
| DI-09 | Filtro `periodo='mes'`. |
| D-17 | `factura.unidad_medida` es snapshot. |

---

## 7. Mensajes literales

- `No hay datos para los filtros seleccionados`
- `Sin datos`
- `Monto promedio`
- `No tiene permisos para esta acción` / `Volver al dashboard`

---

## 8. Checklist por CA

- [ ] CA-22.1: Admin todos; Propietario solo propios; otro caso 403.
- [ ] CA-22.2: filtros por período, inmueble, servicio y unidad.
- [ ] CA-22.3: kWh/m³/Gigas promedian `consumo_del_periodo` con cantidad, mínimo y máximo.
- [ ] CA-22.4: `Moneda fija` promedia `monto_total` con etiqueta `Monto promedio`.
- [ ] CA-22.5: gráfico de barras y tabla con unidad, cantidad, promedio, mínimo y máximo.
- [ ] CA-22.6: solo mensuales; anuales en resumen aparte.
- [ ] CA-22.7: mensaje literal sin datos.
- [ ] CA-22.8: consumos y montos con 2 decimales.
- [ ] Sin CDN, sin emails, sin SQLite; Hito 1 intacto.

---

## 9. Formato de cierre

- Archivos creados/modificados con ruta completa desde la raíz del repo.
- Ruta implementada: `GET /informes/promedio-unidad` → `informes.promedio-unidad` → `InformesController::promedioUnidad` → `auth`.
- Matriz `CA-22.1…CA-22.8` → archivo/test/verificación.
- Decisiones D2/H2/DI aplicadas.
- Comandos ejecutados y resultado (o `NO VERIFICABLE`).
- Pendientes o riesgos.
