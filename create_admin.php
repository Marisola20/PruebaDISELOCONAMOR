<?php
require_once 'config/database.php';

echo "<h2>Creando Usuario Administrador...</h2>";

// Datos del usuario admin
$username = 'admin';
$email = 'admin@dcaperu.com';
$password = 'admin123';
$full_name = 'Administrador';

// Hash de la contraseña
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

echo "Contraseña original: " . $password . "<br>";
echo "Contraseña hasheada: " . $hashed_password . "<br><br>";

// Verificar si ya existe
$stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$stmt->execute([$username]);

if ($stmt->rowCount() == 0) {
    // Insertar nuevo usuario admin
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, full_name, is_admin, is_active) VALUES (?, ?, ?, ?, 1, 1)");
    
    if ($stmt->execute([$username, $email, $hashed_password, $full_name])) {
        echo "<h3 style='color: green;'>✅ Usuario administrador creado exitosamente!</h3>";
        echo "<strong>Credenciales de acceso:</strong><br>";
        echo "Usuario: <strong>" . $username . "</strong><br>";
        echo "Contraseña: <strong>" . $password . "</strong><br>";
        echo "Email: " . $email . "<br><br>";
        echo "<strong>Ahora puedes acceder al panel de administración en:</strong><br>";
        echo "<a href='admin/'>http://localhost/dcaperu/admin/</a>";
    } else {
        echo "<h3 style='color: red;'>❌ Error al crear el usuario administrador.</h3>";
        echo "Error: " . implode(", ", $stmt->errorInfo());
    }
} else {
    echo "<h3>El usuario administrador ya existe. Actualizando contraseña...</h3>";
    
    // Actualizar contraseña
    $stmt = $conn->prepare("UPDATE users SET password = ?, email = ?, full_name = ?, is_admin = 1, is_active = 1 WHERE username = ?");
    
    if ($stmt->execute([$hashed_password, $email, $full_name, $username])) {
        echo "<h3 style='color: green;'>✅ Contraseña actualizada exitosamente!</h3>";
        echo "<strong>Credenciales de acceso:</strong><br>";
        echo "Usuario: <strong>" . $username . "</strong><br>";
        echo "Contraseña: <strong>" . $password . "</strong><br>";
        echo "Email: " . $email . "<br><br>";
        echo "<strong>Ahora puedes acceder al panel de administración en:</strong><br>";
        echo "<a href='admin/'>http://localhost/dcaperu/admin/</a>";
    } else {
        echo "<h3 style='color: red;'>❌ Error al actualizar la contraseña.</h3>";
        echo "Error: " . implode(", ", $stmt->errorInfo());
    }
}

// Verificar que el usuario existe y tiene los permisos correctos
echo "<br><hr><br>";
echo "<h3>Verificando usuario creado:</h3>";

$stmt = $conn->prepare("SELECT id, username, email, is_admin, is_active FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

if ($user) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Campo</th><th>Valor</th></tr>";
    echo "<tr><td>ID</td><td>" . $user['id'] . "</td></tr>";
    echo "<tr><td>Username</td><td>" . $user['username'] . "</td></tr>";
    echo "<tr><td>Email</td><td>" . $user['email'] . "</td></tr>";
    echo "<tr><td>Es Admin</td><td>" . ($user['is_admin'] ? 'SÍ' : 'NO') . "</td></tr>";
    echo "<tr><td>Está Activo</td><td>" . ($user['is_active'] ? 'SÍ' : 'NO') . "</td></tr>";
    echo "</table>";
} else {
    echo "<p style='color: red;'>❌ No se pudo verificar el usuario.</p>";
}

echo "<br><hr><br>";
echo "<h3>⚠️ IMPORTANTE:</h3>";
echo "<p>Después de acceder al panel admin, <strong>ELIMINA este archivo</strong> por seguridad.</p>";
echo "<p>Archivo a eliminar: <code>create_admin.php</code></p>";
?>
