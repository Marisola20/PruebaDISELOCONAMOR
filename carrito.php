<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$cart_items = getUserCart($conn, $user_id);
$cart_total = getCartTotal($conn, $user_id);

// Procesar acciones del carrito
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_quantity') {
        $product_id = $_POST['product_id'];
        $quantity = (int)$_POST['quantity'];
        
        if ($quantity <= 0) {
            // Remover producto
            $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$user_id, $product_id]);
        } else {
            // Actualizar cantidad
            $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$quantity, $user_id, $product_id]);
        }
        
        // Redirigir para evitar reenvío del formulario
        header('Location: carrito.php');
        exit();
    } elseif ($action === 'remove_item') {
        $product_id = $_POST['product_id'];
        
        $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$user_id, $product_id]);
        
        header('Location: carrito.php');
        exit();
    } elseif ($action === 'clear_cart') {
        $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        header('Location: carrito.php');
        exit();
    }
}

// Obtener configuración del sitio
$yape_qr = getSiteConfig($conn, 'yape_qr');
$whatsapp_number = getSiteConfig($conn, 'whatsapp_number');
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrito de Compras - DCA Perú</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .cart-container {
            max-width: 1200px;
            margin: 120px auto 40px;
            padding: 0 20px;
        }
        
        .cart-header {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .cart-header h1 {
            font-size: 2.5rem;
            color: #333;
            margin-bottom: 1rem;
        }
        
        .cart-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }
        
        .cart-items {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .cart-item {
            display: grid;
            grid-template-columns: auto 2fr 1fr 1fr auto;
            gap: 1rem;
            align-items: center;
            padding: 1.5rem 0;
            border-bottom: 1px solid #e1e5e9;
        }
        
        .cart-item:last-child {
            border-bottom: none;
        }
        
        .cart-item-image {
            width: 80px;
            height: 80px;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .cart-item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .cart-item-info h3 {
            margin: 0 0 0.5rem 0;
            color: #333;
            font-size: 1.1rem;
        }
        
        .cart-item-price {
            color: #2ecc71;
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .cart-item-quantity {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-quantity {
            width: 35px;
            height: 35px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        
        .btn-quantity:hover {
            border-color: #667eea;
            color: #667eea;
        }
        
        .quantity-input {
            width: 50px;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 0.5rem;
        }
        
        .cart-item-total {
            font-weight: 600;
            color: #333;
            font-size: 1.1rem;
        }
        
        .btn-remove {
            background: none;
            border: none;
            color: #e74c3c;
            cursor: pointer;
            font-size: 1.2rem;
            padding: 0.5rem;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }
        
        .btn-remove:hover {
            background-color: #fee;
        }
        
        .cart-summary {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            height: fit-content;
            position: sticky;
            top: 120px;
        }
        
        .summary-title {
            font-size: 1.5rem;
            color: #333;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            padding: 0.5rem 0;
        }
        
        .summary-row.total {
            border-top: 2px solid #e1e5e9;
            padding-top: 1rem;
            font-size: 1.2rem;
            font-weight: 700;
            color: #333;
        }
        
        .empty-cart {
            text-align: center;
            padding: 3rem;
            color: #666;
        }
        
        .empty-cart i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 1rem;
        }
        
        .empty-cart h3 {
            margin-bottom: 1rem;
            color: #333;
        }
        
        .empty-cart p {
            margin-bottom: 2rem;
        }
        
        .btn-checkout {
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 1rem;
        }
        
        .btn-checkout:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102,126,234,0.3);
        }
        
        .btn-checkout:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .btn-continue {
            width: 100%;
            background: transparent;
            color: #667eea;
            border: 2px solid #667eea;
            padding: 1rem;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: block;
            text-align: center;
        }
        
        .btn-continue:hover {
            background: #667eea;
            color: white;
        }
        
        .cart-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .btn-clear {
            flex: 1;
            background: #e74c3c;
            color: white;
            border: none;
            padding: 0.8rem;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        
        .btn-clear:hover {
            background: #c0392b;
        }
        
        .btn-update {
            flex: 1;
            background: #3498db;
            color: white;
            border: none;
            padding: 0.8rem;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        
        .btn-update:hover {
            background: #2980b9;
        }
        
        .payment-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-top: 1.5rem;
        }
        
        .payment-info h4 {
            color: #333;
            margin-bottom: 1rem;
            text-align: center;
        }
        
        .yape-qr {
            text-align: center;
            margin-bottom: 1rem;
        }
        
        .yape-qr img {
            max-width: 200px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .whatsapp-info {
            text-align: center;
            margin-top: 1rem;
        }
        
        .whatsapp-info a {
            display: inline-block;
            background: #25d366;
            color: white;
            padding: 0.8rem 1.5rem;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .whatsapp-info a:hover {
            background: #128c7e;
            transform: translateY(-2px);
        }
        
        @media (max-width: 768px) {
            .cart-content {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .cart-item {
                grid-template-columns: 1fr;
                text-align: center;
                gap: 1rem;
            }
            
            .cart-item-image {
                width: 100px;
                height: 100px;
                margin: 0 auto;
            }
            
            .cart-item-quantity {
                justify-content: center;
            }
            
            .cart-summary {
                position: static;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <nav class="navbar">
            <div class="nav-container">
                <div class="nav-logo">
                    <h2>DCA Perú</h2>
                </div>
                <ul class="nav-menu">
                    <li><a href="index.php" class="nav-link">Inicio</a></li>
                    <li><a href="productos.php" class="nav-link">Productos</a></li>
                    <li><a href="index.php#promociones" class="nav-link">Promociones</a></li>
                    <li><a href="index.php#reseñas" class="nav-link">Reseñas</a></li>
                </ul>
                <div class="nav-actions">
                    <a href="perfil.php" class="btn-profile"><i class="fas fa-user"></i></a>
                    <a href="favoritos.php" class="btn-favorites"><i class="fas fa-heart"></i></a>
                    <a href="carrito.php" class="btn-cart active"><i class="fas fa-shopping-cart"></i> <span class="cart-count"><?php echo count($cart_items); ?></span></a>
                    <a href="logout.php" class="btn-logout">Cerrar Sesión</a>
                </div>
            </div>
        </nav>
    </header>

    <div class="cart-container">
        <div class="cart-header">
            <h1>Carrito de Compras</h1>
            <p>Revisa tus productos antes de finalizar la compra</p>
        </div>

        <div class="cart-content">
            <div class="cart-items">
                <?php if (empty($cart_items)): ?>
                    <div class="empty-cart">
                        <i class="fas fa-shopping-cart"></i>
                        <h3>Tu carrito está vacío</h3>
                        <p>Agrega algunos productos para comenzar</p>
                        <a href="productos.php" class="btn-primary">Ver Productos</a>
                    </div>
                <?php else: ?>
                    <form method="POST" action="" id="cartForm">
                        <input type="hidden" name="action" value="update_quantity">
                        
                        <?php foreach ($cart_items as $item): ?>
                            <div class="cart-item">
                                <div class="cart-item-image">
                                    <img src="<?php echo $item['image'] ?: 'assets/images/placeholder.png'; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                </div>
                                
                                <div class="cart-item-info">
                                    <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                    <p class="cart-item-price">S/ <?php echo number_format($item['price'], 2); ?></p>
                                </div>
                                
                                <div class="cart-item-quantity">
                                    <button type="button" class="btn-quantity" onclick="updateQuantity(<?php echo $item['product_id']; ?>, -1)">-</button>
                                    <input type="number" name="quantity_<?php echo $item['product_id']; ?>" 
                                           value="<?php echo $item['quantity']; ?>" min="1" max="99" 
                                           class="quantity-input" onchange="updateQuantity(<?php echo $item['product_id']; ?>, this.value, true)">
                                    <button type="button" class="btn-quantity" onclick="updateQuantity(<?php echo $item['product_id']; ?>, 1)">+</button>
                                </div>
                                
                                <div class="cart-item-total">
                                    S/ <?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                                </div>
                                
                                <button type="button" class="btn-remove" onclick="removeItem(<?php echo $item['product_id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        <?php endforeach; ?>
                        
                        <div class="cart-actions">
                            <button type="button" class="btn-clear" onclick="clearCart()">
                                <i class="fas fa-trash"></i> Limpiar Carrito
                            </button>
                            <button type="button" class="btn-update" onclick="updateAllQuantities()">
                                <i class="fas fa-sync"></i> Actualizar
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>

            <div class="cart-summary">
                <h3 class="summary-title">Resumen del Pedido</h3>
                
                <?php if (!empty($cart_items)): ?>
                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <span>S/ <?php echo number_format($cart_total, 2); ?></span>
                    </div>
                    
                    <div class="summary-row">
                        <span>Envío:</span>
                        <span>Gratis</span>
                    </div>
                    
                    <div class="summary-row total">
                        <span>Total:</span>
                        <span>S/ <?php echo number_format($cart_total, 2); ?></span>
                    </div>
                    
                    <button class="btn-checkout" onclick="proceedToCheckout()">
                        <i class="fas fa-credit-card"></i> Proceder al Pago
                    </button>
                    
                    <a href="productos.php" class="btn-continue">
                        <i class="fas fa-arrow-left"></i> Seguir Comprando
                    </a>
                    
                    <div class="payment-info">
                        <h4>Información de Pago</h4>
                        <div class="yape-qr">
                            <img src="<?php echo $yape_qr ?: 'assets/images/yape-qr.png'; ?>" alt="QR de Yape">
                        </div>
                        <p style="text-align: center; margin-bottom: 1rem;">
                            Escanea el código QR para pagar con Yape
                        </p>
                        <div class="whatsapp-info">
                            <a href="https://wa.me/<?php echo $whatsapp_number; ?>?text=Hola, quiero hacer un pedido desde mi carrito" target="_blank">
                                <i class="fab fa-whatsapp"></i> Enviar Pedido por WhatsApp
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <span>S/ 0.00</span>
                    </div>
                    
                    <div class="summary-row">
                        <span>Envío:</span>
                        <span>Gratis</span>
                    </div>
                    
                    <div class="summary-row total">
                        <span>Total:</span>
                        <span>S/ 0.00</span>
                    </div>
                    
                    <a href="productos.php" class="btn-continue">
                        <i class="fas fa-shopping-bag"></i> Comenzar a Comprar
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Función para actualizar cantidad
        function updateQuantity(productId, change, isDirectInput = false) {
            let newQuantity;
            
            if (isDirectInput) {
                newQuantity = parseInt(change);
            } else {
                const currentInput = document.querySelector(`input[name="quantity_${productId}"]`);
                const currentQuantity = parseInt(currentInput.value);
                newQuantity = currentQuantity + change;
            }
            
            if (newQuantity <= 0) {
                removeItem(productId);
                return;
            }
            
            // Actualizar input
            const input = document.querySelector(`input[name="quantity_${productId}"]`);
            input.value = newQuantity;
            
            // Actualizar en el servidor
            updateQuantityOnServer(productId, newQuantity);
        }
        
        // Función para remover item
        function removeItem(productId) {
            if (confirm('¿Estás seguro de que quieres remover este producto del carrito?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="remove_item">
                    <input type="hidden" name="product_id" value="${productId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Función para limpiar carrito
        function clearCart() {
            if (confirm('¿Estás seguro de que quieres limpiar todo el carrito?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="clear_cart">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Función para actualizar todas las cantidades
        function updateAllQuantities() {
            const form = document.getElementById('cartForm');
            if (form) {
                form.submit();
            }
        }
        
        // Función para proceder al checkout
        function proceedToCheckout() {
            // Verificar que hay productos en el carrito
            const cartItems = document.querySelectorAll('.cart-item');
            if (cartItems.length === 0) {
                alert('Tu carrito está vacío');
                return;
            }
            
            // Redirigir al checkout
            window.location.href = 'checkout.php';
        }
        
        // Función para actualizar cantidad en el servidor
        function updateQuantityOnServer(productId, quantity) {
            fetch('api/cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'update_quantity',
                    product_id: productId,
                    quantity: quantity
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Actualizar totales
                    updateCartTotals();
                } else {
                    console.error('Error actualizando cantidad:', data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }
        
        // Función para actualizar totales del carrito
        function updateCartTotals() {
            const cartItems = document.querySelectorAll('.cart-item');
            let subtotal = 0;
            
            cartItems.forEach(item => {
                const price = parseFloat(item.querySelector('.cart-item-price').textContent.replace('S/ ', ''));
                const quantity = parseInt(item.querySelector('.quantity-input').value);
                const total = price * quantity;
                
                // Actualizar total del item
                item.querySelector('.cart-item-total').textContent = `S/ ${total.toFixed(2)}`;
                
                subtotal += total;
            });
            
            // Actualizar resumen
            const subtotalElement = document.querySelector('.summary-row:not(.total) span:last-child');
            const totalElement = document.querySelector('.summary-row.total span:last-child');
            
            if (subtotalElement) {
                subtotalElement.textContent = `S/ ${subtotal.toFixed(2)}`;
            }
            
            if (totalElement) {
                totalElement.textContent = `S/ ${subtotal.toFixed(2)}`;
            }
        }
        
        // Actualizar totales cuando cambie la cantidad
        document.addEventListener('input', function(e) {
            if (e.target.classList.contains('quantity-input')) {
                updateCartTotals();
            }
        });
    </script>
</body>
</html>
