<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Procesar formulario de configuración
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'update_config') {
        $success = '';
        $error = '';
        
        try {
            // Información de la empresa
            updateSiteConfig($conn, 'company_name', trim($_POST['company_name']));
            updateSiteConfig($conn, 'company_description', trim($_POST['company_description']));
            updateSiteConfig($conn, 'company_address', trim($_POST['company_address']));
            updateSiteConfig($conn, 'company_phone', trim($_POST['company_phone']));
            updateSiteConfig($conn, 'company_email', trim($_POST['company_email']));
            
            // Redes sociales
            updateSiteConfig($conn, 'facebook_url', trim($_POST['facebook_url']));
            updateSiteConfig($conn, 'instagram_url', trim($_POST['instagram_url']));
            updateSiteConfig($conn, 'twitter_url', trim($_POST['twitter_url']));
            updateSiteConfig($conn, 'youtube_url', trim($_POST['youtube_url']));
            
            // Configuración de WhatsApp
            updateSiteConfig($conn, 'whatsapp_number', trim($_POST['whatsapp_number']));
            updateSiteConfig($conn, 'whatsapp_message', trim($_POST['whatsapp_message']));
            
            // Configuración de Yape
            if (isset($_FILES['yape_qr']) && $_FILES['yape_qr']['error'] === 0) {
                $upload_dir = '../assets/images/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_extension = strtolower(pathinfo($_FILES['yape_qr']['name'], PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                if (in_array($file_extension, $allowed_extensions)) {
                    $image_name = 'yape-qr.' . $file_extension;
                    $image_path = 'assets/images/' . $image_name;
                    
                    if (move_uploaded_file($_FILES['yape_qr']['tmp_name'], '../' . $image_path)) {
                        updateSiteConfig($conn, 'yape_qr', $image_path);
                        $success .= 'Imagen QR de Yape actualizada. ';
                    } else {
                        $error .= 'Error al subir la imagen QR de Yape. ';
                    }
                } else {
                    $error .= 'Formato de imagen QR no válido. ';
                }
            }
            
            // Configuración del sitio
            updateSiteConfig($conn, 'site_title', trim($_POST['site_title']));
            updateSiteConfig($conn, 'site_description', trim($_POST['site_description']));
            updateSiteConfig($conn, 'site_keywords', trim($_POST['site_keywords']));
            updateSiteConfig($conn, 'maintenance_mode', isset($_POST['maintenance_mode']) ? 1 : 0);
            
            // Configuración de envío
            updateSiteConfig($conn, 'shipping_cost', floatval($_POST['shipping_cost']));
            updateSiteConfig($conn, 'free_shipping_threshold', floatval($_POST['free_shipping_threshold']));
            
            if (empty($error)) {
                $success = 'Configuración actualizada exitosamente';
            }
            
        } catch (Exception $e) {
            $error = 'Error al actualizar la configuración: ' . $e->getMessage();
        }
    }
}

// Obtener configuración actual
$config = [];
$config_keys = [
    'company_name', 'company_description', 'company_address', 'company_phone', 'company_email',
    'facebook_url', 'instagram_url', 'twitter_url', 'youtube_url',
    'whatsapp_number', 'whatsapp_message', 'yape_qr',
    'site_title', 'site_description', 'site_keywords', 'maintenance_mode',
    'shipping_cost', 'free_shipping_threshold'
];

foreach ($config_keys as $key) {
    $config[$key] = getSiteConfig($conn, $key);
}

