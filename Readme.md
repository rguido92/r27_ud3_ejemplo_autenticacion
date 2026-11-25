# Actividad: autenticación con sesiones PHP y control de roles

Ejemplo sencillo de una aplicación PHP que comprueba credenciales contra MySQL, guarda la identidad del usuario en `$_SESSION` y protege dos páginas: `privada.php` (cualquier usuario autenticado) y `admin.php` (solo rol `admin`).

## Estructura del proyecto

| Fichero | Función principal |
| --- | --- |
| `login.php` | Muestra el formulario y el mensaje de error de un intento anterior. |
| `validar.php` | Recibe el `POST`, consulta la BD, verifica la contraseña y crea la sesión. |
| `usuarios.php` | Función `getUsuarioByUserName()`: busca usuario + rol en la BD. |
| `conexion.php` | Lee `db_settings.ini` y devuelve una conexión PDO. |
| `privada.php` | Zona privada: exige `id_usuario` en sesión. |
| `admin.php` | Zona de administración: exige sesión y rol `admin`. |
| `logout.php` | Destruye la sesión y vuelve al login. |
| `scripts/data.sql` | Crea BD, tablas, roles (`admin`, `user`) y usuarios de prueba. |
| `scripts/user_create.sql` | Crea el usuario de la aplicación y sus permisos. |
| `db_settings.ini` | Parámetros de conexión a MySQL. |

## Puesta en marcha

1. Importar `scripts/data.sql` y `scripts/user_create.sql` en MySQL/MariaDB.
2. Revisar los valores de `db_settings.ini` (driver, host, esquema, usuario y contraseña).
3. Arrancar el servidor desde la raíz del proyecto:

   ```powershell
   php -S localhost:8000
   ```

4. Abrir `http://localhost:8000/login.php`.

Las contraseñas de `scripts/data.sql` están hasheadas. Para crear otras, usa `password_hash()` y sustituye el hash en la tabla `usuarios`.

## Ciclo de vida de la aplicación

Esquema general:

```text
login.php  →  validar.php  →  usuarios.php / conexion.php  →  MySQL
   GET          POST              consulta + hash               |
                                                                v
logout.php  ←  admin.php / privada.php  ←  $_SESSION (en el servidor)
                 nuevas peticiones con la cookie PHPSESSID
```

### 1. `login.php`: formulario y sesión

- `session_start()` (`login.php:19`) abre o recupera la sesión. El navegador recibe una cookie con el identificador de sesión (`PHPSESSID`).
- Si existe `$_SESSION['error_login']`, se muestra con `htmlspecialchars()` y se borra con `unset()` (`login.php:21-28`). Es un mensaje *flash*: se guarda en una petición, se muestra en la siguiente y desaparece.

### 2. `validar.php`: comprobación contra la base de datos

- Primero valida que la petición sea `POST` y que lleguen `usuario` y `password` (`validar.php:6-19`).
- `require_once 'usuarios.php'` lleva a `conexion.php`, que lee `db_settings.ini` y crea el PDO.
- La consulta (`usuarios.php:13`) hace `JOIN` entre `usuarios` y `roles` y devuelve `id`, hash de `password`, `usuario` y `rol`.
- `password_verify($password, $usuario['password'])` (`validar.php:41`) compara la contraseña escrita con el hash de la BD. Nunca se guarda la contraseña en texto plano.
- Si es correcta, se guarda la identidad en la sesión (`validar.php:47-49`):

  ```php
  $_SESSION['id_usuario'] = $usuario['id'];
  $_SESSION['usuario'] = $usuario['usuario'];
  $_SESSION['rol'] = $usuario['rol'];
  ```

- Después se redirige según el rol (`validar.php:51-52`): `admin.php` si es `admin`, `privada.php` en caso contrario. Toda redirección termina con `exit`.
- Si falla, se escribe `$_SESSION['error_login']` y se vuelve a `login.php` (`validar.php:58-61`).

### 3. ¿Por qué no se pierden los datos al redirigir?

`header('Location: ...')` provoca una **nueva petición HTTP**. En esa nueva ejecución de PHP las variables normales ya no existen, pero el navegador reenvía la cookie `PHPSESSID`. Al llamar de nuevo a `session_start()` en la página destino, PHP recupera del servidor el mismo array `$_SESSION`.

Por eso la base de datos se consulta solo durante el login. A partir de ahí, `admin.php` y `privada.php` leen `usuario` y `rol` directamente de la sesión, sin otra consulta. El rol original procede de la tabla `roles` (`scripts/data.sql:28-30`), no de un valor que pueda inventar el navegador.

### 4. Páginas protegidas

`privada.php:6` comprueba únicamente que exista `$_SESSION['id_usuario']`. En `admin.php` el control debe colocarse al principio del fichero, antes de cualquier HTML:

```php
<?php
session_start();

if (!isset($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit;
}

if (($_SESSION['rol'] ?? null) !== 'admin') {
    http_response_code(403);
    die('Acceso denegado. No tienes permisos para acceder a esta página.');
}
?>
```

- Sin sesión → redirección a `login.php`.
- Con sesión pero rol distinto de `admin` → HTTP 403, mensaje de acceso denegado y `die()` detiene el script, por lo que no se imprime el HTML de administración.
- `?? null` evita avisos si la clave `rol` no existe.
- `session_start()` y `header()` deben ir antes de imprimir nada: ni un espacio, ni HTML, ni un `echo` previo.

### 5. `logout.php`: fin del ciclo

- Vacía `$_SESSION`, caduca la cookie de sesión, llama a `session_destroy()` y redirige a `login.php`.
- Después del logout, intentar entrar en `admin.php` vuelve a redirigir al login.

## Puntos clave para el examen

- `$_SESSION` se almacena **en el servidor**; la cookie del navegador solo guarda su identificador.
- Toda página que lea o escriba la sesión debe llamar a `session_start()` antes de generar salida.
- Autenticación = comprobar quién eres (`password_verify`). Autorización = comprobar qué rol tienes (`$_SESSION['rol']`).
- Usar `header('Location: ...')` seguido de `exit` para redirigir; `die()` para mostrar un mensaje y cortar la ejecución.
- El mensaje de error del login viaja en la sesión entre dos peticiones y se elimina después de mostrarse.
- Nunca confiar en datos de rol enviados por el cliente: el rol se lee de la BD en el login y se guarda en la sesión.

## Pruebas rápidas

- Entrar en `admin.php` sin iniciar sesión → debe ir a `login.php`.
- Iniciar sesión con un usuario de rol `user` (por ejemplo `pedro`) → 403 con "Acceso denegado" y sin el contenido de administración.
- Iniciar sesión con un usuario de rol `admin` (por ejemplo `ana`) → `admin.php` muestra el nombre y el rol.
- Pulsar cerrar sesión → `login.php`; al volver a `admin.php`, otra vez redirección al login.
