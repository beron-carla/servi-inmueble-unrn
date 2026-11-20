# Verificación de solo lectura A1–F4 — ServiInmueble (HU-16–HU-22 vs Hito 1)

**Modalidad:** solo lectura. Sin modificar/crear/borrar archivos, sin migraciones/seeders/commits; consultas de BD solo lectura (`SELECT/SHOW/DESCRIBE`) cuando estuviera disponible.  
**Criterio:** no asumir; reportar `NO EXISTE`/`NO VERIFICABLE` con motivo y evidencia cuando corresponda. Cada punto con veredicto, evidencia textual y frase de cambio si hay desvío.

---

## A. Entorno y configuración

### A1 — Zona horaria de la aplicación
- **Veredicto:** OK
- **Evidencia:** `app/Config/App.php:136` → `appTimezone = 'America/Argentina/Buenos_Aires'`.
- **Cambio requerido:** ninguno.

### A2 — Límites de carga PHP (comprobantes)
- **Veredicto:** OK (a nivel PHP)
- **Evidencia:** PHP 8.3.36 (`/mnt/c/xampp/php/php.exe`, `C:\xampp\php\php.ini`) con `upload_max_filesize=40M`, `post_max_size=40M`.
- **Cambio requerido:** mantener coherencia con la configuración del servidor de BD para adjuntos.

### A3 — `max_allowed_packet` (MySQL/MariaDB)
- **Veredicto:** DESVÍO / NO VERIFICABLE (valor efectivo en servidor)
- **Evidencia (archivo):** `/mnt/c/xampp/mysql/bin/my.ini:37` = `1M`; `/mnt/c/xampp/mysql/bin/my.ini:163` = `16M` (frente a 40M/40M en PHP).
- **Evidencia (BD):** `SHOW VARIABLES LIKE 'max_allowed_packet'` no ejecutado.
- **Motivo de no verificabilidad:** BD no conectable (`mysql.exe -u root -h 127.0.0.1 -P 3306 -e "SELECT 1"` → `ERROR 2002 (HY000): Can't connect to server on '127.0.0.1' (10061)`).
- **Cambio requerido:** revisar/ajustar `max_allowed_packet` del servidor MySQL/MariaDB para comprobantes y confirmar con `SHOW VARIABLES` cuando la BD esté disponible.

### A4 — Versión del motor de base de datos
- **Veredicto:** PARCIAL / NO VERIFICABLE (en ejecución)
- **Evidencia:** binarios MariaDB 10.11.16 detectados en el entorno.
- **Evidencia faltante:** `SELECT VERSION()` no ejecutado por falta de conexión a BD.
- **Cambio requerido:** confirmar versión efectiva del servidor al restablecer la conexión.

### A5 — Charset/collation y `DISTINCT unidad_medida`
- **Veredicto:** NO VERIFICABLE
- **Motivo:** requiere conexión a BD para consultar variables de charset/collation y datos (`DISTINCT unidad_medida`).
- **Cambio requerido:** ejecutar verificaciones de charset/collation y valores de `unidad_medida` con BD activa.

---

## B. Datos y esquema efectivo de base de datos

### B1 — Conteos y consistencias de datos
- **Veredicto:** NO VERIFICABLE
- **Motivo:** BD no conectable (`ERROR 2002 (HY000) ... 10061`).
- **Cambio requerido:** ejecutar consultas de conteo/consistencia (facturas, transacciones, relaciones y estados) cuando `serviinmueble`/entorno de BD esté disponible.

### B2 — Esquema efectivo (`SHOW CREATE TABLE` / `SHOW TABLES`)
- **Veredicto:** NO VERIFICABLE
- **Motivo:** sin conexión a BD.
- **Cambio requerido:** confirmar esquema efectivo y contrastarlo con migraciones, en especial `transaccion` y la inexistencia/creación de `notificacion`.

### Observaciones de esquema según migraciones/SQL revisados (repositorio)
- **Veredicto:** NO IMPLEMENTADO (soporte Hito 2)
- **Evidencia:**
  - `app/Database/Migrations/2026-11-09-000010_CreateTransaccion.php`: esquema actual sin campos de validación/gestión ampliada requeridos por Hito 2 (estado de validación, validador, fechas/motivo de rechazo, metadatos/MIME extendidos, unicidad por factura+comprobante según definición, índices previstos).
  - Sin migración/tabla `notificacion` identificada.
