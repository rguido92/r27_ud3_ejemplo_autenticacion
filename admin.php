<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit;
}
if (($_SESSION['rol'] ?? null) !== 'admin') {
    http_response_code(403);
    die("Acceso denegado. No tienes permisos para acceder a esta página.");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Administración</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-5 col-lg-4">

                <h1 class="text-center mb-4">Administración</h1>
                <?php
                if (isset($_SESSION['error_login'])):
                ?>
                    <div class="alert alert-danger" role="alert">
                        <?= htmlspecialchars($_SESSION['error_login']) ?>
                    </div>
                <?php
                    unset($_SESSION['error_login']);
                endif;
                ?>
                <p class="text-center">Bienvenido, <?= htmlspecialchars($_SESSION['usuario']) ?>. Tu rol es <?= htmlspecialchars($_SESSION['rol']) ?>.</p>
            </div>
        </div>
    </div>
</body>

</html>