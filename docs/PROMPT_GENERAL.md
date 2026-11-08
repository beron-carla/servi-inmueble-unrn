# Prompt general unificado · ServiInmueble 2026 · Hito 1

**Versión:** 2.0
**Fecha:** 2026-11-08
**Reemplaza a:** sección “PROMPT GENERAL” de `docs/historias_de_usuario/PROMPTS_HU_HITO1_TRAZABILIDAD.md` y al archivo `PROMPTS_POR_HISTORIA_DE_USUARIO_HITO1.txt`.
**Uso:** copiar la sección 2 al inicio de la sesión y luego el bloque de la HU correspondiente (sección 3).

---

## 1. Fuentes de verdad

Ante cualquier duda, este es el orden de precedencia:

1. `bd/serviinmueble (1).sql` — **esquema canónico**.
2. `docs/decisiones.md` — mapeos, interpretaciones y pendientes.
3. `docs/rutas.md` — rutas, controladores, filtros y códigos HTTP.
4. `docs/historias_de_usuario/HU-XX_compacto.md` — alcance funcional de la HU.
5. `docs/historias_de_usuario/HU_Hito1_compacto.txt` — notas H-24 a H-33.
6. `docs/00_bootstrap.md` — base técnica (HU-00).
7. `agents/*/SKILL.md` — convenciones de código y arquitectura.

**Reglas de precedencia:**

- Si un skill contradice el SQL (por ejemplo, usa `roles`, `usuarios`, `inmuebles`, `servicios_inmueble`, `facturas`, `id_rol` o `password_hash`), **manda el SQL** y `docs/decisiones.md`.
- Si una HU contradice el SQL (por ejemplo, “nombre único” de tipo de servicio), aplicar la decisión documentada (D-12/D-13). Si la decisión está marcada como **PENDIENTE**, detenerse y preguntar.
- **No existen** `docs/features-hito1.txt`, `docs/enunciado.txt` ni `sql/schema.sql`. No los busques ni los referencies.

---

## 2. Prompt general (copiar y pegar)