- **Cambio requerido:** diseñar migraciones para extender `transaccion` y crear tabla `notificacion` conforme a D2-01..D2-13.

---

## C. Rutas, documentación y trazabilidad (Hito 1 vs Hito 2)

### C1 — Rutas Hito 2 y colisiones
- **Veredicto:** OK (sin colisiones implementadas) / NO EXISTE (rutas Hito 2)
- **Evidencia:** revisión de rutas (`php spark routes`, solo lectura) sin rutas Hito 2 colisionantes ni detalle `GET /facturas/(:num)`.
- **Cambio requerido:** definir e implementar rutas de Hito 2 (pagos/validación, mora, notificaciones, informes) y registrarlas en `docs/rutas.md`.

### C2 — Documentación de decisiones y rutas
- **Veredicto:** DESVÍO
- **Evidencia:**
  - `docs/decisiones.md`: faltan D2-01..D2-13 (se registran P-01/P-05 de Hito 1).
  - `docs/rutas.md`: faltan rutas previstas de Hito 2.
- **Cambio requerido:** registrar D2-01..D2-13 en `docs/decisiones.md` y agregar rutas previstas de Hito 2 en `docs/rutas.md`.

### C3 — Historias de usuario y consolidado Hito 2
- **Veredicto:** PARCIAL / NO EXISTE (Hito 2 en repo, según verificación)
- **Evidencia:**
  - Presente: `docs/historias_de_usuario/HU-14_compacto.md`.
  - No localizados durante la verificación: HU-16/17/18, `HU_Hito2_compacto.md` y `docs/historias_de_usuario/hito2/`.
- **Cambio requerido:** restaurar/versionar en `docs/` HU-16..HU-22 y el consolidado/sección 4 de Hito 2.

### C4 — Lógica de listados y reglas base observadas
- **Veredicto:** OK
- **Evidencia:** lógica de listado no excluye inactivos por estado de inmueble/servicio; filtro admin `role:admin`; ENUM/listas de `medio_pago` coherentes (`debito automatico` sin tilde).
- **Cambio requerido:** ninguno identificado en este punto.

---

## D. UI, vistas y assets

### D1 — Parciales, componentes y layout
- **Veredicto:** OK (base) / NO EXISTE (elementos Hito 2)
- **Evidencia:**
  - `app/Views/partials/alerts.php`: existe.
  - `app/Views/components/`: no existe.
  - `app/Views/layout/header.php`: sin campanita/notificaciones.
- **Cambio requerido:** agregar UI de notificaciones (campanita) en `app/Views/layout/header.php` según Hito 2.

### D2 — Chart.js y assets de informes
- **Veredicto:** NO EXISTE
- **Evidencia:** sin `public/assets/plugins/chart.js/` (Chart.js no vendorizado localmente).
- **Cambio requerido:** vendorizar Chart.js en `public/assets/plugins/chart.js/`.

---

## E. Pendientes de Hito 1 (deuda funcional detectada)

### E1 — HU-08 CA-08.3 (eliminación segura de tipo de servicio)
- **Veredicto:** PENDIENTE / DESVÍO
- **Evidencia:** `app/Controllers/Admin/TiposServicioController.php` → acción `destroy` con mensaje diferido.
- **Cambio requerido:** implementar eliminación segura de tipo de servicio y quitar el mensaje diferido.

### E2 — HU-09 CA-09.5 (conteo de inmuebles vinculados)
- **Veredicto:** PENDIENTE / DESVÍO
- **Evidencia:** `app/Views/admin/tipos-servicio/show.php` con placeholder de conteo.
- **Cambio requerido:** mostrar conteo real de inmuebles vinculados.

### E3 — HU-12 CA-12.5 (resumen de facturación en detalle de inmueble)
- **Veredicto:** PENDIENTE / DESVÍO
- **Evidencia:** `app/Views/inmuebles/show.php` con placeholder de resumen de facturación.
- **Cambio requerido:** incorporar resumen de facturación en el detalle de inmueble.

