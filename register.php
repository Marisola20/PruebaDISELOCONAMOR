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

// Procesar registro
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    
    // Validaciones
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password) || empty($full_name)) {
        $error = 'Por favor completa todos los campos obligatorios';
    } elseif ($password !== $confirm_password) {
        $error = 'Las contraseñas no coinciden';
    } elseif (strlen($password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El email no es válido';
    } else {
        try {
            // Verificar si el usuario ya existe
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            
            if ($stmt->rowCount() > 0) {
                $error = 'El usuario o email ya está registrado';
            } else {
                // Crear nuevo usuario
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt = $conn->prepare("
                    INSERT INTO users (username, email, password, full_name, phone, address, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");
                
                if ($stmt->execute([$username, $email, $hashed_password, $full_name, $phone, $address])) {
                    $success = '¡Registro exitoso! Ahora puedes iniciar sesión.';
                    
                    // Limpiar formulario
                    $_POST = array();
                } else {
                    $error = 'Error al crear la cuenta. Por favor intenta más tarde.';
                }
            }
        } catch(PDOException $e) {
            $error = 'Error en el sistema. Por favor intenta más tarde.';
            error_log("Error en registro: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - DCA Perú</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=GFS+Didot:ital@0;1&family=Didact+Gothic&family=Montserrat:wght@300;400;500;600;700;800&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/register.css">
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
    <div class="register-container">
        <div class="register-card">
            <!-- Encabezado de la tarjeta -->
            <div class="card-header">
                <div class="icon">
                    <i class="fas fa-user-plus"></i>
                </div>
                <h2>¡Únete a nosotros!</h2>
                <p>Crea tu cuenta y descubre el mejor regalo para cada momento</p>
            </div>

            <!-- Alertas -->
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <!-- Formulario de registro -->
            <form method="POST" action="" id="registerForm">
                <div class="form-row">
                    <div class="form-group">
                        <label for="username">Usuario <span class="required">*</span></label>
                        <input type="text" id="username" name="username" required 
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                               placeholder="Elige un nombre de usuario">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email <span class="required">*</span></label>
                        <input type="email" id="email" name="email" required 
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                               placeholder="tu@email.com">
                    </div>
                </div>
                
                <div class="form-group full-width">
                    <label for="full_name">Nombre Completo <span class="required">*</span></label>
                    <input type="text" id="full_name" name="full_name" required 
                           value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>"
                           placeholder="Tu nombre completo">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="phone">Teléfono</label>
                        <input type="tel" id="phone" name="phone" 
                               value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"
                               placeholder="+51 999 999 999">
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Dirección</label>
                        <input type="text" id="address" name="address" 
                               value="<?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?>"
                               placeholder="Tu dirección">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Contraseña <span class="required">*</span></label>
                        <div class="password-input">
                            <input type="password" id="password" name="password" required 
                                   placeholder="Mínimo 6 caracteres">
                            <button type="button" class="password-toggle" onclick="togglePassword('password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="password-strength">
                            <span id="strength-text">Fuerza de la contraseña</span>
                            <div class="strength-bar">
                                <div class="strength-fill" id="strength-fill"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirmar Contraseña <span class="required">*</span></label>
                        <div class="password-input">
                            <input type="password" id="confirm_password" name="confirm_password" required 
                                   placeholder="Repite tu contraseña">
                            <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="terms-checkbox">
                    <input type="checkbox" id="terms" name="terms" required>
                    <label for="terms">
                        Acepto los <a href="terminos.php" target="_blank">Términos y Condiciones</a> y la 
                        <a href="privacidad.php" target="_blank">Política de Privacidad</a> <span class="required">*</span>
                    </label>
                </div>
                
                <button type="submit" class="btn-register" id="submitBtn">
                    <i class="fas fa-heart"></i> Crear Cuenta
                </button>
            </form>
            
            <!-- Separador -->
            <div class="divider">
                <span>o regístrate con</span>
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
            <div class="register-footer">
                <p>¿Ya tienes una cuenta? <a href="login.php">Inicia sesión aquí</a></p>
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
        
        // Validación de contraseña
        function checkPasswordStrength(password) {
            let strength = 0;
            let feedback = '';
            
            if (password.length >= 6) strength++;
            if (password.match(/[a-z]/)) strength++;
            if (password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;
            
            const strengthText = document.getElementById('strength-text');
            const strengthFill = document.getElementById('strength-fill');
            
            switch(strength) {
                case 0:
                case 1:
                    feedback = 'Muy débil';
                    strengthFill.className = 'strength-fill strength-weak';
                    break;
                case 2:
                    feedback = 'Débil';
                    strengthFill.className = 'strength-fill strength-fair';
                    break;
                case 3:
                    feedback = 'Regular';
                    strengthFill.className = 'strength-fill strength-fair';
                    break;
                case 4:
                    feedback = 'Buena';
                    strengthFill.className = 'strength-fill strength-good';
                    break;
                case 5:
                    feedback = 'Muy fuerte';
                    strengthFill.className = 'strength-fill strength-strong';
                    break;
            }
            
            strengthText.textContent = feedback;
        }
        
        // Validación del formulario
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const terms = document.getElementById('terms').checked;
            
            // Limpiar errores previos
            document.querySelectorAll('.error').forEach(el => el.classList.remove('error'));
            
            let isValid = true;
            
            // Validar contraseñas
            if (password !== confirmPassword) {
                document.getElementById('confirm_password').classList.add('error');
                alert('Las contraseñas no coinciden');
                isValid = false;
            }
            
            if (password.length < 6) {
                document.getElementById('password').classList.add('error');
                alert('La contraseña debe tener al menos 6 caracteres');
                isValid = false;
            }
            
            // Validar términos
            if (!terms) {
                alert('Debes aceptar los términos y condiciones');
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
                return false;
            }
            
            return true;
        });
        
        // Event listeners
        document.getElementById('password').addEventListener('input', function() {
            checkPasswordStrength(this.value);
        });
        
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            if (this.value !== password) {
                this.classList.add('error');
            } else {
                this.classList.remove('error');
            }
        });
        
        // Auto-focus en el primer campo
        document.getElementById('username').focus();
        
        // Limpiar mensajes de error al escribir
        document.querySelectorAll('input, textarea').forEach(input => {
            input.addEventListener('input', function() {
                this.classList.remove('error');
                const alert = document.querySelector('.alert');
                if (alert) {
                    alert.remove();
                }
            });
        });

        // Efecto de confeti al hacer submit (simulado)
        document.getElementById('registerForm').addEventListener('submit', function() {
            // Aquí podrías agregar confeti real si quieres
            console.log('¡Registrando con amor! ❤️');
        });
    </script>
</body>
</html>
