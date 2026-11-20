# Hito 2 · Historias de usuario

**Proyecto:** ServiInmueble 2026
**Fecha:** 2026-11-20
**Estado:** definición cerrada (decisiones aprobadas). **Sin implementación.**
**Fuente:** alcance del Hito 2 definido por el equipo + decisiones aprobadas el 2026-11-20.

## 1. Alcance

El Hito 2 se organiza en tres líneas:

| Línea | HUs |
|---|---|
| Administración general | HU-16 |
| Facturación y pagos | HU-17, HU-18, HU-19, HU-20 |
| Informes y métricas | HU-21, HU-22 |

| HU | Título | Grupo |
|---|---|---|
| [HU-16](HU-16_compacto.md) | Dashboard de métricas globales | 6 · Administración general |
| [HU-17](HU-17_compacto.md) | Registro de pago con comprobante digital | 7 · Facturación y pagos |
| [HU-18](HU-18_compacto.md) | Validación de pagos | 7 · Facturación y pagos |
| [HU-19](HU-19_compacto.md) | Control de mora | 7 · Facturación y pagos |
| [HU-20](HU-20_compacto.md) | Notificaciones internas | 7 · Facturación y pagos |
| [HU-21](HU-21_compacto.md) | Informe de consumo interanual | 8 · Informes y métricas |
| [HU-22](HU-22_compacto.md) | Promedio de consumo por unidad de medida | 8 · Informes y métricas |

## 2. Decisiones aprobadas

- **D2-01 · Validación de pagos:** el Administrador debe validar cada pago. Mientras tanto, el pago queda "pendiente de validación" y la factura no cambia de estado.
- **D2-02 · Monto del pago:** debe ser igual al total de la factura. No se admiten pagos parciales en el Hito 2.
- **D2-03 · Avisos internos:** se resuelven dentro de la aplicación con un sistema interno de notificaciones. No se envían emails ni mensajes externos en este hito.
- **D2-04 · Promedio:** el informe de promedio se agrupa por unidad de medida (kWh, m³, Gigas, Moneda fija). En informes, kWh/m³/Gigas se miden por consumo y Moneda fija por monto.
- **D2-05 · Alcance de métricas:** el Administrador ve todas las métricas; el Propietario solo las de sus inmuebles. El alcance se filtra siempre en el servidor.
- **D2-06 · Gráficos:** se vendoriza Chart.js en `public/assets/plugins/chart.js/` (carga local, sin CDN), para funcionar sin conexión en XAMPP/demo.
- **D2-07 · Comprobante:** obligatorio para transferencia, débito automático y billetera virtual; opcional para efectivo. Si se adjunta, siempre se valida formato y tamaño (PDF/JPG/JPEG, máx. 5 MB, validado por contenido).
- **D2-08 · Ventana "por vencer":** 7 días corridos por defecto antes del vencimiento aplicable, valor configurable en la aplicación.
- **D2-09 · Notificaciones al propietario:** además de "por vencer" y "vencida", el propietario recibe notificaciones internas `pago_validado` y `pago_rechazado`. El rechazo incluye el motivo.
- **D2-10 · Notificaciones del Administrador:** el Administrador puede ver el listado global de notificaciones en modo lectura para soporte, sin avisos personales ni campanita propia por cada factura.
- **D2-11 · Unicidad de comprobante:** `numero_comprobante` es único por factura (`id_factura`, `numero_comprobante`).
- **D2-12 · Facturas anuales en informes:** los informes mensuales (interanual y promedio) usan solo `periodo = 'mes'`; las facturas anuales se informan en un resumen aparte.
- **D2-13 · Base de recaudación:** la recaudación usa `fecha_pago` de los pagos validados.

## 3. Decisiones pendientes

No quedan decisiones pendientes para la definición de las HU del Hito 2. Las migraciones, rutas definitivas y detalles de UI se resolverán antes de implementar cada HU, sin contradecir este documento.

## 4. Impacto previsto en el esquema

> No se implementa en esta etapa. Se documenta para que las migraciones del Hito 2 se diseñen antes de codificar.

### `transaccion`

La tabla existe, pero no alcanza para el flujo de validación:

- `estado` ENUM('pendiente','validada','rechazada') NOT NULL DEFAULT 'pendiente'.
- `id_admin_validador` INT NULL, FK a `usuario(id_usuario)`.
- `fecha_validacion` DATETIME NULL.
- `motivo_rechazo` VARCHAR(255) NULL.
- `comprobante_nombre` VARCHAR(255) NULL (nombre original del archivo).
- `comprobante_mime` VARCHAR(100) NULL (para servir el archivo con el Content-Type correcto).
- Índice único `uk_transaccion_factura_comprobante` (`id_factura`, `numero_comprobante`).
- Índice `idx_transaccion_estado_pago` (`estado`, `fecha_pago`).