### E4 — Seguridad de autenticación (throttle)
- **Veredicto:** DESVÍO
- **Evidencia:** `app/Services/LoginThrottleService.php:10-11` → ventana de 10 s (“para pruebas”); `app/Services/AuthService.php:47,61,70` → mensajes “Demasiados intentos, intente en 15 minutos”.
- **Cambio requerido:** ajustar `LoginThrottleService::VENTANA_SEGUNDOS` a 900 y alinear mensajes de bloqueo en `AuthService`.

---

## F. Pruebas

### F4 — Ejecución de suite (PHPUnit)
- **Veredicto:** NO VERIFICABLE / NO EJECUTADO
- **Evidencia:** `phpunit.xml` apunta a `serviinmueble_test`; dependencias de BD/migraciones/seeds (`tests/database/BootstrapDatabaseTest.php`). Tests afectados/relevantes identificados: `tests/database/BootstrapDatabaseTest.php`, `tests/database/UsuarioGestionTest.php`, `tests/feature/ServiciosInmuebleTest.php`.
- **Motivo:** BD/motor no disponible (`ERROR 2002 ... 10061`).
- **Cambio requerido:** ejecutar la suite cuando `serviinmueble_test` y MariaDB estén disponibles; actualizar tests afectados por cambios de esquema/lógica de Hito 2.

---

## Hallazgos adicionales

- **Repositorio con muchos cambios sin commitear:** árbol git con numerosos archivos modificados (`M`), incluyendo `app/Services/FacturaService.php`, `app/Repositories/FacturaRepository.php`, `app/Services/ServicioInmuebleService.php`, `app/Services/TipoServicioService.php`, `app/Services/InmuebleService.php` y vistas de detalle de inmueble/tipo de servicio. Riesgo de mezclar WIP/ruido con cambios intencionales.
- **Skills desactualizadas:** `agents/*/SKILL.md` con contenido desactualizado respecto al estado esperado.
- **Dependencias de datos/tests:** uso de `transaccion`/`DemoSeeder` en datos de prueba/bootstrap identificado (impacto ante migraciones de Hito 2).

---

## Resumen de puntos no verificables (por dependencia de BD/entorno)

| Punto | Motivo | Acción para verificar |
|---|---|---|
| A3 (valor efectivo `max_allowed_packet`) | Sin conexión a BD | Ejecutar `SHOW VARIABLES LIKE 'max_allowed_packet';` |
| A4 (versión efectiva BD) | Sin conexión a BD | Ejecutar `SELECT VERSION();` |
| A5 (charset/collation, `DISTINCT unidad_medida`) | Sin conexión a BD | Consultar variables y datos |
| B1 (conteos/consistencias) | Sin conexión a BD | Ejecutar consultas de conteo/consistencia |
| B2 (`SHOW CREATE TABLE`/`SHOW TABLES`) | Sin conexión a BD | Inspeccionar esquema efectivo |
| F4 (PHPUnit) | BD `serviinmueble_test` no disponible | Ejecutar suite con MariaDB y entorno de pruebas listo |

**Error de conexión observado:** `ERROR 2002 (HY000): Can't connect to server on '127.0.0.1' (10061)`.

---

## Definición de Hito 2 (referencia D2-01..D2-13)

- Validación de pagos por **Admin**.
- Pago por **total**.
- **Comprobante obligatorio** salvo efectivo.
- `numero_comprobante` **único por factura**.
- Recaudación por `fecha_pago` **validado**.
- Notificaciones internas: `por_vencer` (7 días configurable), `vencida`, `pago_validado`, `pago_rechazado`.
- Alcance: **Admin global** / **Propietario propio** (validado en servidor).
- Métricas por unidad (**kWh/m³/Gigas** consumo, **Moneda fija** monto).
- Informes mensuales `periodo='mes'` con anuales aparte.
- **Chart.js** vendorizado localmente.

---

## Conclusión

- **Hito 1:** base mayormente coherente con desvíos/deuda puntual (throttle, placeholders HU-08/09/12) y configuración de adjuntos a confirmar en servidor.
- **Hito 2:** no implementado en esquema, rutas, UI/assets ni trazabilidad documental en repositorio (según verificación); requiere saneamiento previo del árbol, cierre de pendientes Hito 1, documentación (decisiones/rutas/HUs) y migraciones + cambios de aplicación y pruebas.
- **Re-verificación obligatoria:** A3–A5, B1–B2 y F4 al restablecer conectividad de BD y entorno de pruebas.
