# Prompt maestro de implementación · ServiInmueble 2026 · Hito 2

**Versión:** 1.0  
**Fecha:** 2026-11-20  
**Uso:** pegar la sección 2 al inicio de la sesión y luego el prompt de la HU correspondiente (`prompt-hu-16.md` … `prompt-hu-22.md`).  
**Matriz maestro:** [`../historias_de_usuario/hito2/trazabilidad-hito-2.md`](../historias_de_usuario/hito2/trazabilidad-hito-2.md).  
**Estado:** Hito 2 definido y aprobado, sin implementar. No modificar código ni BD durante la redacción de estos documentos.

---

## 1. Fuentes de verdad

Orden de precedencia:

1. `bd/serviinmueble (1).sql` — esquema canónico de Hito 1. No se modifica.
2. `docs/historias_de_usuario/hito2/README.md` — decisiones D2-01 a D2-13 y rutas previstas.
3. `docs/historias_de_usuario/hito2/HU_Hito2_compacto.md` — consolidado y notas H2-01 a H2-07.
4. `docs/historias_de_usuario/hito2/HU-16_compacto.md` … `HU-22_compacto.md` — CAs literales.
5. `docs/historias_de_usuario/hito2/trazabilidad-hito-2.md` — matriz, DI-01 a DI-09, catálogo de mensajes, rutas exactas.
6. `docs/decisiones.md` (D-01 a D-30) y `docs/rutas.md` — convenciones de Hito 1.
7. `docs/reportes/resultado_por_prompt.md` — estado real de Hito 1.
8. `agents/*/SKILL.md` — convenciones de código, subordinadas al SQL y a estos documentos.

**No existen** `docs/features-hito1.txt`, `docs/enunciado.txt` ni `sql/schema.sql`. No los busques ni los referencies.  
**No se usan** los nombres obsoletos `roles`, `usuarios`, `inmuebles`, `servicios_inmueble`, `facturas`, `id_rol`, `password_hash` como columna, `created_at`, `updated_at`, `deleted_at`.

---

## 2. Prompt general (copiar y pegar)

