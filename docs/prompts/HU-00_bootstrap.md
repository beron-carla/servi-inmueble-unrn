# HU-00 · Bootstrap técnico · Prompt listo para usar

**Proyecto:** ServiInmueble 2026
**Documento base:** [`../00_bootstrap.md`](../00_bootstrap.md)
**Prompt general:** [`../PROMPT_GENERAL.md`](../PROMPT_GENERAL.md)
**Entorno verificado:** 2026-11-08

---

## 1. Antes de empezar

1. Verificar el entorno:

   ```bat
   scripts\check_env.bat
   ```

   Debe terminar con **0 fallas**. La advertencia de Mailpit es opcional: solo afecta la prueba de envío de emails.

2. Abrir la sesión de trabajo en la raíz del proyecto (`C:\xampp\htdocs\servi-inmueble`).

3. Pegar primero el **prompt general** de [`../PROMPT_GENERAL.md`](../PROMPT_GENERAL.md) §2.

4. Pegar después el prompt de la sección 2 de este archivo.

---

## 2. Prompt (copiar y pegar)

```text
Implementá la HU-00 · Bootstrap técnico de ServiInmueble 2026 siguiendo
docs/PROMPT_GENERAL.md y docs/00_bootstrap.md.

CONTEXTO VERIFICADO (2026-11-08)
- PHP 8.3.36 (XAMPP), CodeIgniter 4.7.5, MariaDB 10.11.16.
- vendor/ instalado y composer.lock presente.
- .env configurado con baseURL, BD de desarrollo, BD de tests, email y seeds.
- Base serviinmueble con 11 tablas vacías y 2 triggers.
- Base serviinmueble_test creada y vacía.
- spark routes solo lista GET /.
- No hay migraciones, seeds, filtros, vista 403 ni layout de autenticación.

OBJETIVO
Dejar el proyecto listo para implementar HU-01 sin resolver infraestructura,
configuración ni decisiones de esquema en el medio.

ALCANCE (docs/00_bootstrap.md §6)
1. Configuración:
   - App: baseURL, indexPage vacío, locale es, timezone America/Argentina/Buenos_Aires.
   - Session: expiration 1800, regenerateDestroy true, timeToUpdate 300.
   - Filters: aliases auth/guest/role y CSRF global.
   - Email: SMTP de desarrollo (Mailpit localhost:1025) y mailType html.
   - Database: grupo tests con MySQLi y serviinmueble_test.
   - phpunit.dist.xml con las variables de la BD de tests.
   - app/Language/es/Validation.php con mensajes en español.
2. Filtros: AuthFilter, GuestFilter, RoleFilter.
3. Vistas base: layout/auth.php, errors/html/error_403.php, emails/bienvenida.php.
4. Helpers: auth_helper.php y format_helper.php.
5. Migraciones: 11 tablas + 2 triggers, con el DDL exacto de
   bd/serviinmueble (1).sql (sin DROP DATABASE, sin USE, sin DELIMITER).
6. Seeds idempotentes: TipoInmuebleSeeder, TipoServicioSeeder,
   AdminUserSeeder, DemoSeeder y DatabaseSeeder.
7. Estructura de capas: Models, Entities, Repositories y Services base.
8. app/Config/Routes.php con el mapa de docs/rutas.md §5.
9. Tests de bootstrap contra serviinmueble_test.
10. Actualizar env y la documentación que corresponda.

REGLAS NO NEGOCIABLES
- Esquema canónico: bd/serviinmueble (1).sql.
- No usar roles, usuarios, inmuebles, servicios_inmueble, facturas,
  id_rol, password_hash, created_at, updated_at ni deleted_at.
- No implementar lógica funcional de HU-01 a HU-15.
- No crear rutas fuera de docs/rutas.md.
- CSRF global en todos los POST.
- Sesión de 1800 segundos con invalidación en la tabla sesion.
- Acceso denegado: HTTP 403 con app/Views/errors/html/error_403.php.
- Tests contra MySQL (serviinmueble_test), nunca SQLite.
- Migraciones con SQL crudo por ENUM, CHECK, YEAR y triggers.
- Seeds idempotentes: se pueden ejecutar dos veces sin duplicar.
- Mensajes de usuario en español.

ENTREGABLES
- Archivos creados y modificados.
- Salida de spark routes (debe coincidir con docs/rutas.md).
- Salida de spark migrate y spark db:seed DatabaseSeeder.
- Salida de scripts/check_env.php.
- Matriz BC-01 a BC-20 -> archivo/test/verificación.
- Pendientes o riesgos.

CRITERIOS DE ACEPTACIÓN
Implementar y verificar los BC-01 a BC-20 de docs/00_bootstrap.md §8.

VALIDACIÓN FINAL
- spark migrate desde una base vacía crea las 11 tablas y los 2 triggers.
- spark db:seed DatabaseSeeder dos veces no duplica datos.
- spark routes no muestra rutas automáticas.
- phpunit corre contra serviinmueble_test.
- No cerrar la HU-00 si queda un BC FALTANTE o PARCIAL.
```

---

## 3. Resultado esperado

Al terminar deben existir:

- `app/Database/Migrations/` con 12 migraciones (11 tablas + triggers).
- `app/Database/Seeds/` con los 5 seeders.
- `app/Filters/AuthFilter.php`, `GuestFilter.php` y `RoleFilter.php`.
- `app/Views/layout/auth.php`, `app/Views/errors/html/error_403.php` y `app/Views/emails/bienvenida.php`.
- `app/Helpers/auth_helper.php` y `app/Helpers/format_helper.php`.
- `app/Models/`, `app/Entities/`, `app/Repositories/` y `app/Services/` con la estructura base.
- `app/Config/Routes.php` con el mapa completo.
- Tests de bootstrap pasando contra `serviinmueble_test`.

## 4. Después de HU-00

1. Ejecutar de nuevo `scripts\check_env.bat`.
2. Verificar `spark routes` y `spark db:table`.
3. Pasar a [`../../docs/prompts/README.md`](README.md) y continuar con HU-01.
