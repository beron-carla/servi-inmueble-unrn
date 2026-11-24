# HU · Hito 2 · ServiInmueble 2026 (compacto)

**Proyecto:** ServiInmueble 2026  
**Fecha:** 2026-11-20  
**Estado:** definición cerrada y decisiones aprobadas. Implementación de HU-16, HU-21 y HU-22 incorporada; ver estado de trazabilidad actualizado al 2026-11-24.
**Fuente de verdad:** este documento + [`hito2/README.md`](README.md) + documentos individuales `hito2/HU-16_compacto.md` a `hito2/HU-22_compacto.md`.

---

## 1. Alcance

| Línea | HUs |
|---|---|
| Administración general | HU-16 |
| Facturación y pagos | HU-17, HU-18, HU-19, HU-20 |
| Informes y métricas | HU-21, HU-22 |

| HU | Título | Grupo |
|---|---|---|
| HU-16 | Dashboard de métricas globales | 6 · Administración general |
| HU-17 | Registro de pago con comprobante digital | 7 · Facturación y pagos |
| HU-18 | Validación de pagos | 7 · Facturación y pagos |
| HU-19 | Control de mora | 7 · Facturación y pagos |
| HU-20 | Notificaciones internas | 7 · Facturación y pagos |
| HU-21 | Informe de consumo interanual | 8 · Informes y métricas |
| HU-22 | Promedio de consumo por unidad de medida | 8 · Informes y métricas |

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

## 3. Reglas globales del Hito 2

- Se mantienen las reglas del Hito 1: password, email único case-insensitive, bajas lógicas, 403 amigable y ocultamiento de acciones no permitidas en la UI.
- Ocultar en la UI es solo UX; la autorización y el alcance por rol se validan siempre en el servidor.
- El Administrador ve todas las métricas y datos; el Propietario solo los de sus inmuebles.
- Los importes se muestran en ARS con 2 decimales y las fechas en dd/mm/aaaa (salvo indicación específica).
- No se envían emails ni notificaciones externas en el Hito 2.
- No se modifican el ENUM `factura.estado` ni las reglas de Hito 1; "vencida" sigue siendo un estado calculado.

---

## HU-16 · Dashboard de métricas globales

**Grupo 6:** ADMINISTRACIÓN GENERAL

Como Administrador o Propietario, quiero ver un panel con métricas de la inmobiliaria, para monitorear el estado de la facturación y priorizar acciones.

**Contexto (Antecedentes):** El Hito 1 entrega un dashboard que solo saluda al usuario. Ya existen usuarios, inmuebles, facturas, pagos (`transaccion`) y notificaciones internas.

### Reglas globales aplicables

- Se mantienen las reglas del Hito 1: password, email único case-insensitive, bajas lógicas, 403 amigable y ocultamiento de acciones no permitidas en la UI.
- El Administrador ve todas las métricas; el Propietario solo las de sus inmuebles.
- Los importes se muestran en ARS con 2 decimales y las fechas en dd/mm/aaaa.
- Ocultar en la UI no reemplaza la validación del servidor: el alcance se filtra siempre en el backend.

### Escenarios

- Panel del Administrador: ve las métricas de todo el sistema.
- Panel del Propietario: ve solo las métricas de sus inmuebles.
- Sin datos: las tarjetas muestran 0 y los gráficos muestran "Sin datos".
- Navegación: al hacer clic en una tarjeta se abre el listado correspondiente con el filtro aplicado.

### Criterios de aceptación

- **CA-16.1** El panel del Administrador muestra: usuarios activos (total, administradores y propietarios), inmuebles activos, facturas pendientes (cantidad y monto), facturas vencidas (cantidad y monto), pagos pendientes de validación (cantidad) y recaudación del mes en curso (suma de pagos validados con fecha de pago del mes).
- **CA-16.2** El panel del Propietario muestra las mismas métricas, excepto usuarios, limitadas a sus inmuebles: inmuebles activos, facturas pendientes y vencidas (cantidad y monto), pagos pendientes de validación, recaudación del mes y notificaciones sin leer.
- **CA-16.3** Gráfico 1: evolución de los últimos 12 meses con dos series, facturación emitida (suma de `monto_total` por `fecha_emision`) y cobranza (suma de `monto_abonado` de pagos validados por `fecha_pago`).
- **CA-16.4** Gráfico 2: distribución de facturas por estado calculado (pendiente, vencida, pagada) del año en curso.
- **CA-16.5** Todo cálculo se filtra en el servidor por rol: el Propietario nunca recibe datos de inmuebles ajenos, aunque manipule parámetros de la URL.
- **CA-16.6** Cada tarjeta enlaza al listado correspondiente con el filtro aplicado (facturas pendientes, vencidas, pagos pendientes, etc.).
- **CA-16.7** Si no hay datos, las tarjetas muestran 0 y los gráficos muestran "Sin datos" sin errores.
- **CA-16.8** Los importes se muestran con separador de miles y 2 decimales; los porcentajes con 1 decimal.

