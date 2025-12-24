// JavaScript para manejo de reseñas y calificaciones

class ReviewSystem {
    constructor() {
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.initRatingInputs();
    }
    
    // Vincular eventos
    bindEvents() {
        // Formulario de reseña del foro
        const forumReviewForm = document.getElementById('reviewForm');
        if (forumReviewForm) {
            forumReviewForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.submitForumReview();
            });
        }
        
        // Formulario de reseña de producto
        const productReviewForm = document.getElementById('productReviewForm');
        if (productReviewForm) {
            productReviewForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.submitProductReview();
            });
        }
        
        // Botones para abrir modales de reseña
        const reviewButtons = document.querySelectorAll('.btn-add-review');
        reviewButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                this.openReviewModal(button.dataset.type, button.dataset.id);
            });
        });
    }
    
    // Inicializar inputs de rating
    initRatingInputs() {
        const ratingInputs = document.querySelectorAll('.rating-input');
        ratingInputs.forEach(container => {
            const stars = container.querySelectorAll('label');
            const input = container.querySelector('input[type="radio"]:checked');
            
            stars.forEach((star, index) => {
                star.addEventListener('click', () => {
                    // Seleccionar la estrella clickeada
                    const radio = container.querySelector(`input[value="${index + 1}"]`);
                    radio.checked = true;
                    
                    // Actualizar visualización
                    this.updateStarDisplay(container, index + 1);
                });
                
                star.addEventListener('mouseenter', () => {
                    this.updateStarDisplay(container, index + 1);
                });
            });
            
            container.addEventListener('mouseleave', () => {
                const selectedRating = container.querySelector('input[type="radio"]:checked');
                if (selectedRating) {
                    this.updateStarDisplay(container, parseInt(selectedRating.value));
                } else {
                    this.updateStarDisplay(container, 0);
                }
            });
        });
    }
    
    // Actualizar visualización de estrellas
    updateStarDisplay(container, rating) {
        const stars = container.querySelectorAll('label i');
        stars.forEach((star, index) => {
            if (index < rating) {
                star.style.color = '#ffd700';
            } else {
                star.style.color = '#ddd';
            }
        });
    }
    
    // Abrir modal de reseña
    openReviewModal(type, id = null) {
        if (type === 'forum') {
            this.openForumReviewModal();
        } else if (type === 'product') {
            this.openProductReviewModal(id);
        }
    }
    
    // Abrir modal de reseña del foro
    openForumReviewModal() {
        const modal = document.getElementById('reviewModal');
        if (modal) {
            modal.style.display = 'block';
        }
    }
    
    // Abrir modal de reseña de producto
    openProductReviewModal(productId) {
        const modal = document.getElementById('productReviewModal');
        if (modal) {
            // Configurar el formulario para el producto específico
            const form = modal.querySelector('#productReviewForm');
            if (form) {
                form.dataset.productId = productId;
            }
            modal.style.display = 'block';
        }
    }
    
    // Enviar reseña del foro
    submitForumReview() {
        const form = document.getElementById('reviewForm');
        const rating = form.querySelector('input[name="rating"]:checked');
        const comment = form.querySelector('#reviewComment').value.trim();
        
        if (!rating) {
            showAlert('Por favor selecciona una calificación', 'error');
            return;
        }
        
        if (!comment) {
            showAlert('Por favor escribe un comentario', 'error');
            return;
        }
        
        // Enviar reseña
        fetch('api/reviews.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'add_forum_review',
                rating: parseInt(rating.value),
                comment: comment
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Reseña enviada exitosamente', 'success');
                this.closeReviewModal('reviewModal');
                this.refreshForumReviews();
                form.reset();
            } else {
                showAlert(data.message || 'Error al enviar reseña', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error al enviar reseña', 'error');
        });
    }
    
    // Enviar reseña de producto
    submitProductReview() {
        const form = document.getElementById('productReviewForm');
        const productId = form.dataset.productId;
        const rating = form.querySelector('input[name="rating"]:checked');
        const comment = form.querySelector('#productReviewComment').value.trim();
        
        if (!rating) {
            showAlert('Por favor selecciona una calificación', 'error');
            return;
        }
        
        if (!comment) {
            showAlert('Por favor escribe un comentario', 'error');
            return;
        }
        
        // Enviar reseña
        fetch('api/reviews.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'add_product_review',
                product_id: productId,
                rating: parseInt(rating.value),
                comment: comment
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Reseña enviada exitosamente', 'success');
                this.closeReviewModal('productReviewModal');
                this.refreshProductReviews(productId);
                form.reset();
            } else {
                showAlert(data.message || 'Error al enviar reseña', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error al enviar reseña', 'error');
        });
    }
    
    // Cerrar modal de reseña
    closeReviewModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'none';
        }
    }
    
    // Actualizar reseñas del foro
    refreshForumReviews() {
        fetch('api/reviews.php?action=get_forum_reviews')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.updateForumReviewsDisplay(data.reviews);
                }
            })
            .catch(error => {
                console.error('Error actualizando reseñas:', error);
            });
    }
    
    // Actualizar reseñas de producto
    refreshProductReviews(productId) {
        fetch(`api/reviews.php?action=get_product_reviews&product_id=${productId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.updateProductReviewsDisplay(data.reviews);
                    this.updateProductRating(data.average_rating, data.review_count);
                }
            })
            .catch(error => {
                console.error('Error actualizando reseñas:', error);
            });
    }
    
    // Actualizar display de reseñas del foro
    updateForumReviewsDisplay(reviews) {
        const container = document.querySelector('.reviews-grid');
        if (!container) return;
        
        let html = '';
        reviews.forEach(review => {
            html += `
                <div class="review-card">
                    <div class="review-header">
                        <div class="reviewer-info">
                            <img src="${review.user_avatar || 'assets/images/default-avatar.png'}" alt="Avatar" class="reviewer-avatar">
                            <div>
                                <h4>${review.user_name}</h4>
                                <div class="review-rating">
                                    ${this.generateStars(review.rating)}
                                </div>
                            </div>
                        </div>
                        <span class="review-date">${this.formatDate(review.created_at)}</span>
                    </div>
                    <p class="review-text">${review.comment}</p>
                </div>
            `;
        });
        
        container.innerHTML = html;
    }
    
    // Actualizar display de reseñas de producto
    updateProductReviewsDisplay(reviews) {
        const container = document.querySelector('.product-reviews');
        if (!container) return;
        
        if (reviews.length === 0) {
            container.innerHTML = '<p class="no-reviews">No hay reseñas para este producto aún.</p>';
            return;
        }
        
        let html = '';
        reviews.forEach(review => {
            html += `
                <div class="product-review">
                    <div class="review-header">
                        <div class="reviewer-info">
                            <img src="${review.user_avatar || 'assets/images/default-avatar.png'}" alt="Avatar" class="reviewer-avatar">
                            <div>
                                <h4>${review.user_name}</h4>
                                <div class="review-rating">
                                    ${this.generateStars(review.rating)}
                                </div>
                            </div>
                        </div>
                        <span class="review-date">${this.formatDate(review.created_at)}</span>
                    </div>
                    <p class="review-text">${review.comment}</p>
                </div>
            `;
        });
        
        container.innerHTML = html;
    }
    
    // Actualizar rating del producto
    updateProductRating(averageRating, reviewCount) {
        const ratingElement = document.querySelector('.product-rating');
        if (ratingElement) {
            const stars = ratingElement.querySelector('.stars');
            const count = ratingElement.querySelector('.rating-count');
            
            if (stars) {
                stars.innerHTML = this.generateStars(averageRating);
            }
            
            if (count) {
                count.textContent = `(${reviewCount} reseñas)`;
            }
        }
    }
    
    // Generar estrellas para rating
    generateStars(rating) {
        let stars = '';
        for (let i = 1; i <= 5; i++) {
            const starClass = i <= rating ? 'fas fa-star active' : 'fas fa-star';
            stars += `<i class="${starClass}"></i>`;
        }
        return stars;
    }
    
    // Formatear fecha
    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('es-PE', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }
    
    // Cargar reseñas de un producto
    loadProductReviews(productId, page = 1) {
        fetch(`api/reviews.php?action=get_product_reviews&product_id=${productId}&page=${page}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.updateProductReviewsDisplay(data.reviews);
                    this.updateProductRating(data.average_rating, data.review_count);
                    
                    // Actualizar paginación si existe
                    if (data.pagination) {
                        this.updatePagination(data.pagination);
                    }
                }
            })
            .catch(error => {
                console.error('Error cargando reseñas:', error);
            });
    }
    
    // Actualizar paginación
    updatePagination(pagination) {
        const paginationContainer = document.querySelector('.reviews-pagination');
        if (!paginationContainer) return;
        
        let html = '';
        
        if (pagination.current_page > 1) {
            html += `<a href="#" onclick="reviewSystem.goToPage(${pagination.current_page - 1})" class="page-link">Anterior</a>`;
        }
        
        for (let i = 1; i <= pagination.total_pages; i++) {
            const activeClass = i === pagination.current_page ? 'active' : '';
            html += `<a href="#" onclick="reviewSystem.goToPage(${i})" class="page-link ${activeClass}">${i}</a>`;
        }
        
        if (pagination.current_page < pagination.total_pages) {
            html += `<a href="#" onclick="reviewSystem.goToPage(${pagination.current_page + 1})" class="page-link">Siguiente</a>`;
        }
        
        paginationContainer.innerHTML = html;
    }
    
    // Ir a página específica
    goToPage(page) {
        const productId = this.getCurrentProductId();
        if (productId) {
            this.loadProductReviews(productId, page);
        }
    }
    
    // Obtener ID del producto actual
    getCurrentProductId() {
        // Intentar obtener del formulario o de la URL
        const form = document.getElementById('productReviewForm');
        if (form && form.dataset.productId) {
            return form.dataset.productId;
        }
        
        // Obtener de la URL
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('id');
    }
    
    // Reportar reseña inapropiada
    reportReview(reviewId, reason) {
        fetch('api/reviews.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'report_review',
                review_id: reviewId,
                reason: reason
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Reseña reportada exitosamente', 'success');
            } else {
                showAlert(data.message || 'Error al reportar reseña', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error al reportar reseña', 'error');
        });
    }
    
    // Marcar reseña como útil
    markReviewHelpful(reviewId) {
        fetch('api/reviews.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'mark_helpful',
                review_id: reviewId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Actualizar contador de útiles
                const helpfulButton = document.querySelector(`[data-review-id="${reviewId}"] .btn-helpful`);
                if (helpfulButton) {
                    helpfulButton.textContent = `Útil (${data.helpful_count})`;
                    helpfulButton.classList.add('active');
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }
}

// Inicializar sistema de reseñas
const reviewSystem = new ReviewSystem();

// Funciones globales para compatibilidad
function openReviewModal(type, id = null) {
    reviewSystem.openReviewModal(type, id);
}

function submitForumReview() {
    reviewSystem.submitForumReview();
}

function submitProductReview() {
    reviewSystem.submitProductReview();
}

function loadProductReviews(productId, page = 1) {
    reviewSystem.loadProductReviews(productId, page);
}

function reportReview(reviewId, reason) {
    reviewSystem.reportReview(reviewId, reason);
}

function markReviewHelpful(reviewId) {
    reviewSystem.markReviewHelpful(reviewId);
}

// Inicializar cuando se carga la página
document.addEventListener('DOMContentLoaded', function() {
    // Cargar reseñas del producto si estamos en una página de producto
    const productId = reviewSystem.getCurrentProductId();
    if (productId) {
        reviewSystem.loadProductReviews(productId);
    }
    
    // Agregar estilos para las reseñas
    const style = document.createElement('style');
    style.textContent = `
        .product-reviews {
            margin-top: 2rem;
        }
        
        .product-review {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .reviews-pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }
        
        .page-link {
            padding: 0.5rem 1rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            text-decoration: none;
            color: #666;
            transition: all 0.3s ease;
        }
        
        .page-link:hover,
        .page-link.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .no-reviews {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 2rem;
        }
        
        .btn-helpful {
            background: none;
            border: 1px solid #ddd;
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            cursor: pointer;
            font-size: 0.8rem;
            transition: all 0.3s ease;
        }
        
        .btn-helpful:hover,
        .btn-helpful.active {
            background: #2ecc71;
            color: white;
            border-color: #2ecc71;
        }
        
        .btn-report {
            background: none;
            border: 1px solid #e74c3c;
            color: #e74c3c;
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            cursor: pointer;
            font-size: 0.8rem;
            transition: all 0.3s ease;
        }
        
        .btn-report:hover {
            background: #e74c3c;
            color: white;
        }
    `;
    document.head.appendChild(style);
});
