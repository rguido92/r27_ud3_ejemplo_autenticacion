<?php

require_once 'usuarios.php';

// Comprobar que la petición procede de un formulario POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit;
}

// Comprobar que se han recibido las credenciales
if (
    !isset($_POST['usuario'], $_POST['password']) ||
    !is_string($_POST['usuario']) ||
    !is_string($_POST['password'])
) {
    header("Location: login.php");
    exit;
}


// Recuperar las credenciales
$username = $_POST['usuario'];
$password = $_POST['password'];

try {
    // Iniciar la sesión
    session_start();

    $usuario = getUsuarioByUserName($username);
    // var_dump($usuario);
    // exit;
} catch (Exception $e) {
    error_log("Error al consultar el usuario: " . $e->getMessage());
    $_SESSION['error_login'] = "Error al consultar el usuario. Por favor, inténtelo de nuevo más tarde.";
    header("Location: login.php");
    exit;
}

// Comprobar las credenciales
if ($usuario !== null && password_verify($password, $usuario['password'])) {




    // Guardar la identidad del usuari
    $_SESSION['id_usuario'] = $usuario['id'];
    $_SESSION['usuario'] = $usuario['usuario'];
    $_SESSION['rol'] = $usuario['rol'];
    // Acceder a la página privada
    $destino = ($usuario['rol'] === 'admin') ? 'admin.php' : 'privada.php';
    header("Location: $destino");
    exit;
} else {

    session_start();

    $_SESSION['error_login'] = "Usuario o contraseña incorrectos.";

    // Credenciales incorrectas
    header("Location: login.php");
    exit;
}