---

## HU-17 · Registro de pago con comprobante digital

**Grupo 7:** FACTURACIÓN Y PAGOS

Como Propietario o Administrador, quiero registrar el pago de una factura adjuntando su comprobante, para dejar constancia y que el Administrador lo valide.

**Contexto (Antecedentes):** Propietario logueado con una factura pendiente o vencida. El pago queda pendiente hasta la validación del Administrador.

### Reglas globales aplicables

- Se mantienen las reglas del Hito 1: password, email único case-insensitive, bajas lógicas, 403 amigable y ocultamiento de acciones no permitidas en la UI.
- Solo el Administrador valida pagos; el Propietario solo registra pagos de sus propias facturas.
- El pago debe ser por el total de la factura: no se admiten pagos parciales en el Hito 2.
- Comprobantes: PDF, JPG o JPEG, hasta 5 MB, validados por contenido.
- Un pago no validado no cambia el estado de la factura.

### Escenarios

- Pago exitoso con comprobante: queda pendiente de validación y la factura sigue pendiente.
- Pago en efectivo: permite adjuntar comprobante o no.
- Monto distinto al total: se rechaza con el mensaje definido.
- Factura ya pagada: no admite un nuevo pago.
- Pago pendiente duplicado: no se permite otro pago mientras el anterior espera validación.
- Comprobante inválido: formato o tamaño fuera de lo permitido.
- Pago tardío: se registra con `en_mora = 1`.
- Acceso denegado: un Propietario no puede registrar pagos de facturas ajenas.

### Criterios de aceptación

- **CA-17.1** Puede registrar el pago el propietario de la factura o un Administrador; cualquier otro usuario recibe 403.
- **CA-17.2** Solo se admite sobre facturas en estado pendiente o vencida (calculado). Si la factura está pagada: "La factura ya está pagada".
- **CA-17.3** `monto_abonado` debe ser igual a `monto_total`; si no: "El monto debe ser igual al total de la factura". No se admiten pagos parciales.
- **CA-17.4** `fecha_pago` es obligatoria, válida y no futura: "La fecha de pago no puede ser futura".
- **CA-17.5** `medio_pago` es obligatorio y debe ser uno de {transferencia, débito automático, efectivo, billetera virtual}. `numero_comprobante` es obligatorio, alfanumérico, de 1 a 50 caracteres y único por factura.
- **CA-17.6** El comprobante es obligatorio para transferencia, débito automático y billetera virtual; es opcional para efectivo. Si se adjunta, el formato debe ser PDF, JPG o JPEG, máximo 5 MB, validado por contenido. Errores: "El comprobante debe ser PDF, JPG o JPEG" y "El comprobante no puede superar los 5 MB".
- **CA-17.7** Se guarda `en_mora = 1` si `fecha_pago` es posterior al vencimiento aplicable (2.º si existe; si no, 1.º); en caso contrario, 0.
- **CA-17.8** El pago se crea en estado "pendiente de validación" y la factura sigue pendiente. Si ya existe un pago pendiente para la factura: "Ya existe un pago pendiente de validación para esta factura".
- **CA-17.9** La operación usa CSRF y patrón PRG; éxito: "Pago registrado. Queda pendiente de validación."
- **CA-17.10** El comprobante no es accesible públicamente; solo el propietario de la factura o un Administrador pueden verlo o descargarlo.

---

## HU-18 · Validación de pagos

**Grupo 7:** FACTURACIÓN Y PAGOS

Como Administrador, quiero revisar, validar o rechazar los pagos cargados, para confirmar la cobranza.

**Contexto (Antecedentes):** Existen pagos en estado pendiente de validación cargados por los propietarios.

### Reglas globales aplicables

- Se mantienen las reglas del Hito 1: password, email único case-insensitive, bajas lógicas, 403 amigable y ocultamiento de acciones no permitidas en la UI.
- Solo el Administrador valida o rechaza pagos.
- Un pago validado marca la factura como pagada; un pago rechazado no altera el estado de la factura.
- Los pagos validados son inmutables y no se eliminan.

