// JavaScript para el Panel de Administración

document.addEventListener('DOMContentLoaded', function() {
    initAdminPanel();
});

function initAdminPanel() {
    initSidebar();
    initCharts();
    initNotifications();
    initConfirmations();
    initFormValidation();
}

// Inicializar sidebar
function initSidebar() {
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.sidebar-overlay');
    
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
            if (overlay) {
                overlay.classList.toggle('active');
            }
        });
    }
    
    if (overlay) {
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
        });
    }
    
    // Marcar enlace activo en el sidebar
    const currentPath = window.location.pathname;
    const sidebarLinks = document.querySelectorAll('.sidebar-nav a');
    
    sidebarLinks.forEach(link => {
        if (link.getAttribute('href') === currentPath.split('/').pop()) {
            link.parentElement.classList.add('active');
        }
    });
}

// Inicializar gráficos (si se usan)
function initCharts() {
    // Aquí se pueden inicializar gráficos con Chart.js u otra librería
    // Por ahora solo un placeholder
    console.log('Charts initialized');
}

// Inicializar notificaciones
function initNotifications() {
    // Mostrar notificaciones automáticamente
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.remove();
            }, 300);
        }, 5000);
    });
}

// Inicializar confirmaciones
function initConfirmations() {
    const deleteButtons = document.querySelectorAll('.btn-delete, .btn-remove');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('¿Estás seguro de que quieres realizar esta acción?')) {
                e.preventDefault();
                return false;
            }
        });
    });
}

// Inicializar validación de formularios
function initFormValidation() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
                return false;
            }
        });
    });
}

// Validar formulario
function validateForm(form) {
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            showFieldError(field, 'Este campo es obligatorio');
            isValid = false;
        } else {
            clearFieldError(field);
        }
    });
    
    // Validaciones específicas
    const emailFields = form.querySelectorAll('input[type="email"]');
    emailFields.forEach(field => {
        if (field.value && !isValidEmail(field.value)) {
            showFieldError(field, 'Email no válido');
            isValid = false;
        }
    });
    
    const numberFields = form.querySelectorAll('input[type="number"]');
    numberFields.forEach(field => {
        if (field.value && field.value < 0) {
            showFieldError(field, 'El valor debe ser mayor o igual a 0');
            isValid = false;
        }
    });
    
    return isValid;
}

// Mostrar error en campo
function showFieldError(field, message) {
    clearFieldError(field);
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error';
    errorDiv.textContent = message;
    errorDiv.style.color = '#e74c3c';
    errorDiv.style.fontSize = '0.8rem';
    errorDiv.style.marginTop = '0.3rem';
    
    field.parentNode.appendChild(errorDiv);
    field.style.borderColor = '#e74c3c';
}

// Limpiar error en campo
function clearFieldError(field) {
    const existingError = field.parentNode.querySelector('.field-error');
    if (existingError) {
        existingError.remove();
    }
    field.style.borderColor = '#e1e5e9';
}

// Validar email
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Función para mostrar notificaciones
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type}`;
    notification.textContent = message;
    
    // Insertar al inicio del contenido principal
    const mainContent = document.querySelector('.main-content');
    if (mainContent) {
        mainContent.insertBefore(notification, mainContent.firstChild);
        
        // Remover después de 5 segundos
        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 5000);
    }
}

// Función para confirmar acciones
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// Función para eliminar elemento
function deleteItem(itemId, itemType) {
    confirmAction(`¿Estás seguro de que quieres eliminar este ${itemType}?`, function() {
        // Crear formulario para eliminar
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="${itemId}">
        `;
        document.body.appendChild(form);
        form.submit();
    });
}

// Función para cambiar estado
function changeStatus(itemId, newStatus, itemType) {
    confirmAction(`¿Estás seguro de que quieres cambiar el estado de este ${itemType}?`, function() {
        // Crear formulario para cambiar estado
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="change_status">
            <input type="hidden" name="id" value="${itemId}">
            <input type="hidden" name="status" value="${newStatus}">
        `;
        document.body.appendChild(form);
        form.submit();
    });
}

// Función para buscar
function searchItems(query) {
    if (query.trim() === '') {
        // Si la búsqueda está vacía, mostrar todos los elementos
        window.location.href = window.location.pathname;
        return;
    }
    
    const currentUrl = new URL(window.location);
    currentUrl.searchParams.set('search', query);
    window.location.href = currentUrl.toString();
}

// Función para filtrar
function filterItems(filterType, value) {
    const currentUrl = new URL(window.location);
    currentUrl.searchParams.set(filterType, value);
    window.location.href = currentUrl.toString();
}

// Función para ordenar
function sortItems(sortBy) {
    const currentUrl = new URL(window.location);
    currentUrl.searchParams.set('sort', sortBy);
    window.location.href = currentUrl.toString();
}

// Función para paginación
function goToPage(page) {
    const currentUrl = new URL(window.location);
    currentUrl.searchParams.set('page', page);
    window.location.href = currentUrl.toString();
}

// Función para seleccionar todos los elementos
function selectAll() {
    const checkboxes = document.querySelectorAll('.item-checkbox');
    const selectAllCheckbox = document.querySelector('#selectAll');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAllCheckbox.checked;
    });
    
    updateBulkActions();
}

// Función para actualizar acciones en lote
function updateBulkActions() {
    const checkboxes = document.querySelectorAll('.item-checkbox:checked');
    const bulkActions = document.querySelector('.bulk-actions');
    
    if (bulkActions) {
        if (checkboxes.length > 0) {
            bulkActions.style.display = 'flex';
            bulkActions.querySelector('.selected-count').textContent = checkboxes.length;
        } else {
            bulkActions.style.display = 'none';
        }
    }
}

// Función para acciones en lote
function bulkAction(action) {
    const checkboxes = document.querySelectorAll('.item-checkbox:checked');
    const itemIds = Array.from(checkboxes).map(cb => cb.value);
    
    if (itemIds.length === 0) {
        showNotification('No hay elementos seleccionados', 'warning');
        return;
    }
    
    let message = '';
    switch(action) {
        case 'delete':
            message = `¿Estás seguro de que quieres eliminar ${itemIds.length} elementos?`;
            break;
        case 'activate':
            message = `¿Estás seguro de que quieres activar ${itemIds.length} elementos?`;
            break;
        case 'deactivate':
            message = `¿Estás seguro de que quieres desactivar ${itemIds.length} elementos?`;
            break;
        default:
            message = `¿Estás seguro de que quieres realizar esta acción en ${itemIds.length} elementos?`;
    }
    
    confirmAction(message, function() {
        // Crear formulario para acción en lote
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="bulk_${action}">
            <input type="hidden" name="ids" value="${itemIds.join(',')}">
        `;
        document.body.appendChild(form);
        form.submit();
    });
}

