# Verificación de solo lectura — ServiInmueble (hallazgos HU-16–HU-22 vs Hito 1)

**Fecha:** 2026-11-20  
**Modalidad:** solo lectura (sin modificar/crear/borrar archivos durante la verificación original; sin migraciones, seeders ni commits; BD solo lectura `SELECT/SHOW/DESCRIBE` cuando estuviera disponible).  
**Alcance solicitado:** contrastar hallazgos de HU-16–HU-22 (Hito 2) contra la base implementada de Hito 1, con veredicto, evidencia y acción sugerida por hallazgo.

> **Nota sobre este documento:** consolida la verificación reportada (A1–F4) y evidencia observable en el repositorio al 2026-11-20. Los puntos dependientes de conexión a BD se mantienen como **NO VERIFICABLE** por indisponibilidad de la base (`ERROR 2002 HY000 10061` en la verificación original). Donde el estado actual del árbol difiere de lo observado originalmente, se indica explícitamente.

---

## 1. Resumen ejecutivo

| Área | Veredicto | Observación clave |
|---|---|---|
| Configuración base / timezone | **OK** | `appTimezone = America/Argentina/Buenos_Aires` configurado. |
| Seguridad login (throttle) | **DESVÍO** | Ventana real 10 s; mensajes informan “15 minutos”. |
| Configuración adjuntos (PHP/MySQL) | **DESVÍO / PARCIAL** | PHP permite 40M/40M; `max_allowed_packet` en `my.ini` aparece en 1M y 16M según sección; valor efectivo del servidor no confirmado. |
| Rutas Hito 2 / colisiones | **OK (sin colisiones)** | No hay rutas Hito 2 implementadas ni `GET /facturas/(:num)`; rutas previstas de Hito 2 ausentes en `docs/rutas.md`. |
| UI/Assets Hito 2 | **NO IMPLEMENTADO** | Sin Chart.js vendorizado; sin campanita/notificaciones en header; `partials/alerts.php` existe y `components/` no. |
| Pendientes Hito 1 (HU-08/09/12) | **PENDIENTE** | Placeholders/mensajes diferidos aún presentes. |
| Esquema Hito 2 (migraciones) | **NO IMPLEMENTADO** | `transaccion` sin campos de validación extendidos; no existe migración/tabla `notificacion`. |
| Trazabilidad documental Hito 2 | **DESVÍO / ACTUALIZADO** | D2-01..D2-13 no están en `docs/decisiones.md`; rutas Hito 2 no están en `docs/rutas.md`. HU-16..HU-22 y consolidado Hito 2 **sí existen actualmente** en `docs/historias_de_usuario/` (modificados en el árbol de trabajo), a diferencia de lo observado en la verificación original. |
| Datos/esquema efectivo BD | **NO VERIFICABLE** | Sin conexión a BD. |
| Pruebas automatizadas (F4) | **NO VERIFICABLE** | Dependen de `serviinmueble_test` y BD/migraciones/seeds. |
| Estado del repositorio | **RIESGO** | Árbol con muchos cambios sin commitear (incluye services/repos/views/docs clave). |

---

## 2. Detalle de verificación

### A. Entorno y configuración

#### A1 — Zona horaria de la aplicación
- **Veredicto:** OK.
- **Evidencia:** `app/Config/App.php:136` → `public string $appTimezone = 'America/Argentina/Buenos_Aires';`
- **Acción:** ninguna.

#### A2 — Límites de carga en PHP (comprobantes/adjuntos)
- **Veredicto:** OK (a nivel PHP, según entorno inspeccionado).
- **Evidencia (verificación original):** PHP 8.3.36 (`/mnt/c/xampp/php/php.exe`, `C:\xampp\php\php.ini`) con `upload_max_filesize=40M`, `post_max_size=40M`.
- **Acción:** mantener coherencia con límites del servidor BD/HTTP al implementar carga de comprobantes.

#### A3 — `max_allowed_packet` (MySQL/MariaDB)
- **Veredicto:** DESVÍO / NO VERIFICABLE (valor efectivo).
- **Evidencia (archivo):** `/mnt/c/xampp/mysql/bin/my.ini`
  - línea 37: `max_allowed_packet=1M`
  - línea 163: `max_allowed_packet=16M`
- **Evidencia (verificación original):** valor efectivo en servidor no confirmado por falta de conexión.
- **Impacto:** riesgo de fallos al almacenar comprobantes si el servidor aplica un límite bajo (p.ej. 1M) frente a uploads PHP de 40M.
- **Acción requerida:** unificar configuración según la sección efectiva del servidor y confirmar con `SHOW VARIABLES LIKE 'max_allowed_packet';` cuando la BD esté disponible.

