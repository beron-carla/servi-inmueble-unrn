# Iniciar ServiInmueble desde la terminal de VS Code

Guía para ejecutar ServiInmueble localmente en Windows con XAMPP, PowerShell integrado de VS Code y el servidor de desarrollo de CodeIgniter.

## Requisitos

- XAMPP instalado en `C:\xampp`.
- PHP 8.2 o superior y Composer instalados.
- El proyecto en `C:\xampp\htdocs\servi-inmueble-unrn`.

## Primera ejecución

### 1. Iniciar MySQL

Abrí el panel de XAMPP e iniciá **MySQL**. Debe quedar activo mientras uses la aplicación. Si MySQL no está iniciado, ServiInmueble no podrá conectarse a la base de datos.

En phpMyAdmin (`http://localhost/phpmyadmin`), verificá que exista la base `serviinmueble`. Si no existe, creala con ese nombre. La conexión configurada en `.env` usa `localhost`, el puerto `3306`, el usuario `root` y contraseña vacía, que son los valores predeterminados de XAMPP.

### 2. Abrir la carpeta en VS Code

Abrí la carpeta `C:\xampp\htdocs\servi-inmueble-unrn` en VS Code y luego una terminal PowerShell integrada (**Terminal > Nueva terminal**). Ejecutá:

```powershell
cd C:\xampp\htdocs\servi-inmueble-unrn
```

### 3. Instalar dependencias

Este paso se realiza la primera vez, o cuando cambian las dependencias del proyecto:

```powershell
composer install
```

Si PowerShell indica que `composer` no se reconoce, instalá Composer para Windows y volvé a abrir la terminal de VS Code.

### 4. Crear tablas y datos iniciales

Ejecutá estos comandos desde la carpeta del proyecto. En PowerShell, `spark` debe ejecutarse a través de PHP; no alcanza con escribir `spark` directamente:

```powershell
& "C:\xampp\php\php.exe" .\spark migrate --all
& "C:\xampp\php\php.exe" .\spark db:seed DatabaseSeeder
```

Estos comandos se necesitan en la primera instalación. No hace falta volver a ejecutarlos cada vez que iniciás el servidor. Las migraciones requieren que MySQL esté iniciado y que la base `serviinmueble` ya exista.

El seeder crea el administrador inicial si todavía no existe:

- Correo: `admin@serviinmueble.local`
- Contraseña temporal: `Admin1234`

En entorno de desarrollo también carga datos de demostración, incluido el usuario `propietario@serviinmueble.local` con contraseña `Propietario1234`.

## Iniciar la aplicación

Para usar el servidor de desarrollo de CodeIgniter, configurá en `.env` la URL base correspondiente:

```ini
app.baseURL = 'http://localhost:8080/'
```

La configuración local puede tener otra URL, por ejemplo `http://localhost/servi-inmueble/public/`, que corresponde a una configuración de Apache. Si ejecutás `spark serve` en el puerto 8080, usá la URL de arriba.

Con MySQL todavía activo, iniciá el servidor desde la terminal integrada:

```powershell
& "C:\xampp\php\php.exe" .\spark serve
```

Dejá esa terminal abierta y visitá <http://localhost:8080>. Para detener el servidor, volvé a la terminal y presioná `Ctrl+C`.

## Próximas ejecuciones

En cada ocasión que quieras trabajar en el proyecto:

1. Iniciá **MySQL** desde el panel de XAMPP.
2. Abrí una terminal de VS Code en la carpeta del proyecto.
3. Iniciá el servidor:

   ```powershell
   & "C:\xampp\php\php.exe" .\spark serve
   ```

4. Abrí <http://localhost:8080>.

Si aparece `Unable to connect to the database`, verificá que MySQL siga activo en XAMPP y que `.env` tenga los parámetros de conexión correctos.
