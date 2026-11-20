# HU-15 · Listado de facturas con totales

**Grupo 5:** SERVICIOS Y FACTURAS

Como Propietario/Administrador, quiero ver un listado tabular de facturas pendientes y pagadas con totales, para tener una vista rápida de mi situación.

## Reglas globales aplicables

- Password: mínimo 8 caracteres, al menos una letra y un número (registro, alta por Admin y password temporal).
- Email único, case-insensitive, incluye usuarios en Baja. Error: "El email ya se encuentra registrado".
- Bajas lógicas (nunca borrado físico), salvo tipo de servicio sin uso (HU-08).
- Sin permiso: error 403 "No tiene permisos para esta acción". Se muestra una página 403 amigable con el mensaje y un botón "Volver al dashboard"; no se redirige automáticamente.
- UI: se ocultan menús y acciones no permitidas para el rol o el estado del recurso (ej. un Propietario no ve el menú de Admin ni acciones sobre inmuebles ajenos). Ocultar es solo UX; no reemplaza la validación del servidor (403).

## Escenarios

- Listado con totales: ejemplo 3 facturas (Energía 08/2026 12000 Pagada; Energía 09/2026 13500 Pendiente; Agua 09/2026 4300 Pendiente); orden por vencimiento aplicable descendente; pendiente 17800, pagado 12000, general 29800.
- Factura vencida: una factura Pendiente cuyo vencimiento aplicable ya pasó se muestra como Vencida y suma al total pendiente.
- Filtro por estado: totales solo de lo filtrado.
- Filtro por inmueble y servicio.
- Filtro por rango de fechas: solo facturas cuyo vencimiento aplicable cae en el rango.
- Sin facturas: "No hay facturas registradas".
- Permisos: Propietario ve solo las de sus inmuebles; Admin ve todas.

## Criterios de aceptación

- **CA-15.1** Tabla: inmueble, servicio, período, vencimiento aplicable (2.º si existe; si no, 1.º), monto, estado.
- **CA-15.2** Sumarios: total pendiente (incluye Pendiente y Vencida), total pagado (Pagadas) y total general (pendiente + pagado).
- **CA-15.3** Filtros: inmueble, servicio, estado y rango de fechas sobre el vencimiento aplicable.
- **CA-15.4** Totales recalculados según filtros.
- **CA-15.5** Orden por vencimiento aplicable descendente por defecto.

---

_Fuente: `HU_Hito1_compacto.txt`. Notas y decisiones de trazabilidad en la sección NOTAS de ese archivo._