#### A4 — Versión de motor de BD
- **Veredicto:** PARCIAL / NO VERIFICABLE (en ejecución).
- **Evidencia (binarios):** MariaDB 10.11.16 disponible en entorno.
- **Evidencia (verificación original):** `SELECT VERSION()` no ejecutado por falta de conexión.
- **Acción requerida:** confirmar versión en el servidor activo al restablecer BD.

#### A5 — Charset/collation y valores de `unidad_medida` en datos
- **Veredicto:** NO VERIFICABLE.
- **Motivo:** requiere conexión a BD (`SHOW VARIABLES`/consultas sobre datos).
- **Acción requerida:** verificar charset/collation efectivos y `DISTINCT unidad_medida` con BD activa.

---

### B. Datos y esquema efectivo de base de datos

#### B1 — Conteos y consistencias de datos
- **Veredicto:** NO VERIFICABLE.
- **Motivo:** BD no conectable (`ERROR 2002 (HY000): Can't connect to server on '127.0.0.1' (10061)`).
- **Acción requerida:** ejecutar conteos/consistencias (facturas, transacciones, usuarios, relaciones) en entorno con BD disponible.

#### B2 — Esquema efectivo (`SHOW CREATE TABLE` / `SHOW TABLES`)
- **Veredicto:** NO VERIFICABLE.
- **Motivo:** sin conexión a BD.
- **Acción requerida:** contrastar esquema efectivo con migraciones, especialmente `transaccion` y existencia de `notificacion`.

#### Evidencia de esquema definido en migraciones (repositorio)
- **`factura`** (`app/Database/Migrations/2026-11-09-000009_CreateFactura.php`): incluye `periodo` (`mes|anio`), `periodo_mes`, `periodo_anio`, montos/fechas/lecturas, `estado` (`pendiente|pagada`), unicidad por servicio+período y FKs.
- **`transaccion`** (`app/Database/Migrations/2026-11-09-000010_CreateTransaccion.php`): campos actuales `id_factura`, `id_propietario`, `fecha_pago`, `monto_abonado`, `medio_pago`, `numero_comprobante`, `comprobante_digital` (mediumblob), `en_mora`; **no incluye** campos de flujo de validación Hito 2 (estado de validación, validador, fechas de validación/rechazo, motivo, metadatos/MIME ampliados) ni restricciones adicionales previstas (p.ej. unicidad por factura+comprobante según D2).
- **`notificacion`**: **no se encontró** migración/tabla en `app/Database/Migrations/*Notific*`.
- **Conclusión:** el esquema versionado actual **no soporta** los requerimientos de Hito 2 (HU-16–HU-22, D2-01..D2-13).

---

### C. Rutas, controladores y trazabilidad documental

#### C1 — Rutas Hito 2 y colisiones
- **Veredicto:** OK (sin colisiones detectadas) / PENDIENTE (implementación Hito 2).
- **Evidencia (`app/Config/Routes.php`):**
  - `GET facturas` → `FacturasController::index`
  - `GET servicios-inmueble/(:num)/facturas/nuevo` → `FacturasController::create/$1`
  - `POST servicios-inmueble/(:num)/facturas` → `FacturasController::store/$1`
- **Observaciones:** no se observan rutas Hito 2 colisionantes ni detalle `GET /facturas/(:num)`.
- **Acción requerida:** definir e implementar rutas previstas de Hito 2 (pagos/validación, mora, notificaciones, informes) y documentarlas en `docs/rutas.md`.

#### C2 — Documentación de decisiones y rutas
- **Veredicto:** DESVÍO.
- **Evidencia:**
  - `docs/decisiones.md`: sin coincidencias para `D2` (decisiones D2-01..D2-13 no registradas).
  - `docs/rutas.md`: referencias a dashboard/facturas base y notas de Hito 1; sin rutas previstas de pagos/mora/notificaciones/informes de Hito 2.
- **Acción requerida:** registrar D2-01..D2-13 en `docs/decisiones.md` y agregar rutas previstas en `docs/rutas.md`.

#### C3 — Historias de usuario y consolidado Hito 2
- **Veredicto (verificación original):** NO ENCONTRADO en repo para HU-16/17/18, `HU_Hito2_compacto.md` y `docs/historias_de_usuario/hito2/`.
- **Estado actual observado (2026-11-20):** existen y están presentes/modificados en el árbol de trabajo:
  - `docs/historias_de_usuario/HU-16_compacto.md` … `HU-22_compacto.md`
  - `docs/historias_de_usuario/HU_Hito2_compacto.md`
  - `docs/historias_de_usuario/hito2/` (`HU-16..HU-22_compacto.md`, `README.md`)
- **Acción requerida:** confirmar versión canónica, revisar cambios pendientes y versionar/estabilizar la trazabilidad documental en el repositorio.

---

### D. UI, vistas y assets

