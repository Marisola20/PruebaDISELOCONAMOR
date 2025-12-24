// JavaScript para el carrito de compras

class ShoppingCart {
    constructor() {
        this.items = [];
        this.init();
    }
    
    init() {
        this.loadCart();
        this.updateCartDisplay();
        this.bindEvents();
    }
    
    // Cargar carrito desde localStorage o servidor
    loadCart() {
        // Intentar cargar desde localStorage primero
        const savedCart = localStorage.getItem('cartItems');
        if (savedCart) {
            this.items = JSON.parse(savedCart);
        } else {
            // Si no hay carrito local, cargar desde el servidor
            this.loadCartFromServer();
        }
    }
    
    // Cargar carrito desde el servidor
    loadCartFromServer() {
        if (!isUserLoggedIn()) return;
        
        fetch('api/cart.php?action=get')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.items = data.items || [];
                    this.saveCart();
                }
            })
            .catch(error => {
                console.error('Error cargando carrito:', error);
            });
    }
    
    // Guardar carrito en localStorage
    saveCart() {
        localStorage.setItem('cartItems', JSON.stringify(this.items));
    }
    
    // Agregar producto al carrito
    addItem(productId, quantity = 1, productData = null) {
        const existingItem = this.items.find(item => item.product_id === productId);
        
        if (existingItem) {
            existingItem.quantity += quantity;
        } else {
            const newItem = {
                product_id: productId,
                quantity: quantity,
                ...productData
            };
            this.items.push(newItem);
        }
        
        this.saveCart();
        this.updateCartDisplay();
        this.syncWithServer();
        
        return true;
    }
    
    // Remover producto del carrito
    removeItem(productId) {
        this.items = this.items.filter(item => item.product_id !== productId);
        this.saveCart();
        this.updateCartDisplay();
        this.syncWithServer();
    }
    
    // Actualizar cantidad de un producto
    updateQuantity(productId, quantity) {
        const item = this.items.find(item => item.product_id === productId);
        if (item) {
            if (quantity <= 0) {
                this.removeItem(productId);
            } else {
                item.quantity = quantity;
                this.saveCart();
                this.updateCartDisplay();
                this.syncWithServer();
            }
        }
    }
    
    // Limpiar carrito
    clearCart() {
        this.items = [];
        this.saveCart();
        this.updateCartDisplay();
        this.syncWithServer();
    }
    
    // Obtener total del carrito
    getTotal() {
        return this.items.reduce((total, item) => {
            return total + (item.price * item.quantity);
        }, 0);
    }
    
    // Obtener cantidad total de items
    getItemCount() {
        return this.items.reduce((count, item) => count + item.quantity, 0);
    }
    
    // Actualizar display del carrito
    updateCartDisplay() {
        // Actualizar contador en el header
        const cartCount = document.querySelector('.cart-count');
        if (cartCount) {
            cartCount.textContent = this.getItemCount();
        }
        
        // Actualizar mini carrito si existe
        this.updateMiniCart();
        
        // Actualizar página del carrito si estamos en ella
        if (window.location.pathname.includes('carrito.php')) {
            this.updateCartPage();
        }
    }
    
    // Actualizar mini carrito
    updateMiniCart() {
        const miniCart = document.querySelector('.mini-cart');
        if (!miniCart) return;
        
        if (this.items.length === 0) {
            miniCart.innerHTML = '<p class="empty-cart">Tu carrito está vacío</p>';
            return;
        }
        
        let html = '<div class="mini-cart-items">';
        this.items.forEach(item => {
            html += `
                <div class="mini-cart-item">
                    <img src="${item.image || 'assets/images/placeholder.png'}" alt="${item.name}">
                    <div class="mini-cart-item-info">
                        <h4>${item.name}</h4>
                        <p>S/ ${parseFloat(item.price).toFixed(2)} x ${item.quantity}</p>
                    </div>
                    <button onclick="cart.removeItem(${item.product_id})" class="btn-remove">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
        });
        
        html += `
            </div>
            <div class="mini-cart-total">
                <strong>Total: S/ ${this.getTotal().toFixed(2)}</strong>
            </div>
            <div class="mini-cart-actions">
                <a href="carrito.php" class="btn-secondary">Ver Carrito</a>
                <a href="checkout.php" class="btn-primary">Finalizar Compra</a>
            </div>
        `;
        
        miniCart.innerHTML = html;
    }
    
    // Actualizar página del carrito
    updateCartPage() {
        const cartContainer = document.querySelector('.cart-items');
        if (!cartContainer) return;
        
        if (this.items.length === 0) {
            cartContainer.innerHTML = `
                <div class="empty-cart">
                    <i class="fas fa-shopping-cart"></i>
                    <h3>Tu carrito está vacío</h3>
                    <p>Agrega algunos productos para comenzar</p>
                    <a href="productos.php" class="btn-primary">Ver Productos</a>
                </div>
            `;
            return;
        }
        
        let html = '';
        this.items.forEach(item => {
            html += `
                <div class="cart-item" data-product-id="${item.product_id}">
                    <div class="cart-item-image">
                        <img src="${item.image || 'assets/images/placeholder.png'}" alt="${item.name}">
                    </div>
                    <div class="cart-item-info">
                        <h4>${item.name}</h4>
                        <p class="cart-item-price">S/ ${parseFloat(item.price).toFixed(2)}</p>
                    </div>
                    <div class="cart-item-quantity">
                        <button onclick="cart.updateQuantity(${item.product_id}, ${item.quantity - 1})" class="btn-quantity">-</button>
                        <span>${item.quantity}</span>
                        <button onclick="cart.updateQuantity(${item.product_id}, ${item.quantity + 1})" class="btn-quantity">+</button>
                    </div>
                    <div class="cart-item-total">
                        S/ ${(item.price * item.quantity).toFixed(2)}
                    </div>
                    <button onclick="cart.removeItem(${item.product_id})" class="btn-remove">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;
        });
        
        cartContainer.innerHTML = html;
        
        // Actualizar totales
        this.updateCartTotals();
    }
    
    // Actualizar totales del carrito
    updateCartTotals() {
        const subtotalElement = document.querySelector('.cart-subtotal');
        const totalElement = document.querySelector('.cart-total');
        
        if (subtotalElement) {
            subtotalElement.textContent = `S/ ${this.getTotal().toFixed(2)}`;
        }
        
        if (totalElement) {
            totalElement.textContent = `S/ ${this.getTotal().toFixed(2)}`;
        }
    }
    
    // Sincronizar con el servidor
    syncWithServer() {
        if (!isUserLoggedIn()) return;
        
        fetch('api/cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'sync',
                items: this.items
            })
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                console.error('Error sincronizando carrito:', data.message);
            }
        })
        .catch(error => {
            console.error('Error sincronizando carrito:', error);
        });
    }
    
    // Vincular eventos
    bindEvents() {
        // Evento para mostrar/ocultar mini carrito
        const cartButton = document.querySelector('.btn-cart');
        if (cartButton) {
            cartButton.addEventListener('click', (e) => {
                e.preventDefault();
                this.toggleMiniCart();
            });
        }
        
        // Evento para cerrar mini carrito al hacer clic fuera
        document.addEventListener('click', (e) => {
            const miniCart = document.querySelector('.mini-cart');
            if (miniCart && !miniCart.contains(e.target) && !e.target.closest('.btn-cart')) {
                miniCart.classList.remove('active');
            }
        });
    }
    
    // Mostrar/ocultar mini carrito
    toggleMiniCart() {
        const miniCart = document.querySelector('.mini-cart');
        if (miniCart) {
            miniCart.classList.toggle('active');
        }
    }
    
    // Procesar checkout
    processCheckout() {
        if (this.items.length === 0) {
            showAlert('Tu carrito está vacío', 'error');
            return false;
        }
        
        // Validar que el usuario esté logueado
        if (!isUserLoggedIn()) {
            showAlert('Debes iniciar sesión para continuar', 'error');
            return false;
        }
        
        // Redirigir al checkout
        window.location.href = 'checkout.php';
        return true;
    }
    
    // Aplicar cupón de descuento
    applyCoupon(couponCode) {
        // Implementar lógica de cupones
        return new Promise((resolve, reject) => {
            fetch('api/coupons.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'apply',
                    coupon_code: couponCode
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resolve(data.discount);
                } else {
                    reject(data.message);
                }
            })
            .catch(error => {
                reject('Error aplicando cupón');
            });
        });
    }
}

