<?php
session_start();
require_once 'conexion.php';

if (isset($_SESSION['usuario_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = trim($_POST['usuario']);
    $password = trim($_POST['password']);

    if (empty($usuario) || empty($password)) {
        $error = "Por favor, completa todos los campos.";
    } elseif (strlen($password) !== 8) {
        $error = "Acceso denegado: la contraseña debe tener 8 caracteres.";
    } else {
        $stmt = $conexion->prepare("SELECT * FROM usuarios WHERE usuario = :usuario");
        $stmt->execute([':usuario' => $usuario]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['usuario_id'] = $user['id'];
            $_SESSION['nombre_usuario'] = $user['nombre'];
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Usuario o contraseña incorrectos.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login - SolarControl</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-card { background: #ffffff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); width: 320px; }
        .login-card h2 { margin-top: 0; color: #1a365d; text-align: center; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input { width: 100%; padding: 8px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; }
        .btn { width: 100%; background: #1a365d; color: white; border: none; padding: 10px; border-radius: 4px; cursor: pointer; font-size: 16px; }
        .btn:hover { background: #2c5282; }
        .error { color: red; font-size: 14px; text-align: center; margin-bottom: 10px; }
        .link { text-align: center; margin-top: 15px; display: block; color: #1a365d; text-decoration: none; font-size: 14px; }
    </style>
</head>
<body>
<div class="login-card">
    <h2>SolarControl</h2>
    <?php if (!empty($error)): ?>
        <p class="error"><?php echo $error; ?></p>
    <?php endif; ?>
    
    <form action="login.php" method="POST">
        <div class="form-group">
            <label>Usuario</label>
            <input type="text" name="usuario" required>
        </div>
        <div class="form-group">
            <label>Contraseña (8 caracteres)</label>
            <input type="password" name="password" minlength="8" maxlength="8" required>
        </div>
        <button type="submit" class="btn">Ingresar</button>
    </form>
    <a href="crear_usuario.php" class="link">Crear cuenta nueva</a>
</div>
</body>
</html>