#### D1 — Componentes parciales y layout
- **Veredicto:** OK (base) / PENDIENTE (Hito 2).
- **Evidencia:**
  - `app/Views/partials/alerts.php`: existe.
  - `app/Views/components/`: no existe.
  - `app/Views/layout/header.php`: sin referencias a campanita/bell/notificaciones.
- **Acción requerida:** incorporar UI de notificaciones (campanita) en el header/layout según diseño de Hito 2.

#### D2 — Librerías para informes/gráficos
- **Veredicto:** NO IMPLEMENTADO.
- **Evidencia:** `public/assets/plugins/` contiene `bootstrap`, `fontawesome-free`, `jquery`; **no** contiene `chart.js` (ni carpeta equivalente).
- **Acción requerida:** vendorizar Chart.js localmente en `public/assets/plugins/chart.js/` (según decisión de Hito 2).

---

### E. Pendientes de Hito 1 (deuda funcional)

#### E1 — HU-08 CA-08.3: eliminación segura de tipo de servicio
- **Veredicto:** PENDIENTE (diferido con placeholder).
- **Evidencia:** `app/Controllers/Admin/TiposServicioController.php` (`destroy`, aprox. líneas 143-155) establece `warning`: *"La eliminación se habilitará al completar la integración con HU-13 y HU-14."* y redirige sin eliminar.
- **Acción requerida:** implementar eliminación segura según reglas de negocio (validar vínculos/dependencias) y reemplazar el mensaje diferido.

#### E2 — HU-09 CA-09.5: conteo de inmuebles vinculados
- **Veredicto:** PENDIENTE (placeholder en vista).
- **Evidencia:** `app/Views/admin/tipos-servicio/show.php` muestra: *"La cantidad de inmuebles vinculados se incorporará en la integración con HU-13."*
- **Acción requerida:** calcular y mostrar el conteo real de inmuebles vinculados al tipo de servicio.

#### E3 — HU-12 CA-12.5: resumen de facturación en detalle de inmueble
- **Veredicto:** PENDIENTE (placeholder en vista).
- **Evidencia:** `app/Views/inmuebles/show.php` (sección “Estado de facturación”): *"El resumen de facturación se completará en la integración con HU-14 y HU-15."*
- **Acción requerida:** incorporar resumen de facturación (según HU/alcance definido) en el detalle del inmueble.

#### E4 — Seguridad de autenticación (relacionado a base operativa)
- **Veredicto:** DESVÍO.
- **Evidencia:**
  - `app/Services/LoginThrottleService.php`: `MAX_INTENTOS = 5`, `VENTANA_SEGUNDOS = 10` (comentario indica cambio para pruebas).
  - `app/Services/AuthService.php`: mensajes de bloqueo devuelven *"Demasiados intentos, intente en 15 minutos"*.
- **Acción requerida:** restablecer ventana a 900 s y alinear mensajes con la política efectiva.

---

### F. Pruebas y calidad

#### F1 — Reglas de dominio observadas (coherentes con base Hito 1)
- **Veredicto:** OK (según revisión estática original).
- **Evidencia/observaciones:** filtro admin `role:admin`; ENUM/listas de `medio_pago` coherentes (`debito automatico` sin tilde); lógica de listado sin excluir inactivos por estado de inmueble/servicio (según verificación reportada).

#### F4 — Ejecución de PHPUnit
- **Veredicto:** NO VERIFICABLE / NO EJECUTADO.
- **Motivo:** `phpunit.xml` orientado a `serviinmueble_test`; dependencias de BD/migraciones/seeds (p.ej. `tests/database/BootstrapDatabaseTest.php`) y conexión a MariaDB no disponible.
- **Acción requerida:** ejecutar suite cuando la BD de pruebas esté disponible y el entorno esté estable.

---

## 3. Hallazgos adicionales relevantes

- **Working tree con alta cantidad de cambios sin commitear:** múltiples archivos `M` (configuración, vistas, controladores, services, repositorios, docs, skills/agents, etc.). Riesgo de mezclar cambios intencionales, WIP y ruido de entorno.
  - Archivos clave mencionados en la verificación: `app/Services/FacturaService.php`, `app/Repositories/FacturaRepository.php`, `app/Services/ServicioInmuebleService.php`, `app/Services/TipoServicioService.php`, `app/Services/InmuebleService.php`, vistas de detalle de inmueble/tipo de servicio.
- **Skills/agents desactualizadas:** se observaron modificaciones en `agents/*/SKILL.md` (revisar alineación con el estado actual del proyecto).
- **Dependencias de pruebas/datos demo:** tests con dependencia de `transaccion`/`DemoSeeder` identificadas (impacto al modificar esquema de Hito 2).

---

## 4. Matriz de desvíos y acciones requeridas