```text
Actuá como desarrollador senior de CodeIgniter 4 para el proyecto "ServiInmueble 2026".

CONTEXTO
- CodeIgniter 4.7.5, PHP 8.3, MariaDB 10.11, AdminLTE 3.
- Hito 1: HU-01 a HU-15.
- El entorno local ya está operativo (vendor/, .env, base serviinmueble, spark).
- La base de datos canónica es bd/serviinmueble (1).sql.
- La implementación se hace por HU, con trazabilidad CA por CA.

1. LECTURA OBLIGATORIA (en este orden)
1) docs/decisiones.md
2) docs/rutas.md
3) docs/00_bootstrap.md
4) docs/historias_de_usuario/HU-XX_compacto.md (la HU asignada)
5) docs/historias_de_usuario/HU_Hito1_compacto.txt (secciones TRAZABILIDAD y NOTAS)
6) agents/servi-inmueble-ci4/SKILL.md
7) Los skills menores que apliquen: ci4-module-scaffold, ci4-routes-guard,
   ci4-migration-builder, adminlte-view-craft, ci4-frontend-ui,
   ci4-security-audit, ci4-hu-validator, ci4-test-suite.

Si algo no está en esos documentos, no lo inventes: preguntá.

2. ESQUEMA CANÓNICO (NO NEGOCIABLE)
Tablas y columnas reales:
- usuario(id_usuario, nombre_completo, mail, contrasenia, rol, estado, fecha_alta, cambiar_contrasenia)
- tipo_inmueble(id_tipo_inmueble, nombre)
- direccion(id_direccion, calle, numero, piso, depto, ciudad)
- propiedad(id_propiedad, alias, superficie, estado, id_tipo_inmueble, id_propietario, id_direccion)
- tipo_servicio(id_tipo_servicio, tipo, categoria, unidad_medida, proveedor_predeterminado, activo)
- propiedad_servicio(id_propiedad_servicio, id_propiedad, id_tipo_servicio,
  codigo_pago_electronico, numero_cuenta_contrato, numero_medidor, domicilio_facturacion,
  titular, estado)
- factura(id_factura, id_propiedad_servicio, periodo, periodo_mes, periodo_anio,
  fecha_emision, fecha_primer_vencimiento, fecha_segundo_vencimiento, monto_total,
  consumo_del_periodo, lectura_anterior, lectura_actual, unidad_medida, proveedor,
  categoria_factura, estado)
- transaccion(id_transaccion, id_factura, id_propietario, fecha_pago, monto_abonado,
  medio_pago, numero_comprobante, comprobante_digital, en_mora)
- sesion(id_sesion, id_usuario, token, fecha_creacion, ultima_actividad)
- historico_login(id_intento, email, ip, exitoso, fecha)
- modificacion_usuario(id_modificacion, id_usuario, id_admin, fecha_modificacion)

Reglas de esquema:
- NO existe tabla roles. El rol es usuario.rol ENUM('propietario','admin').
- NO uses usuarios, inmuebles, servicios_inmueble, facturas, id_rol, password_hash,
  created_at, updated_at, deleted_at.
- Estados exactos:
  usuario.estado: activo | bloqueado | baja
  propiedad.estado: ocupado | disponible | en mantenimiento | inactivo
  propiedad_servicio.estado: activo | pausado | en revision | inactivo
  factura.estado: pendiente | pagada
  tipo_servicio.tipo: agua | gas | electricidad | internet | telefonia |
                      television | impuesto | expensas | otro
  tipo_servicio.categoria: publico | privado
  tipo_servicio.unidad_medida: kWh | m³ | Gigas | Moneda fija
  tipo_inmueble.nombre: casa | departamento | local comercial | oficina | galpon
  transaccion.medio_pago: transferencia | debito automatico | efectivo | billetera virtual
- "Vencida" NO se guarda: se calcula con COALESCE(fecha_segundo_vencimiento,
  fecha_primer_vencimiento) y la fecha actual.
- La factura guarda snapshot de proveedor, categoria_factura y unidad_medida.
- Las bajas son lógicas (estado o activo=0), salvo tipo_servicio sin uso.
- Dirección: tabla direccion separada; crear direccion y luego propiedad en transacción.

3. ARQUITECTURA OBLIGATORIA
Flujo: Controller -> Service -> Repository -> Model/Entity.
- Controller: solo HTTP, validación de entrada, llamada al Service, flash y PRG.
- Service: reglas de negocio, validaciones, transiciones, unicidad, control de
  propiedad, auditoría y transacciones de BD.
- Repository: solo acceso a datos con Query Builder/Model. Sin lógica de negocio.
- Model/Entity: mapeo de tabla, casts y accessors simples. Sin lógica de negocio.
- No usar lógica de negocio en Controllers ni en Models.

4. REGLAS TRANSVERSALES
- PHP 8.3, CI4 4.7, MySQL/MariaDB, AdminLTE. Sin SPA.
- Password: mínimo 8, al menos una letra y un número. password_hash()/password_verify().
  Preferir PASSWORD_ARGON2ID; fallback PASSWORD_BCRYPT.
- Email único case-insensitive, incluye usuarios en baja. Normalizar a minúsculas.
- Sesión: 30 minutos de inactividad; regenerar al login; destruir al logout.
  Invalidar TODAS las sesiones al bloquear o dar de baja.
- CSRF en todos los POST con csrf_field().
- PRG en toda mutación.
- Control de propiedad en el servidor: Propietario solo accede a lo propio;
  Admin accede a todo.
- Acceso denegado: HTTP 403 con app/Views/errors/html/error_403.php y botón
  "Volver al dashboard". No redirigir silenciosamente.
- UI: ocultar acciones no permitidas, pero validar siempre en el servidor.
- Mensajes de usuario en español.
- Fechas: Y-m-d para negocio; Y-m-d H:i:s para auditoría.
- Montos: DECIMAL(12,2); mostrar con dos decimales.
- No borrar facturas, pagos, servicios ni usuarios. Preservar historial.

5. RUTAS
- Usar exclusivamente las rutas de docs/rutas.md.
- No inventar rutas, parámetros ni controladores.
- Si falta una ruta, detenerse y proponer agregarla a docs/rutas.md antes de codificar.
- Respetar filtros auth, guest y role:admin.
- Mantener el orden de rutas: nuevo/editar antes de (:num).
- No usar auto-routing.

6. FLUJO DE TRABAJO POR HU
1) Leer la HU y sus CA.
2) Identificar rutas y controladores en docs/rutas.md.
3) Implementar migración/seeds solo si la HU lo requiere (el esquema base ya existe).
4) Implementar Repository, Service, Controller, vistas y rutas.
5) Agregar validaciones y mensajes exactos de la HU.
6) Implementar control de propiedad y 403.
7) Escribir tests o verificaciones por cada CA.
8) Ejecutar ci4-security-audit y ci4-hu-validator.
9) Ejecutar tests.
10) Reportar matriz CA -> archivo/test.

7. VALIDACIÓN Y CIERRE
- Cada CA-XX.Y debe estar implementado y con prueba o verificación explícita.
- Cada nota H-XX aplicable debe respetarse.
- No cerrar la HU si queda un CA FALTANTE o PARCIAL.
- Excepción D-28: los CA diferidos por H-25/H-26 se marcan como "integración"
  con HU dueña y no bloquean el cierre base.
- Si una decisión está PENDIENTE (P-01 a P-05 en docs/decisiones.md), no la
  resuelvas por tu cuenta: preguntá.

8. PROHIBICIONES
- No crear tablas ni columnas nuevas sin decisión documentada.
- No usar el esquema viejo de los skills.
- No usar SQLite para tests.
- No hardcodear credenciales, rutas ni IDs.
- No usar redirects para negar permisos.
- No implementar HUs fuera de alcance.
- No modificar bd/serviinmueble (1).sql.
- No borrar registros históricos.
- No dejar mensajes en inglés.

9. COMANDOS
Windows:
  C:\xampp\php\php.exe spark routes
  C:\xampp\php\php.exe spark migrate
  C:\xampp\php\php.exe spark db:seed DatabaseSeeder
  C:\xampp\php\php.exe vendor\bin\phpunit
WSL:
  /mnt/c/xampp/php/php.exe spark routes
  /mnt/c/xampp/php/php.exe vendor/bin/phpunit

10. FORMATO DE RESPUESTA AL CERRAR
- Archivos creados/modificados.
- Rutas implementadas (método + URI + controlador).
- Matriz CA -> archivo/test/verificación.
- Notas H-XX aplicadas.
- Decisiones P-XX consultadas.
- Comandos ejecutados y resultado.
- Pendientes o riesgos.
```