### Escenarios

- Listado de pagos pendientes.
- Detalle de un pago con su comprobante.
- Validación exitosa: la factura queda pagada y el propietario recibe notificación interna.
- Rechazo con motivo: la factura sigue pendiente, el propietario recibe notificación con el motivo y puede volver a cargar el pago.
- Doble validación o pago ya procesado.
- Acceso denegado: un Propietario no accede al panel de validación.

### Criterios de aceptación

- **CA-18.1** Solo el Administrador accede al listado y a las acciones de validación; cualquier otro rol recibe 403.
- **CA-18.2** El listado permite filtrar por estado, propietario, factura, medio de pago y rango de fechas. Columnas: fecha de pago, propietario, inmueble, factura, monto, medio, comprobante y estado.
- **CA-18.3** El detalle muestra pago, factura, propietario, inmueble y permite previsualizar o descargar el comprobante.
- **CA-18.4** Validar: en una transacción, el pago pasa a "validada", se registran el Administrador validador y la fecha, y la factura pasa a "pagada". Si el pago ya fue procesado: "El pago ya fue procesado".
- **CA-18.5** Rechazar: exige un motivo de 1 a 255 caracteres; el pago pasa a "rechazada", la factura conserva su estado y el propietario puede cargar un nuevo pago. Sin motivo: "El motivo del rechazo es obligatorio".
- **CA-18.6** El comprobante solo lo ven el propietario de la factura y los Administradores; se sirve con el Content-Type y el nombre original, sin ejecución.
- **CA-18.7** Los pagos validados son inmutables: no se editan ni se eliminan. Los rechazados se conservan como historial.
- **CA-18.8** Sin pagos pendientes: "No hay pagos pendientes de validación".
- **CA-18.9** Al validar o rechazar se actualizan las métricas del dashboard.
- **CA-18.10** Al validar se genera una notificación interna `pago_validado` al propietario. Al rechazar se genera `pago_rechazado` al propietario incluyendo el motivo registrado (HU-20).

---

## HU-19 · Control de mora

**Grupo 7:** FACTURACIÓN Y PAGOS

Como Administrador o Propietario, quiero identificar facturas y pagos en mora, para hacer seguimiento de la cobranza.

**Contexto (Antecedentes):** Una factura pendiente cuyo vencimiento aplicable ya pasó se muestra como vencida. El pago tardío se registra con `en_mora = 1`. El Administrador ve toda la mora; el Propietario solo la de sus inmuebles.

### Reglas globales aplicables

- Se mantienen las reglas del Hito 1: password, email único case-insensitive, bajas lógicas, 403 amigable y ocultamiento de acciones no permitidas en la UI.
- El Administrador ve la mora de todos los inmuebles; el Propietario solo la de los propios.
- El vencimiento aplicable es el segundo si existe; en caso contrario, el primero.
- En el Hito 2 no se aplican intereses, recargos ni bloqueos automáticos por mora.

### Escenarios

- Factura vencida: aparece en el reporte con sus días de mora.
- Pago tardío: se registra con `en_mora = 1`.
- Pago tardío validado: la factura pasa a pagada y el pago conserva la marca de mora.
- Filtros por propietario, inmueble, vencimiento y días de mora.
- Sin mora: el reporte muestra el estado vacío correspondiente.

### Criterios de aceptación

- **CA-19.1** Una factura está en mora si sigue pendiente y su vencimiento aplicable es anterior a hoy. Es el mismo universo que el estado calculado "vencida".
- **CA-19.2** Un pago está en mora si `fecha_pago` es posterior al vencimiento aplicable; se guarda `en_mora = 1` al registrarse y no cambia después.
- **CA-19.3** El reporte muestra propietario, inmueble, servicio, período, vencimiento, días de mora y monto, ordenado por días de mora descendente.
- **CA-19.4** Filtros: propietario (solo Administrador), inmueble, rango de vencimiento y rango de días de mora. El Propietario solo ve sus facturas.
- **CA-19.5** El dashboard muestra cantidad y monto de facturas en mora y cantidad de pagos tardíos.
- **CA-19.6** Si un pago tardío se valida, la factura pasa a pagada pero el pago conserva `en_mora = 1` para el historial.
- **CA-19.7** Los días de mora se calculan como hoy menos el vencimiento aplicable, con mínimo 0.
- **CA-19.8** El Hito 2 no aplica intereses, recargos ni bloqueos automáticos por mora.