// Valores por defecto si no existen
$config['company_name'] = $config['company_name'] ?: 'DCA Perú';
$config['company_description'] = $config['company_description'] ?: 'Tu tienda online de confianza';
$config['whatsapp_number'] = $config['whatsapp_number'] ?: '51999999999';
$config['whatsapp_message'] = $config['whatsapp_message'] ?: 'Hola, me gustaría obtener más información sobre sus productos';
$config['site_title'] = $config['site_title'] ?: 'DCA Perú - Tu Tienda Online';
$config['shipping_cost'] = $config['shipping_cost'] ?: '0.00';
$config['free_shipping_threshold'] = $config['free_shipping_threshold'] ?: '100.00';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración del Sitio - Panel de Administración</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <header class="content-header">
                <div class="header-content">
                    <h1><i class="fas fa-cog"></i> Configuración del Sitio</h1>
                    <p>Personaliza la configuración de tu tienda online</p>
                </div>
            </header>

            <div class="dashboard-content">
                <!-- Mensajes -->
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                <?php if (isset($error)): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" class="config-form">
                    <input type="hidden" name="action" value="update_config">
                    
                    <!-- Información de la Empresa -->
                    <div class="config-section">
                        <div class="section-header">
                            <h3><i class="fas fa-building"></i> Información de la Empresa</h3>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="company_name">Nombre de la Empresa *</label>
                                <input type="text" id="company_name" name="company_name" value="<?php echo htmlspecialchars($config['company_name']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="company_email">Email de Contacto *</label>
                                <input type="email" id="company_email" name="company_email" value="<?php echo htmlspecialchars($config['company_email']); ?>" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="company_phone">Teléfono</label>
                                <input type="text" id="company_phone" name="company_phone" value="<?php echo htmlspecialchars($config['company_phone']); ?>">
                            </div>
                            <div class="form-group">
                                <label for="company_address">Dirección</label>
                                <input type="text" id="company_address" name="company_address" value="<?php echo htmlspecialchars($config['company_address']); ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="company_description">Descripción de la Empresa</label>
                            <textarea id="company_description" name="company_description" rows="3"><?php echo htmlspecialchars($config['company_description']); ?></textarea>
                        </div>
                    </div>

                    <!-- Redes Sociales -->
                    <div class="config-section">
                        <div class="section-header">
                            <h3><i class="fas fa-share-alt"></i> Redes Sociales</h3>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="facebook_url">URL de Facebook</label>
                                <input type="url" id="facebook_url" name="facebook_url" value="<?php echo htmlspecialchars($config['facebook_url']); ?>" placeholder="https://facebook.com/tuempresa">
                            </div>
                            <div class="form-group">
                                <label for="instagram_url">URL de Instagram</label>
                                <input type="url" id="instagram_url" name="instagram_url" value="<?php echo htmlspecialchars($config['instagram_url']); ?>" placeholder="https://instagram.com/tuempresa">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="twitter_url">URL de Twitter</label>
                                <input type="url" id="twitter_url" name="twitter_url" value="<?php echo htmlspecialchars($config['twitter_url']); ?>" placeholder="https://twitter.com/tuempresa">
                            </div>
                            <div class="form-group">
                                <label for="youtube_url">URL de YouTube</label>
                                <input type="url" id="youtube_url" name="youtube_url" value="<?php echo htmlspecialchars($config['youtube_url']); ?>" placeholder="https://youtube.com/tuempresa">
                            </div>
                        </div>
                    </div>

                    <!-- Configuración de WhatsApp -->
                    <div class="config-section">
                        <div class="section-header">
                            <h3><i class="fab fa-whatsapp"></i> Configuración de WhatsApp</h3>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="whatsapp_number">Número de WhatsApp *</label>
                                <input type="text" id="whatsapp_number" name="whatsapp_number" value="<?php echo htmlspecialchars($config['whatsapp_number']); ?>" required placeholder="51999999999">
                                <small>Formato: código de país + número (ej: 51999999999)</small>
                            </div>
                            <div class="form-group">
                                <label for="whatsapp_message">Mensaje Predeterminado</label>
                                <textarea id="whatsapp_message" name="whatsapp_message" rows="2"><?php echo htmlspecialchars($config['whatsapp_message']); ?></textarea>
                                <small>Mensaje que aparecerá por defecto en WhatsApp</small>
                            </div>
                        </div>
                    </div>

                    <!-- Configuración de Yape -->
                    <div class="config-section">
                        <div class="section-header">
                            <h3><i class="fas fa-qrcode"></i> Configuración de Yape</h3>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="yape_qr">Imagen QR de Yape</label>
                                <input type="file" id="yape_qr" name="yape_qr" accept="image/*">
                                <small>Formatos permitidos: JPG, PNG, GIF, WEBP</small>
                            </div>
                            <div class="form-group">
                                <label>QR Actual</label>
                                <div class="current-qr">
                                    <?php if ($config['yape_qr']): ?>
                                        <img src="../<?php echo htmlspecialchars($config['yape_qr']); ?>" alt="QR de Yape" style="max-width: 150px; max-height: 150px;">
                                        <p class="qr-path"><?php echo htmlspecialchars($config['yape_qr']); ?></p>
                                    <?php else: ?>
                                        <div class="no-qr">
                                            <i class="fas fa-qrcode"></i>
                                            <p>No hay QR configurado</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Configuración del Sitio -->
                    <div class="config-section">
                        <div class="section-header">
                            <h3><i class="fas fa-globe"></i> Configuración del Sitio</h3>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="site_title">Título del Sitio *</label>
                                <input type="text" id="site_title" name="site_title" value="<?php echo htmlspecialchars($config['site_title']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="site_description">Descripción del Sitio</label>
                                <textarea id="site_description" name="site_description" rows="2"><?php echo htmlspecialchars($config['site_description']); ?></textarea>
                                <small>Descripción para SEO</small>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="site_keywords">Palabras Clave</label>
                            <input type="text" id="site_keywords" name="site_keywords" value="<?php echo htmlspecialchars($config['site_keywords']); ?>" placeholder="palabra1, palabra2, palabra3">
                            <small>Palabras clave separadas por comas para SEO</small>
                        </div>
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" id="maintenance_mode" name="maintenance_mode" <?php echo $config['maintenance_mode'] ? 'checked' : ''; ?>>
                                <span class="checkmark"></span>
                                Modo mantenimiento
                            </label>
                            <small>Activa el modo mantenimiento para realizar cambios en el sitio</small>
                        </div>
                    </div>

                    <!-- Configuración de Envío -->
                    <div class="config-section">
                        <div class="section-header">
                            <h3><i class="fas fa-shipping-fast"></i> Configuración de Envío</h3>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="shipping_cost">Costo de Envío (S/)</label>
                                <input type="number" id="shipping_cost" name="shipping_cost" value="<?php echo htmlspecialchars($config['shipping_cost']); ?>" step="0.01" min="0">
                                <small>Costo de envío por defecto</small>
                            </div>
                            <div class="form-group">
                                <label for="free_shipping_threshold">Umbral de Envío Gratis (S/)</label>
                                <input type="number" id="free_shipping_threshold" name="free_shipping_threshold" value="<?php echo htmlspecialchars($config['free_shipping_threshold']); ?>" step="0.01" min="0">
                                <small>Monto mínimo para envío gratis</small>
                            </div>
                        </div>
                    </div>

                    <!-- Botones de Acción -->
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Guardar Configuración
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="resetForm()">
                            <i class="fas fa-undo"></i> Restaurar Valores
                        </button>
                    </div>
                </form>

                <!-- Vista Previa -->
                <div class="config-section">
                    <div class="section-header">
                        <h3><i class="fas fa-eye"></i> Vista Previa</h3>
                    </div>
                    <div class="preview-content">
                        <div class="preview-item">
                            <h4>Información de Contacto</h4>
                            <p><strong>Empresa:</strong> <span id="preview-company"><?php echo htmlspecialchars($config['company_name']); ?></span></p>
                            <p><strong>Email:</strong> <span id="preview-email"><?php echo htmlspecialchars($config['company_email']); ?></span></p>
                            <p><strong>Teléfono:</strong> <span id="preview-phone"><?php echo htmlspecialchars($config['company_phone']); ?></span></p>
                        </div>
                        <div class="preview-item">
                            <h4>WhatsApp</h4>
                            <p><strong>Número:</strong> <span id="preview-whatsapp"><?php echo htmlspecialchars($config['whatsapp_number']); ?></span></p>
                            <p><strong>Mensaje:</strong> <span id="preview-whatsapp-msg"><?php echo htmlspecialchars($config['whatsapp_message']); ?></span></p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="assets/js/admin.js"></script>
    <script>
        // Actualizar vista previa en tiempo real
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('input, textarea');
            inputs.forEach(input => {
                input.addEventListener('input', updatePreview);
            });
        });

        function updatePreview() {
            // Actualizar información de la empresa
            const companyName = document.getElementById('company_name').value;
            const companyEmail = document.getElementById('company_email').value;
            const companyPhone = document.getElementById('company_phone').value;
            const whatsappNumber = document.getElementById('whatsapp_number').value;
            const whatsappMessage = document.getElementById('whatsapp_message').value;

            document.getElementById('preview-company').textContent = companyName;
            document.getElementById('preview-email').textContent = companyEmail;
            document.getElementById('preview-phone').textContent = companyPhone;
            document.getElementById('preview-whatsapp').textContent = whatsappNumber;
            document.getElementById('preview-whatsapp-msg').textContent = whatsappMessage;
        }

        function resetForm() {
            if (confirmAction('¿Estás seguro de que quieres restaurar los valores por defecto?')) {
                document.getElementById('config-form').reset();
                updatePreview();
            }
        }

        // Validación del formulario
        document.querySelector('.config-form').addEventListener('submit', function(e) {
            const whatsappNumber = document.getElementById('whatsapp_number').value;
            
            if (whatsappNumber && !/^\d{10,15}$/.test(whatsappNumber)) {
                e.preventDefault();
                alert('El número de WhatsApp debe contener solo números (10-15 dígitos)');
                document.getElementById('whatsapp_number').focus();
                return false;
            }
        });
    </script>
</body>
</html>