// Inicializar carrito
const cart = new ShoppingCart();

// Funciones globales para compatibilidad
function addToCart(productId, quantity = 1) {
    return cart.addItem(productId, quantity);
}

function removeFromCart(productId) {
    return cart.removeItem(productId);
}

function updateCartQuantity(productId, quantity) {
    return cart.updateQuantity(productId, quantity);
}

function clearCart() {
    return cart.clearCart();
}

function getCartTotal() {
    return cart.getTotal();
}

function getCartItemCount() {
    return cart.getItemCount();
}

// Función para agregar producto al carrito desde la página de productos
function addProductToCart(productId, productName, productPrice, productImage) {
    const productData = {
        name: productName,
        price: parseFloat(productPrice),
        image: productImage
    };
    
    if (cart.addItem(productId, 1, productData)) {
        showAlert('Producto agregado al carrito', 'success');
        
        // Mostrar mini carrito
        cart.toggleMiniCart();
    } else {
        showAlert('Error al agregar producto', 'error');
    }
}

// Función para procesar checkout
function processCheckout() {
    if (cart.processCheckout()) {
        // El checkout se procesará en la página correspondiente
        return true;
    }
    return false;
}

// Función para aplicar cupón
function applyCoupon() {
    const couponInput = document.getElementById('couponCode');
    const couponCode = couponInput.value.trim();
    
    if (!couponCode) {
        showAlert('Ingresa un código de cupón', 'error');
        return;
    }
    
    cart.applyCoupon(couponCode)
        .then(discount => {
            showAlert(`Cupón aplicado! Descuento: S/ ${discount.toFixed(2)}`, 'success');
            // Actualizar totales con descuento
            updateCartTotalsWithDiscount(discount);
        })
        .catch(error => {
            showAlert(error, 'error');
        });
}