---

## HU-20 · Notificaciones internas

**Grupo 7:** FACTURACIÓN Y PAGOS

Como Propietario, quiero recibir notificaciones internas, para enterarme de vencimientos y del estado de mis pagos.

**Contexto (Antecedentes):** No existe un sistema de notificaciones. Se incorpora una campanita en la barra superior y un listado interno con avisos generados por la aplicación.

### Reglas globales aplicables

- Se mantienen las reglas del Hito 1: password, email único case-insensitive, bajas lógicas, 403 amigable y ocultamiento de acciones no permitidas en la UI.
- Las notificaciones son internas: en el Hito 2 no se envían emails ni mensajes externos.
- El destinatario de las notificaciones es el Propietario de la factura. El Administrador puede ver el listado global en modo lectura para soporte, sin avisos personales ni campanita propia por cada factura.
- Una notificación no modifica el estado de la factura ni del pago.

### Escenarios

- Aviso "por vencer": factura pendiente cuyo vencimiento aplicable cae dentro de los próximos 7 días.
- Aviso "vencida": factura pendiente cuyo vencimiento aplicable ya pasó.
- Aviso "pago validado": el Administrador valida un pago del propietario.
- Aviso "pago rechazado": el Administrador rechaza un pago e indica el motivo.
- Marcar una notificación como leída.
- Marcar todas como leídas.
- Sin duplicados para el mismo evento.
- Acceso a notificaciones ajenas: 403.
- Sin notificaciones: mensaje de estado vacío.

### Criterios de aceptación

- **CA-20.1** Existen cuatro tipos de notificación: `por_vencer`, `vencida`, `pago_validado` y `pago_rechazado`.
- **CA-20.2** `por_vencer`: factura pendiente con vencimiento aplicable entre hoy y hoy + 7 días inclusive (ventana configurable, 7 días por defecto). `vencida`: factura pendiente con vencimiento aplicable anterior a hoy.
- **CA-20.3** `pago_validado`: se genera al validar un pago (HU-18). `pago_rechazado`: se genera al rechazar un pago (HU-18) e incluye el motivo registrado.
- **CA-20.4** La generación es idempotente y sin duplicados: como máximo una notificación por usuario, factura y tipo para vencimientos; y como máximo una por usuario, transacción y tipo para pagos.
- **CA-20.5** El destinatario es el propietario de la factura. El Administrador puede consultar el listado global en modo lectura para soporte, sin recibir notificaciones personales ni contador de campanita por cada evento.
- **CA-20.6** La barra superior muestra una campanita con el contador de notificaciones no leídas del usuario destinatario. El listado se ordena por fecha de creación descendente y pagina.
- **CA-20.7** El destinatario puede marcar una notificación o todas como leídas. El Administrador no marca como leídas las notificaciones de terceros en el listado global.
- **CA-20.8** Acceder, marcar o consultar una notificación ajena fuera del alcance permitido devuelve 403; una inexistente devuelve 404.
- **CA-20.9** Sin notificaciones: "No tenés notificaciones".
- **CA-20.10** Las notificaciones de vencimiento se generan al ingresar al dashboard o al listado de facturas, sin depender de un cron. Las de pago se generan al validar o rechazar en HU-18. En el Hito 2 no se envían emails.
- **CA-20.11** Cada notificación enlaza a la factura correspondiente; las de pago muestran el estado del pago y, en caso de rechazo, el motivo.

---

## HU-21 · Informe de consumo interanual

**Grupo 8:** INFORMES Y MÉTRICAS

Como Administrador o Propietario, quiero comparar el consumo mensual de un inmueble entre años, para detectar variaciones.

**Contexto (Antecedentes):** Las facturas guardan período, consumo y unidad de medida. El Hito 1 ya calcula y muestra facturas por período.

### Reglas globales aplicables

- Se mantienen las reglas del Hito 1: password, email único case-insensitive, bajas lógicas, 403 amigable y ocultamiento de acciones no permitidas en la UI.
- El Administrador accede a cualquier inmueble; el Propietario solo a los propios.
- Los datos se filtran en el servidor por rol.
- Los importes se muestran en ARS con 2 decimales.

### Escenarios

- Comparación de dos años para un inmueble y servicio.
- Cambio de inmueble, servicio o unidad.
- Meses sin factura: se muestran en 0.
- Facturas anuales: se informan aparte.
- Sin datos para los filtros elegidos.
- Acceso a un inmueble ajeno: 403.

