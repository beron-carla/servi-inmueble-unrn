# Reporte de verificación Hito 2 (HU-16–HU-22) vs Hito 1 — ServiInmueble

**Fecha:** 2026-11-20  
**Alcance:** verificación en modo **solo lectura** (sin modificar archivos ni BD; consultas de BD no ejecutadas por falta de conexión).  
**Objetivo:** resumir hallazgos y acordar qué debemos hacer para avanzar.

## 1) Contexto y estado general

- **Base Hito 1:** mayormente coherente en rutas/config revisadas (timezone `America/Argentina/Buenos_Aires`, sin colisiones con rutas Hito 2, `partials/alerts.php` presente, filtro admin `role:admin`, ENUM/listas de `medio_pago` coherentes, listados no excluyen inactivos indebidamente).
- **Hito 2 (HU-16–HU-22, D2-01..D2-13):** **no implementado** en código/esquema actual (sin rutas/UI de pagos, mora, notificaciones e informes; sin tabla `notificacion`; `transaccion` sin campos de validación/gestión ampliada; sin Chart.js vendorizado localmente).
- **Trazabilidad documental:** HU-14 existe en `docs/historias_de_usuario/`; **no se localizaron** HU-16/17/18, consolidado Hito 2 ni `docs/historias_de_usuario/hito2/`. Las decisiones D2-01..D2-13 **no están** en `docs/decisiones.md` y las rutas previstas **no están** en `docs/rutas.md`.
- **Entorno:** PHP 8.3.36 (uploads 40M/40M), MariaDB 10.11.16 (binarios). BD no conectable (`ERROR 2002 HY000 10061`), por lo que esquema/datos efectivos y ejecución de pruebas quedaron **no verificables**.

## 2) Hallazgos principales (desvíos y riesgos)

| Prioridad | Hallazgo | Evidencia | Impacto |
|---|---|---|---|
| Alta | **Throttle de login inseguro/inconsistente**: ventana real 10 s pero el mensaje indica “15 minutos”. | `app/Services/LoginThrottleService.php:10-11`, `app/Services/AuthService.php:47,61,70` | Debilita protección ante fuerza bruta y confunde al usuario. |
| Alta | **`max_allowed_packet` posiblemente insuficiente** para comprobantes (1M en config activa vs 16M en otra sección y 40M en PHP). | `/mnt/c/xampp/mysql/bin/my.ini:37` (1M) y `:163` (16M); PHP 40M/40M | Riesgo de fallos al subir/guardar comprobantes grandes. Confirmar en servidor con BD activa. |
| Media | **Pendientes diferidos de Hito 1** con placeholders. | HU-08 CA-08.3 `app/Controllers/Admin/TiposServicioController.php` + `destroy`; HU-09 CA-09.5 `app/Views/admin/tipos-servicio/show.php`; HU-12 CA-12.5 `app/Views/inmuebles/show.php` | Funcionalidad incompleta y deuda antes de sumar Hito 2. |
| Media | **Documentación Hito 2 incompleta en repo** (decisiones, rutas y HUs no versionadas/localizables). | `docs/decisiones.md`, `docs/rutas.md`, ausencia de HU-16..22/Hito 2 en `docs/` | Falta de trazabilidad y riesgo de implementaciones divergentes. |
| Media | **Repositorio con muchos cambios sin commitear** (`M` en services/repos/views/tests clave). | Estado git en rama `Lucas-trabajo` (p.ej. `FacturaService`, `FacturaRepository`, `ServicioInmuebleService`, `TipoServicioService`, `InmuebleService`, vistas de detalle) | Riesgo de mezclar WIP/ruido y dificultar revisión e integración. |
| Bloqueante (entorno) | **BD y suite no verificables**. | MySQL no conecta (`10061`); `phpunit.xml` apunta a `serviinmueble_test` y depende de migraciones/seeds (`tests/database/BootstrapDatabaseTest.php`) | No se pueden validar datos/esquema efectivo ni ejecutar pruebas (F4). |