| ID | Hallazgo | Veredicto | Prioridad | Acción requerida |
|---|---|---|---|---|
| V-01 | Throttle login 10 s vs mensaje “15 minutos” | Desvío | Alta | Ajustar `VENTANA_SEGUNDOS` a 900 y alinear mensajes en `AuthService`. |
| V-02 | `max_allowed_packet` inconsistente/efectivo desconocido | Desvío/No verificable | Alta | Ajustar config MySQL y confirmar con `SHOW VARIABLES`. |
| V-03 | HU-08 CA-08.3 eliminación segura diferida | Pendiente | Media | Implementar lógica segura y quitar placeholder. |
| V-04 | HU-09 CA-09.5 conteo inmuebles vinculados | Pendiente | Media | Mostrar conteo real. |
| V-05 | HU-12 CA-12.5 resumen de facturación | Pendiente | Media | Implementar resumen en detalle de inmueble. |
| V-06 | D2-01..D2-13 ausentes en `docs/decisiones.md` | Desvío | Media | Registrar decisiones Hito 2. |
| V-07 | Rutas Hito 2 ausentes en `docs/rutas.md` | Desvío | Media | Documentar rutas previstas/implementadas. |
| V-08 | HU-16..HU-22/consolidado Hito 2 (trazabilidad) | Actualizado/pendiente de consolidar | Media | Confirmar versión canónica y versionar cambios. |
| V-09 | Esquema Hito 2 no implementado (`transaccion`/`notificacion`) | Pendiente | Alta (para Hito 2) | Diseñar y aplicar migraciones compatibles. |
| V-10 | Chart.js no vendorizado y sin campanita | Pendiente | Media (para Hito 2) | Agregar assets y UI de notificaciones. |
| V-11 | Validaciones/datos/pruebas dependientes de BD | No verificable | Bloqueante por entorno | Restablecer BD y re-ejecutar A3–A5, B1–B2 y F4. |
| V-12 | Árbol git con muchos cambios sin commitear | Riesgo | Alta | Revisar, separar WIP y sanear antes de avanzar. |

---

## 5. Alcance funcional objetivo de Hito 2 (referencia D2-01..D2-13)

- Validación de pagos por **Admin**.
- Pago registrado por **total**.
- **Comprobante obligatorio** salvo medio de pago efectivo.
- `numero_comprobante` **único por factura**.
- Recaudación calculada por `fecha_pago` **validado**.
- Notificaciones internas: `por_vencer` (7 días, configurable), `vencida`, `pago_validado`, `pago_rechazado`.
- Alcance de acceso: **Admin global** / **Propietario solo lo propio** (validado en servidor).
- Métricas por unidad: **kWh, m³, Gigas** (consumo) y **Moneda fija** (monto).
- Informes **mensuales** (`periodo='mes'`) con **anuales** aparte.
- **Chart.js** vendorizado localmente.

---

## 6. Próximos pasos recomendados

1. **Restaurar seguridad/config base:** corregir throttle de login y unificar/confirmar `max_allowed_packet`.
2. **Sanear repositorio:** revisar y ordenar cambios sin commitear (separar WIP, ruido y cambios intencionales).
3. **Cerrar deuda Hito 1:** implementar HU-08 CA-08.3, HU-09 CA-09.5 y HU-12 CA-12.5.
4. **Consolidar trazabilidad Hito 2:** registrar D2-01..D2-13, actualizar `docs/rutas.md` y versionar HU/consolidado Hito 2.
5. **Preparar e implementar Hito 2:** migraciones (`transaccion`, `notificacion`), assets/UI (Chart.js, campanita), lógica de negocio/controladores/vistas y actualización de tests.
6. **Re-verificar con BD activa:** ejecutar consultas A3–A5/B1–B2 y suite PHPUnit sobre `serviinmueble_test`.

---

## 7. Fuentes y evidencia consultada (repositorio)

- Configuración y servicios: `app/Config/App.php`, `app/Services/LoginThrottleService.php`, `app/Services/AuthService.php`
- Rutas y controladores: `app/Config/Routes.php`, `app/Controllers/Admin/TiposServicioController.php`
- Vistas: `app/Views/layout/header.php`, `app/Views/partials/*`, `app/Views/admin/tipos-servicio/show.php`, `app/Views/inmuebles/show.php`
- Migraciones: `app/Database/Migrations/2026-11-09-000009_CreateFactura.php`, `app/Database/Migrations/2026-11-09-000010_CreateTransaccion.php`
- Documentación: `docs/decisiones.md`, `docs/rutas.md`, `docs/historias_de_usuario/*`
- Infra/assets: `/mnt/c/xampp/mysql/bin/my.ini`, `public/assets/plugins/*`
- Estado de trabajo: `git status` (árbol con múltiples modificaciones)