```text
Actuá como desarrollador senior de CodeIgniter 4 para el proyecto "ServiInmueble 2026".

CONTEXTO
- CodeIgniter 4.7.5, PHP 8.3, MariaDB 10.11, AdminLTE 3, autoload explícito.
- Hito 1 (HU-00 a HU-15) implementado: 124 tests, 773 assertions; OK.
- Hito 2 = HU-16 a HU-22. Definición cerrada, sin implementar.
- Estado post-verificación vigente:
  * appTimezone = America/Argentina/Buenos_Aires.
  * LoginThrottleService::VENTANA_SEGUNDOS = 900; mensajes de 15 minutos alineados.
  * HU-08/09/12 están integradas; no reabrir sus placeholders.
  * transaccion sin campos de validación; no existe tabla notificacion.
  * Sin rutas de pagos/mora/notificaciones/informes; sin Chart.js vendorizado.
  * max_allowed_packet no confirmado; A3-A5/B1-B2 requieren BD activa.
  * Working tree con cambios sin commitear: sanear antes de tocar código.
- La base canónica es bd/serviinmueble (1).sql. El Hito 2 la extiende solo por migraciones.
- El trabajo se hace por HU, con trazabilidad CA por CA.

1. LECTURA OBLIGATORIA (en este orden)
1) docs/historias_de_usuario/hito2/HU-XX_compacto.md (la HU asignada)
2) docs/historias_de_usuario/hito2/README.md
3) docs/historias_de_usuario/hito2/HU_Hito2_compacto.md (notas H2-01 a H2-07)
4) docs/historias_de_usuario/hito2/trazabilidad-hito-2.md
5) docs/decisiones.md
6) docs/rutas.md
7) docs/prompts/prompt-general-hito-2.md (este documento)
8) agents/servi-inmueble-ci4/SKILL.md y los skills que apliquen:
   ci4-module-scaffold, ci4-migration-builder, ci4-routes-guard,
   adminlte-view-craft, ci4-frontend-ui, ci4-security-audit,
   ci4-hu-validator, ci4-test-suite, php-best-practices.

Si algo no está en esos documentos, no lo inventes: detenete y preguntá.
Antes de codificar, cumplí H2-07: registrar D2-01..D2-13 en docs/decisiones.md y
las 15 rutas del mapa de la sección 6 en docs/rutas.md, y revisar las tres
migraciones de la trazabilidad (sección 7). Este paquete documental NO modifica
esos dos archivos base.

2. ESQUEMA CANÓNICO (NO NEGOCIABLE)
Tablas Hito 1 (columnas exactas):
- usuario(id_usuario, nombre_completo, mail, contrasenia, rol, estado, fecha_alta, cambiar_contrasenia)
- tipo_inmueble(id_tipo_inmueble, nombre)
- direccion(id_direccion, calle, numero, piso, depto, ciudad)
- propiedad(id_propiedad, alias, superficie, estado, id_tipo_inmueble, id_propietario, id_direccion)
- tipo_servicio(id_tipo_servicio, tipo, categoria, unidad_medida, proveedor_predeterminado, activo)
- propiedad_servicio(id_propiedad_servicio, id_propiedad, id_tipo_servicio,
  codigo_pago_electronico, numero_cuenta_contrato, numero_medidor,
  domicilio_facturacion, titular, estado)
- factura(id_factura, id_propiedad_servicio, periodo, periodo_mes, periodo_anio,
  fecha_emision, fecha_primer_vencimiento, fecha_segundo_vencimiento, monto_total,
  consumo_del_periodo, lectura_anterior, lectura_actual, unidad_medida, proveedor,
  categoria_factura, estado)
- transaccion(id_transaccion, id_factura, id_propietario, fecha_pago, monto_abonado,
  medio_pago, numero_comprobante, comprobante_digital, en_mora)
- sesion(id_sesion, id_usuario, token, fecha_creacion, ultima_actividad)
- historico_login(id_intento, email, ip, exitoso, fecha)
- modificacion_usuario(id_modificacion, id_usuario, id_admin, fecha_modificacion)

Estados exactos:
- usuario.rol: propietario | admin (NO existe tabla roles)
- usuario.estado: activo | bloqueado | baja
- propiedad.estado: ocupado | disponible | en mantenimiento | inactivo
- propiedad_servicio.estado: activo | pausado | en revision | inactivo
- factura.estado: pendiente | pagada (NUNCA "vencida")
- transaccion.medio_pago: transferencia | debito automatico | efectivo | billetera virtual
- tipo_servicio.unidad_medida: kWh | m³ | Gigas | Moneda fija
- periodo: mes | anio; periodo_mes = '00' solo cuando periodo = 'anio'

Reglas de esquema:
- "Vencida" se calcula con COALESCE(fecha_segundo_vencimiento, fecha_primer_vencimiento)
  y la fecha actual (D-16). No se guarda.
- La factura guarda snapshot de proveedor, categoria_factura y unidad_medida (D-17).
- Bajas logicas salvo tipo_servicio sin uso (HU-08). No se borran facturas ni pagos.

Extension Hito 2 (solo por migraciones, orden estricto):
1) 2026-11-20-000001_AlterTransaccionHito2.php:
   estado ENUM('pendiente','validada','rechazada') DEFAULT 'pendiente',
   id_admin_validador INT NULL FK usuario, fecha_validacion DATETIME NULL,
   motivo_rechazo VARCHAR(255) NULL, comprobante_nombre VARCHAR(255) NULL,
   comprobante_mime VARCHAR(100) NULL,
   UNIQUE (id_factura, numero_comprobante), KEY (estado, fecha_pago),
   backfill DI-04 (pagos existentes -> validada).
2) 2026-11-20-000002_CreateNotificacion.php:
   id_notificacion, id_usuario FK, id_factura FK, id_transaccion FK NULL,
   tipo ENUM('por_vencer','vencida','pago_validado','pago_rechazado'),
   mensaje VARCHAR(255), leida TINYINT(1) DEFAULT 0,
   fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
   idempotencia_key VARCHAR(120) NOT NULL, UNIQUE (id_usuario, idempotencia_key).
3) 2026-11-20-000003_AddIndicesFacturaHito2.php:
   factura(estado, fecha_primer_vencimiento, fecha_segundo_vencimiento) y
   factura(periodo_anio, periodo_mes, id_propiedad_servicio).
No modificar bd/serviinmueble (1).sql ni el ENUM factura.estado.

3. DECISIONES QUE NO PODES CONTRADECIR
- D2-01: el Admin valida cada pago; hasta entonces la factura no cambia.
- D2-02: pago por el total; sin parciales.
- D2-03: notificaciones internas; sin emails ni push.
- D2-04: kWh/m³/Gigas por consumo; Moneda fija por monto.
- D2-05: Admin global / Propietario solo lo propio, filtrado en servidor.
- D2-06: Chart.js local en public/assets/plugins/chart.js/, sin CDN.
- D2-07: comprobante obligatorio salvo efectivo; PDF/JPG/JPEG <= 5 MB por contenido.
- D2-08: "por vencer" = 7 dias corridos por defecto, configurable.
- D2-09: pago_validado y pago_rechazado (con motivo) al Propietario.
- D2-10: Admin ve notificaciones globales en lectura; sin campanita propia.
- D2-11: numero_comprobante unico por factura.
- D2-12: informes mensuales usan periodo='mes'; anuales en resumen aparte.
- D2-13: recaudacion = monto_abonado de pagos validados por fecha_pago.
- H2-01..H2-07: ver docs/historias_de_usuario/hito2/HU_Hito2_compacto.md.
- DI-01: idempotencia con idempotencia_key; claves venc:{id_factura}:{tipo} y
  pago:{id_transaccion}:{tipo}.
- DI-02: BLOB + MIME por contenido con finfo; 5 MiB; Content-Disposition inline y nosniff.
- DI-03: app/Config/Hito2.php con diasPorVencer = 7.
- DI-04: backfill de pagos historicos a 'validada'.
- DI-05: comparar montos con number_format(..., 2, '.', '').
- DI-06: Chart.js v4.4.7 en chart.umd.min.js, cargado en layout/main.php.
- DI-07: estado calculado reutiliza FacturaService::estadoCalculado().
- DI-08: autorizacion de alcance siempre en el servicio.
- DI-09: informes filtran periodo='mes'.
Reglas Hito 1 vigentes: CSRF global en POST, PRG, 403 amigable, sin borrado fisico,
password/email/sesion de 30 min, arquitectura por capas, tests MySQL.

4. ARQUITECTURA OBLIGATORIA
Flujo: Controller -> Service -> Repository -> Model/Entity.
- Controller: HTTP, entrada, llamada al Service, flash y PRG. Sin reglas de negocio.
- Service: reglas de negocio, validaciones, control de propiedad, transacciones BD,
  generacion de notificaciones.
- Repository: consultas y acceso a datos con Query Builder/Model. Sin reglas de negocio.
- Model/Entity: mapeo y casts simples.
Servicios y repositorios del Hito 2 (nombres exactos):
- PagoService / PagoRepository / ComprobanteService / MoraService
- ValidacionPagoService
- NotificacionService / NotificacionRepository / NotificacionModel
- DashboardService / DashboardRepository
- InformeService / InformeRepository

5. REGLAS TRANSVERSALES
- Admin ve todo; Propietario solo sus inmuebles. Validar siempre en servidor.
- 403 real con app/Views/errors/html/error_403.php ("No tiene permisos para esta acción",
  boton "Volver al dashboard"); 404 para inexistentes. Nunca redirect por permisos.
- Todo POST usa csrf_field() y termina en redirect (PRG).
- Importes: ARS con separador de miles y 2 decimales (formato_moneda).
- Fechas visibles: dd/mm/aaaa (formato_fecha). Negocio: Y-m-d; auditoria: Y-m-d H:i:s.
- Porcentajes: 1 decimal (formato_porcentaje).
- Zona horaria: America/Argentina/Buenos_Aires.
- Sin emails, sin cron, sin pasarela, sin intereses, sin pagos parciales.
- No editar ni eliminar pagos validados; conservar rechazados como historial.
- Una notificacion nunca cambia estado de factura ni de pago.

6. RUTAS EXACTAS (no inventar otras)
GET  /dashboard                          dashboard                    DashboardController::index              auth
GET  /facturas/(:num)/pagar              pagos.create                 PagosController::create/$1             auth
POST /facturas/(:num)/pagos               pagos.store                  PagosController::store/$1              auth
GET  /pagos                              pagos.index                  PagosController::index                 auth
GET  /pagos/(:num)/comprobante           pagos.comprobante            PagosController::comprobante/$1        auth
GET  /admin/pagos                        admin.pagos.index            Admin\PagosController::index           auth, role:admin
GET  /admin/pagos/(:num)                 admin.pagos.show             Admin\PagosController::show/$1         auth, role:admin
POST /admin/pagos/(:num)/validar         admin.pagos.validar          Admin\PagosController::validar/$1      auth, role:admin
POST /admin/pagos/(:num)/rechazar        admin.pagos.rechazar         Admin\PagosController::rechazar/$1     auth, role:admin
GET  /mora                               mora.index                   MoraController::index                  auth
GET  /notificaciones                     notificaciones.index         NotificacionesController::index        auth
POST /notificaciones/(:num)/leer         notificaciones.leer          NotificacionesController::marcarLeida/$1      auth
POST /notificaciones/leer-todas          notificaciones.leer-todas    NotificacionesController::marcarTodasLeidas   auth
GET  /informes/consumo-interanual        informes.consumo-interanual  InformesController::consumoInteranual  auth
GET  /informes/promedio-unidad           informes.promedio-unidad     InformesController::promedioUnidad      auth
Declarar leer-todas antes de (:num)/leer; las estaticas antes de (:num).
autoRoute = false. Grupo admin con filtros ['auth','role:admin'].

7. MENSAJES LITERALES (copiar sin cambiar tildes ni puntos)
No tiene permisos para esta acción / Volver al dashboard
La factura ya está pagada
El monto debe ser igual al total de la factura
La fecha de pago no puede ser futura
El comprobante debe ser PDF, JPG o JPEG
El comprobante no puede superar los 5 MB
El comprobante es obligatorio para este medio de pago
Ya existe un pago pendiente de validación para esta factura
Pago registrado. Queda pendiente de validación.
El pago ya fue procesado
El motivo del rechazo es obligatorio
El motivo del rechazo no puede superar los 255 caracteres
No hay pagos pendientes de validación
No tenés notificaciones
No hay datos de consumo para los filtros seleccionados
No hay datos para los filtros seleccionados
No hay facturas en mora con los filtros seleccionados
Sin datos / — / Monto / Monto promedio
Pago validado correctamente. / Pago rechazado correctamente.
404: No se encontró la factura solicitada. / No se encontró el pago solicitado.

8. FLUJO DE TRABAJO POR HU
1) Leer HU, CAs, decisiones D2/H2/DI y el prompt especifico.
2) Verificar dependencias y prerequisitos de entorno.
3) Aplicar solo las migraciones que correspondan a esa HU.
4) Implementar Repository, Service, Controller, vistas y rutas.
5) Agregar validaciones y mensajes exactos del catalogo.
6) Implementar control de propiedad y 403 en el servicio.
7) Escribir tests o verificaciones por cada CA.
8) Ejecutar ci4-security-audit y ci4-hu-validator.
9) Ejecutar tests contra serviinmueble_test.
10) Reportar matriz CA -> archivo/test/verificacion.

9. VALIDACION Y CIERRE
- Cada CA-XX.Y debe estar implementado y tener prueba o verificacion explicita.
- Cada nota D2/H2/DI aplicable debe respetarse.
- No cerrar la HU si queda un CA FALTANTE o PARCIAL. No hay D-28 para el Hito 2.
- Si MariaDB no conecta, reportar NO VERIFICABLE; no usar SQLite.

10. PROHIBICIONES
- No crear tablas/columnas nuevas sin decision documentada (D2/DI).
- No usar el esquema viejo de los skills.
- No usar SQLite para tests.
- No hardcodear credenciales, rutas ni IDs.
- No redirigir para negar permisos.
- No implementar HUs fuera de alcance ni emails/push.
- No modificar bd/serviinmueble (1).sql.
- No borrar registros historicos.
- No dejar mensajes en ingles.
- No reintroducir VENTANA_SEGUNDOS = 10 ni placeholders de HU-08/09/12.

11. COMANDOS
Windows (XAMPP):
  C:\xampp\php\php.exe spark routes
  C:\xampp\php\php.exe spark migrate
  C:\xampp\php\php.exe spark db:seed DatabaseSeeder
  C:\xampp\php\php.exe vendor\bin\phpunit
Windows (WAMP, guia vigente):
  C:\wamp64\bin\php\php8.3.28\php.exe spark routes
  C:\wamp64\bin\php\php8.3.28\php.exe vendor\bin\phpunit
WSL:
  /mnt/c/xampp/php/php.exe spark routes
  /mnt/c/xampp/php/php.exe vendor/bin/phpunit
BD (cuando este disponible):
  SHOW VARIABLES LIKE 'max_allowed_packet';
  SELECT VERSION();

12. FORMATO DE RESPUESTA AL CERRAR
- Archivos creados/modificados (ruta completa desde la raiz del repo).
- Rutas implementadas (metodo + URI + nombre + controlador + filtros).
- Matriz CA -> archivo/test/verificacion.
- Decisiones D2/H2/DI aplicadas.
- Comandos ejecutados y resultado (incluido NO VERIFICABLE si aplica).
- Pendientes o riesgos.
```

