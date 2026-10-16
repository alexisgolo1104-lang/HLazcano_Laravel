# HLazcano en Laravel

Aplicación web migrada del proyecto PHP a Laravel 11. Las rutas están en `routes/web.php`; los controladores y modelos de negocio están en `app/Http/Controllers` y `app/Models`; las vistas activas están en `resources/views` y sus subdirectorios. Los fragmentos PHP de pantalla que no requerían lógica se conservaron como vistas Blade independientes en `resources/views/legacy`.

## Configuración local con Laragon

1. Importa el archivo [`database/hlazcanodb.sql`](./database/hlazcanodb.sql) en MySQL. El script crea `hlazcano_db` si aún no existe y carga el esquema y los datos de ejemplo originales. **Importa solo en una base nueva o vacía**: el volcado contiene instrucciones `DROP TABLE` para las tablas de la aplicación.

   ```bash
   mysql -u root -p < database/hlazcanodb.sql
   ```

   El archivo conserva usuarios de demostración del proyecto original; cambia sus contraseñas antes de usar la aplicación en un entorno real.

2. Configura `.env` para apuntar a esa base. Ejemplo para Laragon:

   ```dotenv
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=hlazcano_db
   DB_USERNAME=root
   DB_PASSWORD=
   ```

3. Desde la raíz de este proyecto ejecuta:

   ```bash
   php artisan migrate
   php artisan serve
   ```

   La migración de Laravel amplía `usuarios.contrasena` para permitir hashes seguros. Al iniciar sesión por primera vez, una contraseña heredada en texto plano se verifica y se convierte a hash. Las nuevas cuentas también guardan contraseñas con hash.

4. Abre la URL local mostrada por `artisan serve`. El usuario puede iniciar sesión con su correo o nombre de usuario. El registro valida nombre y correo, exige confirmación de contraseña y asigna el rol de empleado.

## Módulos migrados

- Autenticación y registro con sesión Laravel.
- Dashboard con ventas del mes, inventario, alertas y clientes desde la base.
- Inventario: consulta, filtros, alta, edición, baja lógica y protección contra productos duplicados por nombre de catálogo.
- Ventas y compras: registro transaccional de encabezado/detalles, validación de existencia y actualización del stock.
- Clientes, alertas, localizador, reportes, usuarios, catálogos y tickets imprimibles.
- Respaldos completos en ZIP con historial; la restauración está restringida a superadministradores, solicita confirmación explícita y cierra la sesión al terminar.

La aplicación usa las tablas existentes (`usuarios`, `productos`, `ventas`, `compras`, entre otras) y la conexión definida en `.env`; ejecutar las migraciones no reemplaza ni recrea los datos importados.
Los respaldos solo se pueden restaurar en la misma base de datos y con la misma `APP_KEY` con la que se generaron. La restauración reemplaza todas las tablas y datos actuales. Mantén copias fuera del servidor y no cambies la `APP_KEY` si necesitas restaurar un respaldo existente.

## Pruebas

```bash
php artisan test
```

Las pruebas usan SQLite en memoria para no modificar la base MySQL configurada para el desarrollo.