## 3) Qué debemos hacer (plan accionable)

### A. Correcciones base y saneamiento (prioridad inmediata)

1. **Seguridad login:** ajustar `LoginThrottleService::VENTANA_SEGUNDOS` a 900 s (15 min) y alinear los mensajes de bloqueo en `AuthService` con la ventana real.
2. **Config de BD para adjuntos:** revisar/ajustar `max_allowed_packet` del servidor MySQL/MariaDB para soportar comprobantes; **confirmar con** `SHOW VARIABLES LIKE 'max_allowed_packet'` cuando la BD esté disponible.
3. **Ordenar el repositorio:** revisar los cambios sin commitear, separar WIP/ruido de cambios intencionales y dejar el árbol listo antes de implementar Hito 2 (especial atención a services, repos, vistas y tests modificados).

### B. Cerrar pendientes de Hito 1

4. **HU-08 CA-08.3:** implementar eliminación segura de tipo de servicio y quitar el mensaje de “diferido”.
5. **HU-09 CA-09.5:** mostrar conteo real de inmuebles vinculados en el detalle de tipo de servicio.
6. **HU-12 CA-12.5:** incorporar resumen de facturación en el detalle de inmueble.

### C. Trazabilidad y preparación de Hito 2 (antes de codificar)

7. **Documentar decisiones y rutas:** registrar D2-01..D2-13 en `docs/decisiones.md` y agregar las rutas previstas de Hito 2 en `docs/rutas.md`.
8. **Restaurar/versionar HUs Hito 2:** incorporar en `docs/` HU-16..HU-22 y el consolidado/sección 4 de Hito 2 para que el equipo trabaje sobre la misma fuente.
9. **Alcance acordado (recordatorio D2-01..D2-13):**
   - Validación de pagos por Admin.
   - Pago por total.
   - Comprobante obligatorio salvo efectivo.
   - `numero_comprobante` único por factura.
   - Recaudación calculada por `fecha_pago` validado.
   - Notificaciones internas: `por_vencer` (7 días, configurable), `vencida`, `pago_validado`, `pago_rechazado`.
   - Alcance: Admin global / Propietario solo lo propio (validado en servidor).
   - Métricas por unidad (kWh/m³/Gigas consumo, Moneda fija monto).
   - Informes mensuales (`periodo='mes'`) con anuales aparte.
   - Chart.js vendorizado localmente.

### D. Implementación Hito 2 (con impacto en datos y pruebas)

10. **Migraciones/esquema:** extender `transaccion` (estado, validador, fechas, motivo de rechazo, metadatos/MIME de comprobante, unicidad por factura+comprobante, índices) y crear tabla `notificacion`.
11. **Frontend/assets:** vendorizar Chart.js en `public/assets/plugins/chart.js/` y agregar UI de notificaciones (campanita) en `app/Views/layout/header.php`.
12. **Aplicación y pruebas:** actualizar modelos/repos/servicios/controladores/rutas/vistas y ajustar tests afectados (p.ej. `tests/database/BootstrapDatabaseTest.php`, `tests/database/UsuarioGestionTest.php`, `tests/feature/ServiciosInmuebleTest.php`, entre otros).

### E. Re-verificación cuando haya BD

13. **Ejecutar pendientes bloqueados:** consultas A3–A5/B1–B2 (variables/versión/charset, conteos/consistencias, `SHOW CREATE TABLE/SHOW TABLES`) y correr la suite de PHPUnit sobre `serviinmueble_test` con MariaDB disponible.

## 4) Acuerdos sugeridos para el equipo

- **No iniciar implementación de Hito 2** sin antes sanear el working tree y versionar decisiones/HUs/rutas.
- **Tratar el throttle y `max_allowed_packet` como prerequisitos** (seguridad y soporte de comprobantes).
- **Revalidar todo lo “NO VERIFICABLE”** apenas se restablezca la conexión a BD y el entorno de pruebas.