---

## 3. Mapa de rutas de archivos involucradas

### 3.1 Archivos existentes a modificar

| Archivo | HUs que lo modifican | Cambio |
|---|---|---|
| `app/Config/Routes.php` | HU-16…HU-22 | Agregar/verificar las 15 rutas de la sección 6. |
| `app/Models/TransaccionModel.php` | HU-17 | Ampliar `allowedFields` con los 6 campos y `estado`. |
| `app/Database/Seeds/DemoSeeder.php` | HU-17 | Insertar `estado` explícito (DI-04). |
| `app/Controllers/DashboardController.php` | HU-16, HU-20 | Métricas + disparo idempotente de vencimientos. |
| `app/Controllers/FacturasController.php` | HU-20 | Disparo idempotente de vencimientos en `index()`. |
| `app/Views/dashboard/index.php` | HU-16, HU-19 | Tarjetas y gráficos (HU-16); enlace a `/mora` (HU-19); mantiene el disparo idempotente de vencimientos de HU-20. |
| `app/Views/layout/header.php` | HU-20 | Campanita y contador solo para Propietario. |
| `app/Views/layout/main.php` | HU-16, HU-21, HU-22 | Carga local de Chart.js (DI-06). |
| `app/Views/layout/sidebar.php` | HU-16…HU-22 | Enlaces a pagos, mora, notificaciones e informes. |
| `app/Helpers/format_helper.php` | HU-16 | Agregar `formato_porcentaje()` (1 decimal). |
| `docs/decisiones.md` | Todas | Registrar D2-01…D2-13 (H2-07). |
| `docs/rutas.md` | Todas | Registrar las 15 rutas (H2-07). |