### Criterios de aceptación

- **CA-21.1** El Administrador accede a cualquier inmueble; el Propietario solo a los propios; cualquier otro caso devuelve 403.
- **CA-21.2** Filtros: inmueble (obligatorio), servicio (opcional), año base (por defecto el actual), año comparado (por defecto el anterior) y unidad de medida.
- **CA-21.3** El gráfico muestra 12 puntos por año con el consumo mensual de cada uno; los meses sin factura valen 0.
- **CA-21.4** La tabla muestra mes, consumo del año base, consumo del año comparado, diferencia y variación porcentual, e incluye los totales anuales.
- **CA-21.5** Solo se incluyen facturas mensuales (`periodo = 'mes'`); las anuales se muestran en un resumen aparte.
- **CA-21.6** El agrupamiento usa `periodo_anio` y `periodo_mes`; no se mezclan inmuebles ni servicios no seleccionados.
- **CA-21.7** Sin datos: "No hay datos de consumo para los filtros seleccionados".
- **CA-21.8** Para servicios con unidad "Moneda fija" el gráfico muestra montos y la etiqueta "Monto", no consumo.
- **CA-21.9** Si el valor del año base es 0, la variación se muestra como "—" y no se divide por cero.

---

## HU-22 · Promedio de consumo por unidad de medida

**Grupo 8:** INFORMES Y MÉTRICAS

Como Administrador o Propietario, quiero ver el consumo promedio por unidad de medida, para comparar el comportamiento entre servicios e inmuebles.

**Contexto (Antecedentes):** La factura guarda el snapshot de `unidad_medida` y `consumo_del_periodo`. Las unidades del catálogo son kWh, m³, Gigas y Moneda fija.

### Reglas globales aplicables

- Se mantienen las reglas del Hito 1: password, email único case-insensitive, bajas lógicas, 403 amigable y ocultamiento de acciones no permitidas en la UI.
- El Administrador ve todos los inmuebles; el Propietario solo los propios.
- Los datos se filtran en el servidor por rol.
- Los importes se muestran en ARS con 2 decimales.

### Escenarios

- Promedio de consumo para kWh, m³ y Gigas.
- Promedio de monto para "Moneda fija".
- Filtros por período, inmueble, tipo de servicio y unidad.
- Sin datos para los filtros elegidos.
- Acceso a datos ajenos: 403.

### Criterios de aceptación

- **CA-22.1** El Administrador ve todos los inmuebles; el Propietario solo ve los propios; cualquier otro caso devuelve 403.
- **CA-22.2** Filtros: rango de períodos (desde y hasta), inmueble (opcional; por defecto todos los permitidos por el rol), tipo de servicio y unidad de medida.
- **CA-22.3** Para kWh, m³ y Gigas se promedia `consumo_del_periodo` por unidad de medida, informando cantidad de facturas, promedio, mínimo y máximo.
- **CA-22.4** Para "Moneda fija" se promedia `monto_total` y se rotula "Monto promedio", porque el consumo no aplica.
- **CA-22.5** El gráfico de barras muestra el promedio por unidad de medida y la tabla informa unidad, cantidad de facturas, promedio, mínimo y máximo.
- **CA-22.6** Solo se incluyen facturas mensuales (`periodo = 'mes'`) para que los promedios sean comparables; las facturas anuales se informan en un resumen aparte.
- **CA-22.7** Sin datos: "No hay datos para los filtros seleccionados".
- **CA-22.8** Los consumos se muestran con 2 decimales y los montos en ARS con 2 decimales.

---

## 4. Impacto en el esquema

Se conserva aquí el diseño de esquema aprobado; las migraciones efectivamente aplicadas se registran en `docs/reportes/resultado_por_prompt.md`.

- **`transaccion`**: agregar `estado` ENUM('pendiente','validada','rechazada') DEFAULT 'pendiente', `id_admin_validador` (FK usuario, NULL), `fecha_validacion` (DATETIME NULL), `motivo_rechazo` (VARCHAR(255) NULL), `comprobante_nombre` (VARCHAR(255) NULL), `comprobante_mime` (VARCHAR(100) NULL), índice único (`id_factura`,`numero_comprobante`), índice (`estado`,`fecha_pago`).
- **`notificacion` (nueva)**: `id_notificacion`, `id_usuario` (FK), `id_factura` (FK), `id_transaccion` (FK NULL), `tipo` ENUM('por_vencer','vencida','pago_validado','pago_rechazado'), `mensaje`, `leida`, `fecha_creacion`; idempotencia por evento (vencimiento: usuario+factura+tipo; pago: usuario+transacción+tipo) e índice (`id_usuario`,`leida`,`fecha_creacion`).
- **Índices**: `factura(estado, fecha_primer_vencimiento, fecha_segundo_vencimiento)`, `factura(periodo_anio, periodo_mes, id_propiedad_servicio)`.
- `factura.estado` no se modifica.