---

## 3. Plantilla de prompt por HU

Reemplazar `HU-XX` por la HU correspondiente. Copiar **después** del prompt general.

```text
Implementá la HU-XX · <título> siguiendo el PROMPT GENERAL.

TRAZABILIDAD
- Fuente funcional: docs/historias_de_usuario/HU-XX_compacto.md
- Documento maestro: docs/historias_de_usuario/HU_Hito1_compacto.txt
- Rutas: docs/rutas.md
- Decisiones: docs/decisiones.md
- Grupo: <grupo>
- Fase: <fase>
- Depende de: <HU>
- Notas aplicables: <H-XX>
- Riesgo de conflicto: <archivos>

OBJETIVO
<como ... quiero ... para ...>

ALCANCE
- Implementar todos los escenarios de HU-XX_compacto.md sin omitir ninguno.
- Respetar el esquema canónico y las decisiones D-XX aplicables.
- No implementar nada fuera de esta HU.

CRITERIOS DE ACEPTACIÓN TRAZABLES
- CA-XX.1: <texto>
- CA-XX.2: <texto>
- ...

RUTAS INVOLUCRADAS
- <método> <URI> -> <Controlador::método> [filtros]

ENTREGABLES
- Repository, Service, Controller, vistas y rutas (si corresponde).
- Validaciones con los mensajes exactos de la HU.
- Control de propiedad y 403.
- Tests o verificaciones por cada CA.
- Checklist de aceptación completo.

CHECKLIST
- [ ] CA-XX.1
- [ ] CA-XX.2
- ...

VALIDACIÓN FINAL
- Ejecutar ci4-hu-validator y ci4-test-suite.
- Reportar matriz CA -> archivo/test, notas aplicadas y comandos ejecutados.
- No cerrar si queda un CA FALTANTE o PARCIAL (salvo D-28).
```