### 3.2 Archivos nuevos

| HU | Archivos nuevos |
|---|---|
| HU-17 | `app/Database/Migrations/2026-11-20-000001_AlterTransaccionHito2.php`, `app/Services/PagoService.php`, `app/Services/ComprobanteService.php`, `app/Services/MoraService.php`, `app/Repositories/PagoRepository.php`, `app/Controllers/PagosController.php`, `app/Views/pagos/create.php`, `app/Views/pagos/index.php`, `tests/database/Hito2SchemaTest.php`, `tests/unit/Services/ComprobanteServiceTest.php`, `tests/feature/PagosTest.php` |
| HU-20 | `app/Config/Hito2.php`, `app/Database/Migrations/2026-11-20-000002_CreateNotificacion.php`, `app/Models/NotificacionModel.php`, `app/Services/NotificacionService.php`, `app/Repositories/NotificacionRepository.php`, `app/Controllers/NotificacionesController.php`, `app/Views/notificaciones/index.php`, `tests/unit/Services/NotificacionServiceTest.php`, `tests/feature/NotificacionesTest.php` |
| HU-18 | `app/Services/ValidacionPagoService.php`, `app/Controllers/Admin/PagosController.php`, `app/Views/admin/pagos/index.php`, `app/Views/admin/pagos/show.php`, `tests/feature/AdminPagosTest.php` |
| HU-19 | `app/Repositories/MoraRepository.php`, `app/Controllers/MoraController.php`, `app/Views/mora/index.php`, `tests/feature/MoraTest.php` |
| HU-16 | `app/Services/DashboardService.php`, `app/Repositories/DashboardRepository.php`, `tests/feature/DashboardMetricasTest.php` |
| HU-21 | `app/Controllers/InformesController.php`, `app/Views/informes/consumo-interanual.php`, `tests/feature/InformesConsumoInteranualTest.php`; `app/Services/InformeService.php` y `app/Repositories/InformeRepository.php` se crean acá y se reutilizan en HU-22 |
| HU-22 | `app/Views/informes/promedio-unidad.php`, `tests/feature/InformesPromedioUnidadTest.php`; se agrega `promedioUnidad()` a `app/Controllers/InformesController.php`, `promedioPorUnidad()` a `InformeService` y `InformeRepository` |
| Transversal | `app/Database/Migrations/2026-11-20-000003_AddIndicesFacturaHito2.php` (aplicar antes de las pruebas de HU-19/HU-21/HU-22), `public/assets/plugins/chart.js/chart.umd.min.js` (DI-06) |

