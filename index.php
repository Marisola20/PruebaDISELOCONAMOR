<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Obtener productos destacados
$featured_products = getFeaturedProducts($conn);
// Obtener promociones activas
$active_promotions = getActivePromotions($conn);
// Obtener reseñas del foro
$reviews = getForumReviews($conn);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Díselo con Amor - Tu Tienda Online</title>
    <link rel="stylesheet" href="assets/css/style.css?v=2.28">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Fuentes personalizadas para el diseño romántico -->
    <link
        href="https://fonts.googleapis.com/css2?family=GFS+Didot:ital@0;1&family=Didact+Gothic&family=Montserrat:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap"
        rel="stylesheet">
</head>

<body>
    <!-- Header -->
    <header class="header">
        <nav class="navbar">
            <div class="nav-container">
                <div class="nav-logo">
                    <img src="assets/img/logo-web.svg" alt="Díselo con Amor" width="150" class="logo-desktop">
                    <img src="assets/img/logo-phone.svg" alt="Díselo con Amor" width="80" class="logo-mobile">
                </div>
                <ul class="nav-menu">
                    <li><a href="#inicio" class="nav-link">Inicio</a></li>
                    <li><a href="#productos" class="nav-link">Productos</a></li>
                    <li><a href="#promociones" class="nav-link">Promociones</a></li>
                    <li><a href="#reseñas" class="nav-link">Reseñas</a></li>
                    <li><a href="#contacto" class="nav-link">Contacto</a></li>

                    <!-- Botones móviles (solo visibles en móvil) -->
                    <div class="mobile-actions">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <a href="perfil.php" class="btn-profile"><i class="fas fa-user"></i> Perfil</a>
                            <a href="favoritos.php" class="btn-favorites"><i class="fas fa-heart"></i> Favoritos</a>
                            <a href="carrito.php" class="btn-cart"><i class="fas fa-shopping-cart"></i> Carrito</a>
                            <a href="logout.php" class="btn-logout">Cerrar Sesión</a>
                        <?php else: ?>
                            <a href="login.php" class="btn-login">Iniciar Sesión</a>
                            <a href="register.php" class="btn-register">Registrarse</a>
                        <?php endif; ?>
                    </div>
                </ul>
                <div class="nav-actions">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="perfil.php" class="btn-profile"><i class="fas fa-user"></i></a>
                        <a href="favoritos.php" class="btn-favorites"><i class="fas fa-heart"></i></a>
                        <a href="carrito.php" class="btn-cart"><i class="fas fa-shopping-cart"></i> <span
                                class="cart-count">0</span></a>
                        <a href="logout.php" class="btn-logout">Cerrar Sesión</a>
                    <?php else: ?>
                        <a href="login.php" class="btn-login">Iniciar Sesión</a>
                        <a href="register.php" class="btn-register">Registrarse</a>
                    <?php endif; ?>
                </div>
                <div class="hamburger">
                    <span class="bar"></span>
                    <span class="bar"></span>
                    <span class="bar"></span>
                </div>
            </div>
        </nav>
    </header>

    <!-- Hero Section -->
    <section id="inicio" class="hero">
        <div class="hero-content">
            <h1>Díselo con Amor</h1>
            <p>Descubre la magia de expresar tus sentimientos con nuestros productos románticos y elegantes</p>
            <a href="#productos" class="btn-primary"><i class="fas fa-gift"></i> Ver Productos</a>
        </div>
        <div class="hero-decoration">
            <div class="floating-hearts">
                <i class="fas fa-heart"></i>
                <i class="fas fa-heart"></i>
                <i class="fas fa-heart"></i>
                <i class="fas fa-heart"></i>
                <i class="fas fa-heart"></i>
            </div>
        </div>
    </section>

    <!-- Promociones -->
    <?php if (!empty($active_promotions)): ?>
        <section id="promociones" class="promotions">
            <div class="container">
                <h2 class="section-title">Promociones Especiales</h2>
                <div class="promotions-grid">
                    <?php foreach ($active_promotions as $promo): ?>
                        <div class="promo-card">
                            <div class="promo-image">
                                <img src="<?php echo $promo['image']; ?>" alt="<?php echo $promo['title']; ?>">
                            </div>
                            <div class="promo-content">
                                <h3><?php echo $promo['title']; ?></h3>
                                <p><?php echo $promo['description']; ?></p>
                                <div class="promo-price">
                                    <span class="old-price">S/ <?php echo number_format($promo['old_price'], 2); ?></span>
                                    <span class="new-price">S/ <?php echo number_format($promo['new_price'], 2); ?></span>
                                </div>
                                <p class="promo-date">Válido hasta: <?php echo date('d/m/Y', strtotime($promo['end_date'])); ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- Productos Destacados -->
    <section id="productos" class="products">
        <div class="container">
            <h2 class="section-title">Productos</h2>
            <p class="section-tagline">Encuentra el regalo perfecto y hecho a tu medida ✨</p>
            <div class="products-grid">
                <?php foreach ($featured_products as $product): ?>
                    <div class="product-card" data-product-id="<?php echo $product['id']; ?>">
                        <div class="product-image">
                            <?php if (!empty($product['image'])): ?>
                                <img src="<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>">
                            <?php else: ?>
                                <div class="image-placeholder">
                                    <i class="fas fa-mountain"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="product-info">
                            <h3><?php echo $product['name']; ?></h3>
                            <p class="product-subtitle"><?php echo substr($product['description'], 0, 50); ?>...</p>
                            <button class="btn-contact" onclick="contactProduct(<?php echo $product['id']; ?>)">
                                Contactar
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="view-all">
                <a href="productos.php" class="btn-secondary">Ver Todos los Productos</a>
            </div>
        </div>
    </section>

    <!-- Foro de Reseñas -->
    <section id="reseñas" class="reviews">
        <div class="container">
            <h2 class="section-title">ReseñasSSSS</h2>
            <p class="section-tagline">Tu opinión hace crecer nuestra familia de amor y detalles 🥰</p>
            <div class="reviews-grid">
                <?php foreach ($reviews as $review): ?>
                    <div class="review-card">
                        <div class="review-content">
                            <p class="review-text"><?php echo $review['comment']; ?></p>
                            <div class="reviewer-info">
                                <div class="reviewer-avatar">
                                    <img src="<?php echo $review['user_avatar'] ?: 'assets/images/default-avatar.png'; ?>"
                                        alt="Avatar">
                                </div>
                                <div class="reviewer-details">
                                    <h4><?php echo $review['user_name']; ?></h4>
                                    <span
                                        class="review-date"><?php echo date('d/m/Y', strtotime($review['created_at'])); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="add-review">
                    <button class="btn-primary" onclick="openReviewModal()">Agregar Reseña</button>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Sección de Contacto -->
    <section id="contacto" class="contact">
        <div class="container">
            <h2 class="section-title">Contacto</h2>
            <p class="section-tagline">¿Tienes alguna pregunta o quieres saber más sobre nuestros servicios?
                ¡Contáctanos!</p>

            <div class="contact-content">
                <!-- Formulario de Contacto -->
                <div class="contact-form-container">
                    <form class="contact-form" id="contactForm">
                        <div class="form-group">
                            <label for="fullName">Nombre Completo:</label>
                            <input type="text" id="fullName" name="fullName" placeholder="nombre completo" required>
                        </div>

                        <div class="form-group">
                            <label for="subject">Asunto:</label>
                            <input type="text" id="subject" name="subject" placeholder="Selecciona el asunto" required>
                        </div>

                        <div class="form-group">
                            <label for="email">Correo:</label>
                            <input type="text" id="email" name="email" placeholder="correo electronico" required>
                        </div>

                        <div class="form-group">
                            <label for="message">Mensaje:</label>
                            <textarea id="message" name="message" rows="5" placeholder="Mensaje" required></textarea>
                        </div>

                        <button type="submit" class="btn-primary">Enviar</button>
                    </form>
                </div>

                <!-- Información de Contacto y AMORBOT -->
                <div class="contact-info-container">
                    <div class="amorbot-section">
                        <div class="amorbot-illustration">
                            <img src="assets/img/amor-robot.svg" alt="AMORBOT" class="amorbot-image">
                        </div>
                        <h3 class="amorbot-title">AMORBOT</h3>
                        <p class="amorbot-tagline">"Hoy es un gran día para recordarte lo especial que eres❤️❤️"</p>
                    </div>

                    <div class="more-info-card">
                        <h4>Más información</h4>
                        <div class="contact-details">
                            <div class="contact-item">
                                <i class="fas fa-envelope"></i>
                                <span>contacto@diseloconamor.com</span>
                            </div>
                            <div class="contact-item">
                                <i class="fas fa-phone"></i>
                                <span>981 451 159</span>
                            </div>
                            <div class="contact-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <span>Lima, Perú</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-links">
                    <a href="#inicio">Inicio</a>
                    <a href="#productos">Productos</a>
                    <a href="#promociones">Promociones</a>
                    <a href="#reseñas">Reseñas</a>
                    <a href="#contacto">Contacto</a>
                </div>
                <div class="footer-social">
                    <a href="#" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" title="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="https://www.tiktok.com/@diselo_conamor" title="TikTok"><i class="fab fa-tiktok"></i></a>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 Díselo con Amor. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>

    <!-- Botón flotante de WhatsApp -->
    <div class="whatsapp-float">
        <a href="https://wa.me/51981451159" target="_blank">
            <i class="fab fa-whatsapp"></i>
        </a>
    </div>

    <!-- Botón flotante del AMORBOT -->
    <div class="amorbot-float">
        <a href="#" onclick="showAmorbotInfo()">
            <img src="assets/img/amor-robot.svg" alt="AMORBOT" class="amorbot-float-image">
        </a>
    </div>

    <!-- Modal de Vista Rápida -->
    <div id="quickViewModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <div id="quickViewContent"></div>
        </div>
    </div>

    <!-- Modal de Reseña -->
    <div id="reviewModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Agregar Reseña</h3>
            <form id="reviewForm">
                <div class="form-group">
                    <label>Calificación:</label>
                    <div class="rating-input">
                        <input type="radio" name="rating" value="5" id="star5">
                        <label for="star5"><i class="fas fa-star"></i></label>
                        <input type="radio" name="rating" value="4" id="star4">
                        <label for="star4"><i class="fas fa-star"></i></label>
                        <input type="radio" name="rating" value="3" id="star3">
                        <label for="star3"><i class="fas fa-star"></i></label>
                        <input type="radio" name="rating" value="2" id="star2">
                        <label for="star2"><i class="fas fa-star"></i></label>
                        <input type="radio" name="rating" value="1" id="star1">
                        <label for="star1"><i class="fas fa-star"></i></label>
                    </div>
                </div>
                <div class="form-group">
                    <label for="reviewComment">Comentario:</label>
                    <textarea id="reviewComment" name="comment" rows="4" required></textarea>
                </div>
                <button type="submit" class="btn-primary">Enviar Reseña</button>
            </form>
        </div>
    </div>

    <!-- Modal del AMORBOT -->
    <div id="amorbotModal" class="modal">
        <div class="modal-content amorbot-modal">
            <span class="close">&times;</span>
            <div class="amorbot-modal-header">
                <img src="assets/img/amor-robot.svg" alt="AMORBOT" class="amorbot-modal-image">
                <h3>¡Hola! Soy AMORBOT 🤖❤️</h3>
            </div>
            <div class="amorbot-modal-content">
                <h4>¿Cómo funciona nuestra página?</h4>
                <div class="how-it-works">
                    <div class="step">
                        <div class="step-icon">🎁</div>
                        <div class="step-content">
                            <h5>1. Explora Productos</h5>
                            <p>Navega por nuestra colección de regalos románticos y personalizados</p>
                        </div>
                    </div>
                    <div class="step">
                        <div class="step-icon">💬</div>
                        <div class="step-content">
                            <h5>2. Contacta Directamente</h5>
                            <p>Usa el botón "Contactar" para hablar con nosotros por WhatsApp</p>
                        </div>
                    </div>
                    <div class="step">
                        <div class="step-icon">✨</div>
                        <div class="step-content">
                            <h5>3. Personaliza tu Regalo</h5>
                            <p>Te ayudamos a crear el regalo perfecto para tu ser especial</p>
                        </div>
                    </div>
                    <div class="step">
                        <div class="step-icon">🚚</div>
                        <div class="step-content">
                            <h5>4. Envío a Todo el Perú</h5>
                            <p>Recibe tu regalo personalizado en la puerta de tu casa</p>
                        </div>
                    </div>
                </div>
                <div class="amorbot-tip">
                    <p><strong>💡 Tip del AMORBOT:</strong> "Los mejores regalos son los que vienen del corazón. ¡Déjame
                        ayudarte a encontrar el perfecto!"</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    <script src="assets/js/main.js?v=2.14"></script>
    <script src="assets/js/cart.js"></script>
    <script src="assets/js/reviews.js"></script>
</body>

</html>