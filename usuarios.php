<?php
require_once __DIR__ . "/conexion.php";
function getUsuarioByUserName($username)
{

    try {
        // Obtener la conexión PDO con la base de datos
        $conexion = getConnection();

        // Preparar una consulta para buscar el usuario por su nombre
        // Solo se recuperan el identificador, el nombre de usuario
        // y el hash de su contraseña
        $sql = "select usuarios.id,usuarios.password,usuarios.usuario,roles.nombre AS rol from usuarios join roles on usuarios.id_rol = roles.id where usuario = :usuario";

        $stmt = $conexion->prepare($sql);

        $stmt->execute(['usuario' => $username]);
        // Obtener el resultado de la consulta como un array asociativo
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        // Devolver el array con los datos del usuario
        // o null si no se ha encontrado
        return $usuario ?: null;
    } catch (PDOException $e) {
        // Registrar los detalles del error en el log del servidor
        // para poder diagnosticar el problema sin mostrarlos al usuario
        error_log("Error al consultar el usuario: " . $e->getMessage());

        // Propagar la excepción para que la gestione el código que
        // ha llamado a esta función
        throw $e;
    }
}