---

## 4. Orden de ejecución

```text
HU-17 → HU-20 → HU-18 → HU-19 → HU-16 → HU-21 → HU-22
```

- HU-21 y HU-22 pueden ejecutarse en paralelo entre sí después de vendorizar Chart.js.
- HU-18 depende de `NotificacionService` (HU-20) por CA-18.10.
- HU-16 se implementa al final para consumir servicios y esquema estables.
- Antes de HU-17: sanear working tree, verificar `max_allowed_packet` y cumplir H2-07.

---

## 5. Checklist de cierre de cada HU

- [ ] Leí HU compacta, README, consolidado H2 y trazabilidad.
- [ ] Usé solo tablas/columnas del SQL canónico más las migraciones D2/DI.
- [ ] Registré D2 y rutas en los documentos base antes de codificar (H2-07).
- [ ] Implementé todos los escenarios y CAs con prueba o verificación.
- [ ] Apliqué las decisiones D2/H2/DI que corresponden a la HU.
- [ ] Validé alcance por rol en el servidor (Admin global / Propietario propio).
- [ ] Los accesos denegados devuelven 403 con vista amigable; inexistentes, 404.
- [ ] CSRF en todos los POST y PRG en las mutaciones.
- [ ] Mensajes en español, literales según el catálogo.
- [ ] No rompí Hito 1 (throttle 900, HU-08/09/12 integradas, suite base).
- [ ] Ejecuté tests contra `serviinmueble_test` (o reporté NO VERIFICABLE).
- [ ] Dejé la matriz CA → archivo/test y el listado de comandos.