`comprobante_digital` ya existe como MEDIUMBLOB; el límite de 5 MB se valida en la aplicación.

### `notificacion` (nueva)

- `id_notificacion` INT AUTO_INCREMENT PRIMARY KEY.
- `id_usuario` INT NOT NULL, FK a `usuario(id_usuario)` (destinatario).
- `id_factura` INT NOT NULL, FK a `factura(id_factura)`.
- `id_transaccion` INT NULL, FK a `transaccion(id_transaccion)` (para notificaciones de pago).
- `tipo` ENUM('por_vencer','vencida','pago_validado','pago_rechazado') NOT NULL.
- `mensaje` VARCHAR(255) NOT NULL.
- `leida` TINYINT(1) NOT NULL DEFAULT 0.
- `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP.
- Índice único de idempotencia para vencimientos: (`id_usuario`, `id_factura`, `tipo`) aplicable cuando `id_transaccion` es NULL.
- Índice único de idempotencia para pagos: (`id_usuario`, `id_transaccion`, `tipo`) aplicable cuando `id_transaccion` no es NULL.
- Índice `idx_notificacion_usuario_leida` (`id_usuario`, `leida`, `fecha_creacion`) para contador y listado.

> Nota de diseño: la unicidad diferenciada por tipo de evento (vencimiento vs. pago) debe resolverse en la migración (índices parciales no están disponibles en MariaDB/MySQL de la misma forma que en otros motores; alternativa: `tipo_evento`/clave compuesta o unicidad lógica + índice). Se define al implementar la migración.

### Índices de consulta

- `factura(estado, fecha_primer_vencimiento, fecha_segundo_vencimiento)`.
- `factura(periodo_anio, periodo_mes, id_propiedad_servicio)`.

No se modifica el ENUM `factura.estado`: sigue siendo `pendiente`/`pagada`; "vencida" se calcula.

## 5. Rutas previstas

> Deben agregarse a `docs/rutas.md` y aprobarse antes de implementar. Ninguna se crea por auto-routing.

| Método | Ruta | Filtro | HU |
|---|---|---|---|
| GET | `/dashboard` | auth | HU-16 (se amplía) |
| GET | `/facturas/(:num)/pagar` | auth | HU-17 |
| POST | `/facturas/(:num)/pagos` | auth | HU-17 |
| GET | `/pagos` | auth | HU-17/18 (mis pagos) |
| GET | `/pagos/(:num)/comprobante` | auth | HU-17/18 |
| GET | `/admin/pagos` | auth, role:admin | HU-18 |
| GET | `/admin/pagos/(:num)` | auth, role:admin | HU-18 |
| POST | `/admin/pagos/(:num)/validar` | auth, role:admin | HU-18 |
| POST | `/admin/pagos/(:num)/rechazar` | auth, role:admin | HU-18 |
| GET | `/mora` | auth | HU-19 |
| GET | `/notificaciones` | auth | HU-20 |
| POST | `/notificaciones/(:num)/leer` | auth | HU-20 |
| POST | `/notificaciones/leer-todas` | auth | HU-20 |
| GET | `/informes/consumo-interanual` | auth | HU-21 |
| GET | `/informes/promedio-unidad` | auth | HU-22 |

## 6. Sobre los gráficos

**Decisión aprobada (D2-06):** Chart.js se vendoriza en `public/assets/plugins/chart.js/` y se carga localmente, sin CDN, para funcionar sin conexión en XAMPP y en la entrega.

Las HU exigen que los gráficos se rendericen con los datos y filtros definidos; la integración de la librería es una tarea de implementación.

## 7. Fuera de alcance del Hito 2

- Pagos parciales o saldo a favor.
- Intereses, recargos o punitorios por mora.
- Pasarela de pagos o débito automático real.
- Envío de emails o notificaciones push.
- Generación de PDF de facturas o comprobantes.
- Edición o eliminación de pagos ya registrados.

## 8. Próximos pasos

1. Agregar las rutas a `docs/rutas.md` y las decisiones D2-01 a D2-13 a `docs/decisiones.md`.
2. Diseñar las migraciones de `transaccion` y `notificacion`.
3. Implementar HU por HU, con trazabilidad CA por CA.