// Función para subir imagen
function uploadImage(input, previewId) {
    const file = input.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById(previewId);
            if (preview) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
        };
        reader.readAsDataURL(file);
    }
}

// Función para previsualizar imagen
function previewImage(input, previewId) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById(previewId);
            if (preview) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Función para remover imagen
function removeImage(previewId, inputId) {
    const preview = document.getElementById(previewId);
    const input = document.getElementById(inputId);
    
    if (preview) {
        preview.src = '';
        preview.style.display = 'none';
    }
    
    if (input) {
        input.value = '';
    }
}

// Función para mostrar modal
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
}

// Función para ocultar modal
function hideModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

// Función para exportar datos
function exportData(format) {
    const currentUrl = new URL(window.location);
    currentUrl.searchParams.set('export', format);
    window.location.href = currentUrl.toString();
}

// Función para imprimir
function printPage() {
    window.print();
}

// Función para refrescar página
function refreshPage() {
    window.location.reload();
}

// Función para ir atrás
function goBack() {
    window.history.back();
}

// Función para ir adelante
function goForward() {
    window.history.forward();
}

// Función para copiar al portapapeles
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        showNotification('Copiado al portapapeles', 'success');
    }).catch(function() {
        showNotification('Error al copiar', 'error');
    });
}

// Función para descargar archivo
function downloadFile(url, filename) {
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Función para formatear fecha
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('es-PE', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Función para formatear precio
function formatPrice(price) {
    return new Intl.NumberFormat('es-PE', {
        style: 'currency',
        currency: 'PEN'
    }).format(price);
}

// Función para truncar texto
function truncateText(text, maxLength) {
    if (text.length <= maxLength) return text;
    return text.substr(0, maxLength) + '...';
}

// Función para debounce
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Función para throttle
function throttle(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// Event listeners para elementos dinámicos
document.addEventListener('click', function(e) {
    // Manejar clicks en botones de eliminar
    if (e.target.classList.contains('btn-delete') || e.target.closest('.btn-delete')) {
        const button = e.target.classList.contains('btn-delete') ? e.target : e.target.closest('.btn-delete');
        const itemId = button.dataset.id;
        const itemType = button.dataset.type || 'elemento';
        deleteItem(itemId, itemType);
    }
    
    // Manejar clicks en botones de cambiar estado
    if (e.target.classList.contains('btn-status') || e.target.closest('.btn-status')) {
        const button = e.target.classList.contains('btn-status') ? e.target : e.target.closest('.btn-status');
        const itemId = button.dataset.id;
        const newStatus = button.dataset.status;
        const itemType = button.dataset.type || 'elemento';
        changeStatus(itemId, newStatus, itemType);
    }
    
    // Manejar clicks en checkboxes
    if (e.target.classList.contains('item-checkbox')) {
        updateBulkActions();
    }
});

// Event listeners para formularios
document.addEventListener('submit', function(e) {
    if (e.target.classList.contains('search-form')) {
        e.preventDefault();
        const query = e.target.querySelector('input[name="search"]').value;
        searchItems(query);
    }
});

// Event listeners para inputs
document.addEventListener('input', function(e) {
    if (e.target.classList.contains('search-input')) {
        const debouncedSearch = debounce(function(query) {
            searchItems(query);
        }, 500);
        debouncedSearch(e.target.value);
    }
});

// Event listeners para selects
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('filter-select')) {
        const filterType = e.target.dataset.filter;
        const value = e.target.value;
        filterItems(filterType, value);
    }
    
    if (e.target.classList.contains('sort-select')) {
        const sortBy = e.target.value;
        sortItems(sortBy);
    }
});

// Inicializar tooltips si existen
if (typeof tippy !== 'undefined') {
    tippy('[data-tippy-content]', {
        placement: 'top',
        arrow: true,
        animation: 'scale'
    });
}

// Inicializar select2 si existe
if (typeof $ !== 'undefined' && $.fn.select2) {
    $('.select2').select2({
        theme: 'bootstrap-5',
        width: '100%'
    });
}

// Inicializar datepicker si existe
if (typeof $ !== 'undefined' && $.fn.datepicker) {
    $('.datepicker').datepicker({
        format: 'dd/mm/yyyy',
        autoclose: true,
        todayHighlight: true,
        language: 'es'
    });
}

// Inicializar datatable si existe
if (typeof $ !== 'undefined' && $.fn.DataTable) {
    $('.datatable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json'
        },
        responsive: true,
        pageLength: 25
    });
}
