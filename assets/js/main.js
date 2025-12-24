// JavaScript principal para "Díselo con Amor"

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar todas las funcionalidades
    initNavigation();
    initModals();
    initAnimations();
    initHeaderEffects();
    updateCartCount();
});

// Efectos del header con glassmorphism
function initHeaderEffects() {
    const header = document.querySelector('.header');
    if (!header) return;
    
    let ticking = false;
    let isScrolling = false;
    let scrollTimeout;
    
    function updateHeader() {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        
        // Agregar clase de movimiento cuando se está scrolleando
        if (scrollTop > 10) {
            header.classList.add('moving');
            isScrolling = true;
            
            // Limpiar timeout anterior
            clearTimeout(scrollTimeout);
            
            // Quitar clase de movimiento después de 150ms sin scroll
            scrollTimeout = setTimeout(() => {
                header.classList.remove('moving');
                isScrolling = false;
            }, 150);
        } else {
            header.classList.remove('moving');
            header.classList.remove('scrolled');
            isScrolling = false;
        }
        
        // Aplicar clase scrolled para el efecto glassmorphism
        if (scrollTop > 50) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
        
        ticking = false;
    }
    
    function requestTick() {
        if (!ticking) {
            requestAnimationFrame(updateHeader);
            ticking = true;
        }
    }
    
    window.addEventListener('scroll', requestTick);
}

// Navegación móvil
function initNavigation() {
    const hamburger = document.querySelector('.hamburger');
    const navMenu = document.querySelector('.nav-menu');
    
    if (hamburger && navMenu) {
        hamburger.addEventListener('click', function() {
            navMenu.classList.toggle('active');
            hamburger.classList.toggle('active');
        });
        
        // Cerrar menú al hacer clic en un enlace
        const navLinks = document.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', () => {
                navMenu.classList.remove('active');
                hamburger.classList.remove('active');
            });
        });
    }
    
    // Navegación suave
    const navLinks = document.querySelectorAll('a[href^="#"]');
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            const targetSection = document.querySelector(targetId);
            
            if (targetSection) {
                const headerHeight = document.querySelector('.header').offsetHeight;
                // Agregar 120px extra para que baje más y se vea mejor
                const extraOffset = -100;
                const targetPosition = targetSection.offsetTop - headerHeight - extraOffset;
                
                window.scrollTo({
                    top: targetPosition,
                    behavior: 'smooth'
                });
            }
        });
    });
}

// Inicializar modales
function initModals() {
    const modals = document.querySelectorAll('.modal');
    
    modals.forEach(modal => {
        const closeBtn = modal.querySelector('.close');
        
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                modal.style.display = 'none';
            });
        }
        
        // Cerrar modal al hacer clic fuera
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        });
    });
}

// Animaciones al hacer scroll
function initAnimations() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);
    
    // Observar elementos para animar
    const animatedElements = document.querySelectorAll('.product-card, .promo-card, .review-card');
    animatedElements.forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(el);
    });
}

// Actualizar contador del carrito
function updateCartCount() {
    const cartCount = document.querySelector('.cart-count');
    if (cartCount) {
        const cartItems = JSON.parse(localStorage.getItem('cartItems')) || [];
        cartCount.textContent = cartItems.length;
    }
}