// Función para actualizar totales con descuento
function updateCartTotalsWithDiscount(discount) {
    const subtotal = cart.getTotal();
    const total = subtotal - discount;
    
    const discountElement = document.querySelector('.cart-discount');
    const totalElement = document.querySelector('.cart-total');
    
    if (discountElement) {
        discountElement.textContent = `-S/ ${discount.toFixed(2)}`;
    }
    
    if (totalElement) {
        totalElement.textContent = `S/ ${total.toFixed(2)}`;
    }
}

// Función para mostrar resumen del pedido
function showOrderSummary() {
    const orderSummary = document.querySelector('.order-summary');
    if (!orderSummary) return;
    
    let html = '<h3>Resumen del Pedido</h3>';
    html += '<div class="order-items">';
    
    cart.items.forEach(item => {
        html += `
            <div class="order-item">
                <span>${item.name} x ${item.quantity}</span>
                <span>S/ ${(item.price * item.quantity).toFixed(2)}</span>
            </div>
        `;
    });
    
    html += '</div>';
    html += `<div class="order-total">
        <strong>Total: S/ ${cart.getTotal().toFixed(2)}</strong>
    </div>`;
    
    orderSummary.innerHTML = html;
}

// Función para validar stock antes del checkout
function validateStock() {
    return new Promise((resolve, reject) => {
        const productIds = cart.items.map(item => item.product_id);
        
        fetch('api/stock.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'validate',
                products: productIds
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                resolve(true);
            } else {
                reject(data.message);
            }
        })
        .catch(error => {
            reject('Error validando stock');
        });
    });
}

// Función para calcular envío
function calculateShipping(address) {
    return new Promise((resolve, reject) => {
        fetch('api/shipping.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'calculate',
                address: address
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                resolve(data.shipping_cost);
            } else {
                reject(data.message);
            }
        })
        .catch(error => {
            reject('Error calculando envío');
        });
    });
}

// Inicializar funcionalidades del carrito cuando se carga la página
document.addEventListener('DOMContentLoaded', function() {
    // Crear mini carrito si no existe
    if (!document.querySelector('.mini-cart')) {
        const miniCart = document.createElement('div');
        miniCart.className = 'mini-cart';
        miniCart.style.display = 'none';
        document.body.appendChild(miniCart);
    }
    
    // Mostrar resumen del pedido si estamos en checkout
    if (window.location.pathname.includes('checkout.php')) {
        showOrderSummary();
    }
    
    // Validar stock antes de permitir checkout
    const checkoutForm = document.getElementById('checkoutForm');
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            validateStock()
                .then(() => {
                    // Proceder con el checkout
                    this.submit();
                })
                .catch(error => {
                    showAlert(error, 'error');
                });
        });
    }
});