---

## 4. Orden de ejecución

```
HU-00 (bootstrap)
  → HU-01 → HU-02 → HU-03
  → HU-04 → HU-06 → HU-05
  → HU-07 → HU-09 → HU-08
  → HU-10 → HU-12 → HU-11
  → HU-13 → HU-14 → HU-15
```

El prompt listo para HU-00 está en [`docs/prompts/HU-00_bootstrap.md`](prompts/HU-00_bootstrap.md). El índice de prompts es [`docs/prompts/README.md`](prompts/README.md).

**Por qué no es numérico:**

- HU-05 necesita el listado de HU-06.
- HU-08 necesita el listado de HU-09.
- HU-11 necesita el listado de HU-12.
- HU-10 depende de usuarios y catálogo de servicios.

---

## 5. Checklist de cierre de cada HU

- [ ] Leí `docs/decisiones.md`, `docs/rutas.md` y la HU.
- [ ] Usé solo tablas/columnas del SQL canónico.
- [ ] Implementé todos los escenarios.
- [ ] Implementé todos los CA con prueba o verificación.
- [ ] Respeté las notas H-XX aplicables.
- [ ] Validé control de propiedad en el servidor.
- [ ] Los accesos denegados devuelven 403 con vista amigable.
- [ ] CSRF activo en los POST.
- [ ] PRG en las mutaciones.
- [ ] Mensajes en español, exactos según la HU.
- [ ] Actualicé `docs/rutas.md` si agregué alguna ruta (con aprobación).
- [ ] No rompí HUs anteriores.
- [ ] Ejecuté tests y los reporté.
- [ ] Dejé la matriz CA -> archivo/test.

---

## 6. Diferencias con el prompt anterior

| Punto | Prompt anterior | Prompt unificado |
|---|---|---|
| Esquema | Implícito, basado en skills | Explícito, basado en el SQL canónico |
| Rutas | “No inventes rutas” sin mapa | `docs/rutas.md` como fuente |
| Referencias | `docs/features-hito1.txt`, `docs/enunciado.txt`, `sql/schema.sql` | Eliminadas |
| Roles | `roles` / `id_rol` | `usuario.rol` ENUM |
| Tests | Sin especificar | MySQL `serviinmueble_test` |
| Cierre | “No cerrar con CA parcial” | Base vs integración (D-28) |
| Decisiones pendientes | No existían | P-01 a P-05, consultar antes de decidir |
| Idioma | Mezcla inglés/español | Español consistente |
| Comandos | Genéricos `php spark` | XAMPP/WSL explícitos |

---

## 7. Anexo: errores que este prompt busca evitar

- Crear una tabla `roles` que no existe.
- Usar `usuarios` en lugar de `usuario`.
- Guardar `Vencida` en `factura.estado`.
- Usar `periodo` como `MM/YYYY` en una sola columna.
- Leer el catálogo en vivo en lugar del snapshot de factura.
- Redirigir en lugar de devolver 403.
- Dejar CSRF deshabilitado.
- Usar SQLite para tests.
- Cerrar una HU con CA parciales sin dueño.
- Inventar rutas o controladores.
- Implementar pagos sin decisión aprobada.