// Función para vista rápida de productos
function quickView(productId) {
    const modal = document.getElementById('quickViewModal');
    const content = document.getElementById('quickViewContent');
    
    if (modal && content) {
        content.innerHTML = '<div class="loading">Cargando producto...</div>';
        modal.style.display = 'block';
        
        fetch(`api/product.php?id=${productId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const product = data.product;
                    content.innerHTML = `
                        <div class="quick-view-content">
                            <div class="product-quick-image">
                                <img src="${product.image}" alt="${product.name}">
                            </div>
                            <div class="product-quick-info">
                                <h3>${product.name}</h3>
                                <p class="product-description">${product.description}</p>
                                <div class="product-price">
                                    <span class="price">S/ ${parseFloat(product.price).toFixed(2)}</span>
                                    ${product.old_price > 0 ? `<span class="old-price">S/ ${parseFloat(product.old_price).toFixed(2)}</span>` : ''}
                                </div>
                                <div class="product-rating">
                                    ${generateStars(product.rating)}
                                    <span class="rating-count">(${product.review_count} reseñas)</span>
                                </div>
                                <div class="product-actions">
                                    <button class="btn-add-cart" onclick="addToCart(${product.id})">
                                        Agregar al Carrito
                                    </button>
                                    <button class="btn-favorite" onclick="toggleFavorite(${product.id})">
                                        <i class="fas fa-heart"></i> Favorito
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                } else {
                    content.innerHTML = '<div class="alert alert-error">Error al cargar el producto</div>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                content.innerHTML = '<div class="alert alert-error">Error al cargar el producto</div>';
            });
    }
}

// Generar estrellas para rating
function generateStars(rating) {
    let stars = '';
    for (let i = 1; i <= 5; i++) {
        const starClass = i <= rating ? 'fas fa-star active' : 'fas fa-star';
        stars += `<i class="${starClass}"></i>`;
    }
    return stars;
}

// Función para agregar al carrito
function addToCart(productId) {
    if (!isUserLoggedIn()) {
        showAlert('Debes iniciar sesión para agregar productos al carrito', 'error');
        return;
    }
    
    fetch('api/cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'add',
            product_id: productId,
            quantity: 1
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Producto agregado al carrito', 'success');
            updateCartCount();
        } else {
            showAlert(data.message || 'Error al agregar al carrito', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error al agregar al carrito', 'error');
    });
}

// Función para toggle de favoritos
function toggleFavorite(productId) {
    if (!isUserLoggedIn()) {
        showAlert('Debes iniciar sesión para usar favoritos', 'error');
        return;
    }
    
    const button = event.target.closest('.btn-favorite');
    const isFavorite = button.classList.contains('active');
    
    fetch('api/favorites.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: isFavorite ? 'remove' : 'add',
            product_id: productId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (isFavorite) {
                button.classList.remove('active');
                button.innerHTML = '<i class="fas fa-heart"></i>';
                showAlert('Producto removido de favoritos', 'success');
            } else {
                button.classList.add('active');
                button.innerHTML = '<i class="fas fa-heart" style="color: #C24F48;"></i>';
                showAlert('Producto agregado a favoritos', 'success');
            }
        } else {
            showAlert(data.message || 'Error al actualizar favoritos', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error al actualizar favoritos', 'error');
    });
}

// Función para abrir modal de reseña
function openReviewModal() {
    const modal = document.getElementById('reviewModal');
    if (modal) modal.style.display = 'block';
}

// Verificar si el usuario está logueado
function isUserLoggedIn() {
    return document.querySelector('.btn-logout') !== null;
}

// Mostrar alertas
function showAlert(message, type = 'info') {
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.textContent = message;
    document.body.insertBefore(alert, document.body.firstChild);
    setTimeout(() => alert.remove(), 5000);
}

// Funciones auxiliares
function searchProducts(query) {
    if (query.trim() === '') return;
    window.location.href = `productos.php?search=${encodeURIComponent(query)}`;
}

function filterByCategory(categoryId) {
    window.location.href = categoryId === 'all' ? 'productos.php' : `productos.php?category=${categoryId}`;
}

function sortProducts(sortBy) {
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('sort', sortBy);
    window.location.href = `productos.php?${urlParams.toString()}`;
}

function goToPage(page) {
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('page', page);
    window.location.href = `${window.location.pathname}?${urlParams.toString()}`;
}

function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;
    
    const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('error');
            isValid = false;
        } else {
            input.classList.remove('error');
        }
    });
    
    return isValid;
}

function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const icon = event.target;
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

function confirmAction(message, callback) {
    if (confirm(message)) callback();
}

