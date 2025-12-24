<?php
session_start();
require_once 'config/database.php';

// Si ya está logueado, redirigir al inicio
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';

// Procesar login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'Por favor completa todos los campos';
    } else {
        try {
            $stmt = $conn->prepare("SELECT id, username, password, full_name, is_admin FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Login exitoso
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['is_admin'] = $user['is_admin'];
                
                // Redirigir según el tipo de usuario
                if ($user['is_admin']) {
                    header('Location: admin/');
                } else {
                    header('Location: index.php');
                }
                exit();
            } else {
                $error = 'Usuario o contraseña incorrectos';
            }
        } catch(PDOException $e) {
            $error = 'Error en el sistema. Por favor intenta más tarde.';
            error_log("Error en login: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - DCA Perú</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=GFS+Didot:ital@0;1&family=Didact+Gothic&family=Montserrat:wght@300;400;500;600;700;800&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>
    <!-- Corazones flotantes -->
    <div class="floating-hearts">
        <div class="heart"></div>
        <div class="heart"></div>
        <div class="heart"></div>
        <div class="heart"></div>
        <div class="heart"></div>
        <div class="heart"></div>
        <div class="heart"></div>
        <div class="heart"></div>
        <div class="heart"></div>
    </div>

    <!-- Botón de regreso -->
    <div class="back-home">
        <a href="index.php">
            <i class="fas fa-arrow-left"></i>
            Volver al Inicio
        </a>
    </div>

    <!-- Contenedor principal -->
    <div class="login-container">
        <div class="login-card">
            <!-- Encabezado de la tarjeta -->
            <div class="card-header">
                <div class="icon">
                    <i class="fas fa-heart"></i>
                </div>
                <h2>¡Bienvenido de vuelta!</h2>
                <p>Inicia sesión en para mejor experiencia</p>
            </div>

            <!-- Alertas -->
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <!-- Formulario de login -->
            <form method="POST" action="" id="loginForm">
                <div class="form-group">
                    <label for="username">Usuario o Email</label>
                    <input type="text" id="username" name="username" required 
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                           placeholder="Ingresa tu usuario o email">
                </div>
                
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <div class="password-input">
                        <input type="password" id="password" name="password" required 
                               placeholder="Ingresa tu contraseña">
                        <button type="button" class="password-toggle" onclick="togglePassword('password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="forgot-password">
                    <a href="forgot-password.php">¿Olvidaste tu contraseña?</a>
                </div>
                
                <button type="submit" class="btn-login">
                    <i class="fas fa-heart"></i> Iniciar Sesión
                </button>
            </form>
            
            <!-- Separador -->
            <div class="divider">
                <span>o continúa con</span>
            </div>
            
            <!-- Login social -->
            <div class="social-login">
                <a href="#" class="btn-social">
                    <i class="fab fa-google"></i> Google
                </a>
                <a href="#" class="btn-social">
                    <i class="fab fa-facebook"></i> Facebook
                </a>
            </div>
            
            <!-- Footer -->
            <div class="login-footer">
                <p>¿No tienes una cuenta? <a href="register.php">Regístrate aquí</a></p>
            </div>
        </div>
    </div>
    
    <script>
        // Función para mostrar/ocultar contraseña
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = event.target;
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.innerHTML = '<i class="fas fa-eye-slash"></i>';
            } else {
                input.type = 'password';
                icon.innerHTML = '<i class="fas fa-eye"></i>';
            }
        }
        
        // Validación del formulario
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            
            if (!username || !password) {
                e.preventDefault();
                alert('Por favor completa todos los campos');
                return false;
            }
            
            return true;
        });
        
        // Auto-focus en el primer campo
        document.getElementById('username').focus();
        
        // Limpiar mensajes de error al escribir
        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('input', function() {
                const alert = document.querySelector('.alert');
                if (alert) {
                    alert.remove();
                }
            });
        });

        // Efecto de confeti al hacer submit (simulado)
        document.getElementById('loginForm').addEventListener('submit', function() {
            // Aquí podrías agregar confeti real si quieres
            console.log('¡Iniciando sesión con amor! ❤️');
        });
    </script>
</body>
</html>