---

## 6. Diferencias con el prompt general del Hito 1

| Punto | Hito 1 | Hito 2 |
|---|---|---|
| Fuentes | `hito1/HU_Hito1_compacto.txt` | `hito2/README.md` + `HU_Hito2_compacto.md` + trazabilidad |
| Decisiones | D-01…D-30, P-01…P-05 | D-01…D-30 vigentes + D2-01…D2-13 + DI-01…DI-09 |
| Esquema | Sin tabla `notificacion` | 3 migraciones nuevas; `transaccion` con estado de validación |
| Rutas | Sin pagos | 15 rutas canónicas de pagos, mora, notificaciones e informes |
| Notificaciones | Email de bienvenida | Solo internas; sin email |
| Gráficos | No aplica | Chart.js local, sin CDN |
| Mensajes | Por HU | Catálogo centralizado en trazabilidad §8 |
| Cierre | D-28 permite integración diferida | Sin excepciones: ningún CA parcial |
| Verificación | Suite 124/773 OK | Misma base + re-verificación A3–A5/B1–B2 con BD |
| Orden | 00→01→…→15 | 17→20→18→19→16→21→22 |

---

## 7. Errores que este prompt busca evitar

- Inventar rutas o controladores fuera del mapa de la sección 6.
- Guardar `vencida` en `factura.estado`.
- Usar `debito automático` con tilde en vez de `debito automatico`.
- Cambiar el estado de la factura al registrar el pago (debe esperar validación).
- Aceptar pagos parciales o montos distintos del total.
- Adjuntar comprobantes sin validar contenido o superar 5 MB.
- Servir comprobantes sin control de propietario/Admin o con MIME calculado por extensión.
- Duplicar notificaciones por evento.
- Enviar emails o depender de un cron.
- Mezclar facturas anuales en gráficos mensuales.
- Usar CDN de Chart.js o SQLite en tests.
- Modificar `bd/serviinmueble (1).sql`.
- Reintroducir deuda ya cerrada de Hito 1.