function formatPrice(price) {
    return new Intl.NumberFormat('es-PE', { style: 'currency', currency: 'PEN' }).format(price);
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('es-PE', { year: 'numeric', month: 'long', day: 'numeric' });
}

function truncateText(text, maxLength) {
    return text.length <= maxLength ? text : text.substr(0, maxLength) + '...';
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func(...args), wait);
    };
}

function throttle(func, limit) {
    let inThrottle;
    return function() {
        if (!inThrottle) {
            func.apply(this, arguments);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

function initLazyLoading() {
    const images = document.querySelectorAll('img[data-src]');
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.remove('lazy');
                observer.unobserve(img);
            }
        });
    });
    images.forEach(img => imageObserver.observe(img));
}

function scrollToTop() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function initScrollToTop() {
    const scrollBtn = document.createElement('button');
    scrollBtn.innerHTML = '<i class="fas fa-arrow-up"></i>';
    scrollBtn.className = 'scroll-to-top';
    scrollBtn.onclick = scrollToTop;
    document.body.appendChild(scrollBtn);
    
    window.addEventListener('scroll', () => {
        scrollBtn.style.display = window.pageYOffset > 300 ? 'block' : 'none';
    });
}

// Inicializar extras
document.addEventListener('DOMContentLoaded', function() {
    initLazyLoading();
    initScrollToTop();
    
    const style = document.createElement('style');
    style.textContent = `
        .scroll-to-top {
            position: fixed;
            bottom: 100px;
            right: 30px;
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #C24F48 0%, #842F36 100%);
            color: white;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            display: none;
            z-index: 1000;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(196, 79, 72, 0.3);
        }
        .scroll-to-top:hover {
            background: linear-gradient(135deg, #842F36 0%, #C24F48 100%);
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(196, 79, 72, 0.4);
        }
    `;
    document.head.appendChild(style);
});

// Función para contactar producto
function contactProduct(productId) {
    // Redirigir a WhatsApp con mensaje personalizado
    const message = `Hola! Me interesa el producto con ID: ${productId}. ¿Podrías darme más información?`;
    const whatsappUrl = `https://wa.me/51999999999?text=${encodeURIComponent(message)}`;
    window.open(whatsappUrl, '_blank');
}

// Función para crear efecto de confeti
function createConfetti(event) {
    const button = event.currentTarget;
    const rect = button.getBoundingClientRect();
    const centerX = rect.left + rect.width / 2;
    const centerY = rect.top + rect.height / 2;
    
    // Configuración del confeti
    const confettiConfig = {
        particleCount: 100,
        spread: 70,
        origin: {
            x: centerX / window.innerWidth,
            y: centerY / window.innerHeight
        },
        colors: ['#FF69B4', '#FFD700', '#87CEEB', '#98FB98', '#DDA0DD', '#FF6B6B', '#4ECDC4', '#45B7D1'],
        shapes: ['circle', 'square'],
        gravity: 0.8,
        ticks: 200
    };
    
    // Lanzar confeti
    confetti(confettiConfig);
    
    // Lanzar confeti adicional después de un pequeño delay
    setTimeout(() => {
        confetti({
            ...confettiConfig,
            particleCount: 50,
            spread: 50,
            origin: {
                x: (centerX + 20) / window.innerWidth,
                y: (centerY + 20) / window.innerHeight
            }
        });
    }, 150);
}

// Agregar el efecto de confeti al botón principal
document.addEventListener('DOMContentLoaded', function() {
    const heroButton = document.querySelector('.hero .btn-primary');
    if (heroButton) {
        heroButton.addEventListener('click', createConfetti);
    }
});

// Función para mostrar información del AMORBOT
function showAmorbotInfo() {
    const modal = document.getElementById('amorbotModal');
    if (modal) {
        modal.style.display = 'block';
        
        // Agregar efecto de confeti cuando se abre el modal
        confetti({
            particleCount: 50,
            spread: 60,
            origin: { y: 0.6 },
            colors: ['#FF69B4', '#FFD700', '#87CEEB', '#98FB98', '#DDA0DD']
        });
    }
}