## 5. Rutas definidas

| Método | Ruta | Filtro | HU |
|---|---|---|---|
| GET | `/dashboard` | auth | HU-16 |
| GET | `/facturas/(:num)/pagar` | auth | HU-17 |
| POST | `/facturas/(:num)/pagos` | auth | HU-17 |
| GET | `/pagos` | auth | HU-17/18 |
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

Las rutas de HU-16, HU-21 y HU-22 están implementadas; su evidencia figura en la matriz de estado.

## 6. Fuera de alcance del Hito 2

- Pagos parciales o saldo a favor.
- Intereses, recargos o punitorios por mora.
- Pasarela de pagos o débito automático real.
- Envío de emails o notificaciones push.
- Generación de PDF de facturas o comprobantes.
- Edición o eliminación de pagos ya registrados.

## 7. Estado de implementación y trazabilidad · 2026-11-24

| HU | Criterios | Implementación y evidencia | Estado |
|---|---|---|---|
| HU-16 | CA-16.1 a CA-16.8 | Dashboard y métricas en `app/Controllers/DashboardController.php`, `app/Services/DashboardService.php`, `app/Repositories/DashboardRepository.php` y `app/Views/dashboard/index.php`; `tests/feature/DashboardMetricasTest.php`; commit `aeea491`. | Implementada; las 3 pruebas dirigidas pasan. |
| HU-21 | CA-21.1 a CA-21.9 | Informe interanual en `app/Controllers/InformesController.php`, `app/Services/InformeService.php`, `app/Repositories/InformeRepository.php` y `app/Views/informes/consumo-interanual.php`; `tests/feature/InformesTest.php`; commit `2363c54`. | Implementada; las 4 pruebas dirigidas pasan. |
| HU-22 | CA-22.1 a CA-22.8 | Promedios por unidad en `app/Controllers/InformesController.php`, `app/Services/InformeService.php`, `app/Repositories/InformeRepository.php` y `app/Views/informes/promedio-unidad.php`; `tests/feature/InformesPromedioUnidadTest.php`; commit `15527cb`. | Implementada; validación dirigida parcialmente satisfactoria: 4 de 5 pruebas pasan. |

La ejecución conjunta de las tres suites el 2026-11-24 terminó con 11 de 12 pruebas y 56 aserciones; falló `InformesPromedioUnidadTest::testRangoMensualAgregaConsumosYMontosYSeparaFacturasAnuales`, que esperaba encontrar `20,00` en la respuesta. El detalle está registrado en `docs/reportes/resultado_por_prompt.md`; el cierre de implementación no equivale a una suite completamente verde.

## 8. Notas de trazabilidad

- **H2-01:** La validación de pagos introduce un estado intermedio en `transaccion`; la factura solo pasa a `pagada` al validar (D2-01, HU-17/HU-18).
- **H2-02:** `en_mora` en `transaccion` registra pagos tardíos históricos; la mora de facturas pendientes se calcula por vencimiento aplicable (HU-19, CA-19.1/19.2).
- **H2-03:** Las notificaciones de pago se disparan en las acciones de HU-18 y se entregan por HU-20; no generan efectos secundarios sobre factura/pago (D2-09).
- **H2-04:** El alcance por rol (Admin global, Propietario propio) es transversal a HU-16, HU-19, HU-20, HU-21 y HU-22 y debe validarse en servidor (D2-05).
- **H2-05:** Chart.js se vendoriza localmente (D2-06); la elección no altera los CA de los gráficos.
- **H2-06:** Los documentos individuales de cada HU están en `docs/historias_de_usuario/hito2/HU-16_compacto.md` a `HU-22_compacto.md`. El índice y decisiones están en `docs/historias_de_usuario/hito2/README.md`.
- **H2-07:** Preparación previa a la implementación cumplida: rutas en `docs/rutas.md`, decisiones en `docs/decisiones.md` y migraciones de Hito 2 registradas en el reporte de resultados.

---

_Fin del documento Hito 2 (compacto)._
