# Prompt de implementación · Autogenerar número de comprobante

**Versión:** 1.0
**Fecha:** 2026-11-23
**Uso:** pegar el bloque de la sección 2 en el agente de código, después del prompt general del proyecto.
**Estado:** este documento no modifica código ni BD; solo describe el cambio.

---

## 1. Contexto

Hoy `PagoService::registrar()` exige que el propietario escriba `numero_comprobante` en
`app/Views/pagos/create.php`, lo normaliza desde `PagosController::entradaFormulario()`,
lo valida en `PagoService::validarDatos()` (obligatorio, `/^[A-Za-z0-9]{1,50}$/`, único por
factura) y lo persiste en `transaccion.numero_comprobante VARCHAR(50) NOT NULL`.

Objetivo: que el sistema genere automáticamente un código con nomenclatura fija y correlativo
único, y que el usuario no tenga que ingresarlo.

---

## 2. Prompt específico (copiar y pegar)

```text
Modificá ServiInmueble 2026 (CodeIgniter 4, PHP) para que el número de comprobante de los pagos se genere automáticamente, con nomenclatura estable y valor siempre distinto. No es un cambio de HU nueva: es una mejora sobre HU-17 (registro de pago). Respetá las convenciones del repositorio, el prompt general y las decisiones D2-11 (numero_comprobante único por factura) y DI-02.

ALCANCE / NO HACER
- NO eliminar la columna `transaccion.numero_comprobante` (sigue NOT NULL VARCHAR(50)).
- NO tocar `bd/serviinmueble (1).sql` (SQL canónico). Toda extensión se hace con migración nueva.
- NO modificar la lógica de validación de archivo, monto, fecha, medio de pago, mora, permisos, CSRF/PRG ni el flujo de validación HU-18/estado pendiente.
- NO cambiar rutas ni firma pública de `PagoService::registrar()`.
- NO reutilizar el id autoincremental como número de comprobante (la numeración debe ser legible y correlativa por fecha).

NOMENCLATURA (exacta, sin variantes)
- Formato: `CP-YYYYMMDD-NNNNNN`.
  Ejemplos: CP-20261123-000001, CP-20261123-000002, CP-20261124-000001.
- `YYYYMMDD` = fecha del pago (`fecha_pago`,_no_ la fecha de registro).
- `NNNNNN` = correlativo de 6 dígitos, reiniciado cada día, por la fecha del pago.
- Longitud 17 caracteres; cabe en VARCHAR(50). Debe ser globalmente único.
- Actualizá la validación interna de formato a `/^[A-Z]{2}-[0-9]{8}-[0-9]{6}$/` (o la que corresponda si se mantiene validación defensiva); el regex anterior `/^[A-Za-z0-9]{1,50}$/` rechaza los guiones y hay que ajustarlo en `app/Services/PagoService.php`.

ESTRATEGIA DE GENERACIÓN (concurrencia)
1. Crear migración nueva `app/Database/Migrations/2026-11-23-000001_CreateSecuenciaComprobante.php`:
   - tabla `secuencia_comprobante` con `fecha DATE NOT NULL PRIMARY KEY` y `ultimo INT UNSIGNED NOT NULL DEFAULT 0`.
   - `down()` elimina la tabla. No backfillegue filas antiguas de `transaccion`.
2. Crear `App\Services\NumeroComprobanteService` con método público
   `siguiente(string $fechaPago): string` (o `DateTimeImmutable`, mantené el estilo del proyecto):
   -dentro de la transacción ya abierta por `PagoService::registrar()`:
     a) `INSERT IGNORE INTO secuencia_comprobante (fecha, ultimo) VALUES (?, 0)`;
     b) `SELECT ultimo FROM secuencia_comprobante WHERE fecha = ? FOR UPDATE` para bloquear la fila;
     c) `UPDATE secuencia_comprobante SET ultimo = ultimo + 1 WHERE fecha = ?`;
     d) devolver `sprintf('CP-%s-%06d', $fechaCompacta, $ultimo + 1)`.
   - si la fecha del pago no es válida, lanzar `InvalidArgumentException`.
   - inyectar el servicio en el constructor de `PagoService` con patrón `??=` igual que `ComprobanteService`/`MoraService`, para no romper los tests que ya inyectan dependencias.
3. Generar el número DESPUÉS de todas las validaciones y de la comprobación de pago pendiente, e inmediatamente antes de armar `$datosPago` (línea donde hoy se copia `$datos['numero_comprobante']`). Asignar el resultado a `$datos['numero_comprobante']` también, para que el array `datos` devuelto en el resultado refleje el número real.
4. La generación vive dentro de `transBegin()/transCommit()`; si falla el inserto del pago, el `transRollback()` también revierte el correlativo, por lo que no se queman números. Si hay error 1062 por colisión, mantener el manejo actual de `DatabaseException` con un mensaje claro (no exponer SQL).

CAMBIOS EN LA INTERFAZ Y ENTRADA
- `app/Views/pagos/create.php`: quitar el `<input name="numero_comprobante">` y su bloque de error. Reemplazarlo por texto informativo tipo: "El número de comprobante se generará automáticamente al registrar el pago (formato CP-YYYYMMDD-NNNNNN).". No enviar el campo en el POST.
- `app/Controllers/PagosController.php::entradaFormulario()`: sacar `numero_comprobante` del array de campos leídos de POST.
- `app/Services/PagoService.php::normalizarEntrada()`: sacar `numero_comprobante` del array de campos normalizados.
- `app/Services/PagoService.php::validarDatos()`: eliminar las tres validaciones de número escrito por el usuario (obligatorio, formato, "Ya existe un comprobante con ese número para esta factura"). No dejar mensajes de error huérfanos para un campo que ya no existe.
- Mantener `numero_comprobante` en elINSERT de `PagoRepository::crear()` y en las vistas `pagos/index.php` y `admin/pagos/show.php` (siguen mostrando el código generado).

COMPATIBILIDAD CON DATOS EXISTENTES
- Los comprobantes ya registrados conservan su número actual (por ejemplo `DEMO-FACTURA-PAGADA` del seeder). No normalizar ni reescribir filas antiguas.
- La restricción `uk_transaccion_factura_comprobante (id_factura, numero_comprobante)` se mantiene. El nuevo código es único a nivel global, así que también cumple la unicidad por factura.
- Actualizar `app/Database/Seeds/DemoSeeder.php` solo si es necesario para que los pagos nuevos del seeder usen el servicio generador (los valores legacy se dejan intactos).

PRUEBAS (obligatorio)
- Actualizar `tests/feature/PagosTest.php` y `tests/feature/AdminPagosTest.php` para que:
  1. el POST de registro ya no envíe `numero_comprobante` y el pago se cree igual;
  2. el número guardado coincida con `/^CP-\d{8}-\d{6}$/` y use la `fecha_pago` enviada;
  3. dos pagos sobre facturas distintas con la misma `fecha_pago` produzcan `...-000001` y `...-000002` (correlativo);
  4. dos fechas distintas reinicien el correlativo a `000001`;
  5. el número generado aparezca en `pagos/index` y en `admin/pagos/show`;
  6. un rollback (por ejemplo monto inválido) no deje hueco en `secuencia_comprobante`.
- Agregar prueba de concurrencia simple si el stack de tests lo permite (dos llamadas con la misma fecha); si no, documentar por qué no es verificable.
- Ejecutar `php spark migrate` y luego la suite (`php spark phpunit` o el comando que indique `phpunit.dist.xml`). Reportar cantidad de tests/assertions y fallos. Verificar además `migrate:rollback` de la migración nueva.

ENTREGA
- Listar archivos modificados/creados, el formato final del código y los comandos ejecutados con su resultado.
- Si algo no se puede verificar (BD no disponible, etc.), escribir explícitamente `NO VERIFICABLE` y el motivo.
- Actualizar `docs/rutas.md` y los textos de validación en `docs/prompts/prompt-general-hito-2.md` / `prompt-hu-17.md` solo donde mencionen que `numero_comprobante` es ingresado por el usuario; no reescribir el resto de esos documentos.
```

---

## 3. Notas de diseño (para vos, no para pegar)

- Se eligió `CP-YYYYMMDD-NNNNNN` en lugar de un código sin guiones porque es legible; el costo es ajustar un regex en `PagoService::validarDatos()`. Si preferís no tocar el regex, la variante equivalente es `CPYYYYMMDDNNNNNN` (16 caracteres, alfanumérico puro).
- La tabla `secuencia_comprobante` con `SELECT ... FOR UPDATE` evita el problema clásico de `MAX() + 1` bajo concurrencia.
- El reinicio diario es por `fecha_pago`, no por fecha de registro, para que dos pagos registrados hoy con distinta fecha de pago no compartan correlativo.
- Si querés numeración continua sin reinicio diario, cambiá la PK por una fila única (`id = 1`) y el formato a `CP-NNNNNNNNNN`; decilo antes de ejecutar el prompt.
