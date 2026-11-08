# Prompts del Hito 1 · ServiInmueble 2026

**Fecha:** 2026-11-08
**Prompt general vigente:** [`../PROMPT_GENERAL.md`](../PROMPT_GENERAL.md)
**Esquema canónico:** [`bd/serviinmueble (1).sql`](<../../bd/serviinmueble (1).sql>)

---

## 1. Cómo iniciar cada sesión

Siempre, en este orden:

1. Pegar el **prompt general** de [`../PROMPT_GENERAL.md`](../PROMPT_GENERAL.md) §2.
2. Pegar el prompt de la HU correspondiente.
3. Adjuntar o indicar los documentos de la HU:
   - `docs/historias_de_usuario/HU-XX_compacto.md`
   - `docs/rutas.md`
   - `docs/decisiones.md`
   - `docs/00_bootstrap.md` (solo para HU-00)

> Si un prompt viejo contradice `docs/decisiones.md` o el SQL canónico, **manda el SQL canónico** y las decisiones documentadas.

---

## 2. Orden de ejecución

```
HU-00 → HU-01 → HU-02 → HU-03
      → HU-04 → HU-06 → HU-05
      → HU-07 → HU-09 → HU-08
      → HU-10 → HU-12 → HU-11
      → HU-13 → HU-14 → HU-15
```

No es el orden numérico: HU-05 necesita el listado de HU-06, HU-08 el de HU-09 y HU-11 el de HU-12.

---

## 3. Prompts disponibles

| HU | Prompt | Estado |
|---|---|---|
| HU-00 · Bootstrap técnico | [`HU-00_bootstrap.md`](HU-00_bootstrap.md) | ✅ listo |
| HU-01 · Registro de usuarios | `docs/historias_de_usuario/PROMPTS_HU_HITO1_TRAZABILIDAD.md` §HU-01 | ⚠️ usar con el prompt general v2 |
| HU-02 · Inicio de sesión | ídem §HU-02 | ⚠️ usar con el prompt general v2 |
| HU-03 · Cierre de sesión | ídem §HU-03 | ⚠️ usar con el prompt general v2 |
| HU-04 · Alta de usuarios | ídem §HU-04 | ⚠️ usar con el prompt general v2 |
| HU-05 · Modificación y baja de usuarios | ídem §HU-05 | ⚠️ usar con el prompt general v2 |
| HU-06 · Listado de usuarios | ídem §HU-06 | ⚠️ usar con el prompt general v2 |
| HU-07 · Alta de tipos de servicio | ídem §HU-07 | ⚠️ usar con el prompt general v2 |
| HU-08 · Modificación y baja de tipos | ídem §HU-08 | ⚠️ usar con el prompt general v2 |
| HU-09 · Listado de tipos de servicio | ídem §HU-09 | ⚠️ usar con el prompt general v2 |
| HU-10 · Alta de inmuebles | ídem §HU-10 | ⚠️ usar con el prompt general v2 |
| HU-11 · Modificación y baja de inmuebles | ídem §HU-11 | ⚠️ usar con el prompt general v2 |
| HU-12 · Listado de inmuebles | ídem §HU-12 | ⚠️ usar con el prompt general v2 |
| HU-13 · Vinculación de servicios | ídem §HU-13 | ⚠️ usar con el prompt general v2 |
| HU-14 · Alta de factura | ídem §HU-14 | ⚠️ usar con el prompt general v2 |
| HU-15 · Listado de facturas | ídem §HU-15 | ⚠️ usar con el prompt general v2 |

> **Por qué “usar con el prompt general v2”:** los prompts de `PROMPTS_HU_HITO1_TRAZABILIDAD.md` conservan la estructura de trazabilidad (CA y notas H-XX), pero fueron escritos antes de fijar el esquema SQL. El prompt general v2 impone el esquema canónico, las rutas y las decisiones D-XX. Ante conflicto, gana el prompt general v2.

---

## 4. Plantilla para una HU nueva

Si necesitás un prompt desde cero, usá la plantilla de [`../PROMPT_GENERAL.md`](../PROMPT_GENERAL.md) §3 y completá:

- HU y título.
- Archivo funcional.
- Grupo, fase y dependencias.
- Notas H-XX aplicables.
- Riesgo de conflicto (controladores, rutas, vistas compartidas).
- Objetivo y alcance.
- Criterios de aceptación CA-XX.Y.
- Rutas involucradas.
- Checklist de cierre.

---

## 5. Checklist antes de pedir una HU

- [ ] `scripts\check_env.bat` da 0 fallas.
- [ ] La HU anterior está cerrada o sus pendientes están documentados.
- [ ] Leí `docs/rutas.md` y `docs/decisiones.md`.
- [ ] Sé qué decisiones P-XX están pendientes y no las voy a resolver por mi cuenta.
- [ ] Tengo claro el alcance: no se implementan HUs futuras.
