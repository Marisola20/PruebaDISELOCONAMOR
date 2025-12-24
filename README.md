# DCA Perú - Tienda Online

Una tienda online completa desarrollada con PHP, MySQL, HTML, CSS y JavaScript, diseñada para vender productos con sistema de carrito de compras, pasarela de pago Yape, y panel de administración completo.

## 🚀 Características Principales

### Frontend
- **Diseño Responsive**: Adaptable a todos los dispositivos
- **Interfaz Moderna**: Diseño atractivo con gradientes y animaciones
- **Navegación Intuitiva**: Menú hamburguesa para móviles
- **Botón Flotante WhatsApp**: Contacto directo con clientes

### Sistema de Usuarios
- **Registro y Login**: Sistema completo de autenticación
- **Perfiles de Usuario**: Gestión de información personal
- **Sistema de Favoritos**: Productos favoritos por usuario
- **Historial de Pedidos**: Seguimiento de compras

### Catálogo de Productos
- **Gestión de Productos**: CRUD completo con imágenes
- **Categorías**: Organización por categorías
- **Búsqueda y Filtros**: Búsqueda avanzada de productos
- **Sistema de Reseñas**: Calificaciones y comentarios por producto
- **Foro de Reseñas**: Reseñas generales del sitio

### Carrito de Compras
- **Carrito Persistente**: Guardado en base de datos
- **Gestión de Cantidades**: Ajuste de cantidades en tiempo real
- **Cálculo de Totales**: Cálculo automático de precios
- **Integración Yape**: QR de pago integrado

### Sistema de Pedidos
- **Códigos Únicos**: Generación automática de códigos de pedido
- **Estados de Pedido**: Seguimiento completo del proceso
- **Integración WhatsApp**: Envío de pedidos por WhatsApp
- **Comprobantes de Pago**: Sistema de verificación de pagos

### Promociones
- **Promociones por Fechas**: Activación automática por fechas
- **Descuentos**: Sistema de precios con descuentos
- **Imágenes Promocionales**: Gestión de banners promocionales

### Panel de Administración
- **Dashboard Completo**: Estadísticas en tiempo real
- **Gestión de Productos**: CRUD completo de productos
- **Gestión de Usuarios**: Administración de clientes
- **Gestión de Pedidos**: Seguimiento y actualización de estados
- **Configuración del Sitio**: Personalización de parámetros
- **Gestión de Promociones**: Creación y edición de ofertas

## 🛠️ Tecnologías Utilizadas

- **Backend**: PHP 7.4+
- **Base de Datos**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript ES6+
- **Framework CSS**: CSS Grid, Flexbox
- **Iconos**: Font Awesome 6.0
- **Fuentes**: Google Fonts (Poppins)
- **Servidor Web**: Apache/Nginx (XAMPP recomendado)

## 📋 Requisitos del Sistema

- **PHP**: 7.4 o superior
- **MySQL**: 5.7 o superior
- **Servidor Web**: Apache o Nginx
- **Extensiones PHP**: PDO, PDO_MySQL, GD (para imágenes)
- **Navegador**: Chrome, Firefox, Safari, Edge (versiones modernas)

## 🚀 Instalación

### 1. Preparar el Entorno

#### Opción A: XAMPP (Recomendado para desarrollo)
1. Descargar e instalar [XAMPP](https://www.apachefriends.org/)
2. Iniciar Apache y MySQL desde el panel de control
3. Colocar el proyecto en la carpeta `htdocs`

#### Opción B: Servidor Local
1. Instalar PHP, MySQL y Apache por separado
2. Configurar el servidor web para apuntar al directorio del proyecto

### 2. Configurar la Base de Datos

1. Crear una nueva base de datos llamada `dcaperu_db`
2. El sistema creará automáticamente todas las tablas necesarias
3. Se creará un usuario administrador por defecto:
   - **Usuario**: `admin`
   - **Contraseña**: `admin123`
   - **Email**: `admin@dcaperu.com`

### 3. Configurar el Proyecto

1. **Clonar o descargar** el proyecto en tu servidor web
2. **Configurar permisos** de escritura en las carpetas:
   ```bash
   chmod 755 assets/images/
   chmod 755 assets/uploads/
   ```
3. **Acceder al sitio** desde tu navegador

### 4. Configuración Inicial

1. **Acceder al panel de administración**:
   - URL: `http://localhost/dcaperu/admin/`
   - Usuario: `admin`
   - Contraseña: `admin123`

2. **Configurar parámetros básicos**:
   - Número de WhatsApp
   - Imagen QR de Yape
   - Información de la empresa
   - Redes sociales

3. **Crear categorías** de productos

4. **Agregar productos** al catálogo

5. **Configurar promociones** si es necesario

## 📁 Estructura del Proyecto

```
DCAPeru/
├── admin/                          # Panel de administración
│   ├── index.php                  # Dashboard principal
│   ├── productos.php              # Gestión de productos
│   ├── categorias.php             # Gestión de categorías
│   ├── promociones.php            # Gestión de promociones
│   ├── pedidos.php                # Gestión de pedidos
│   ├── usuarios.php               # Gestión de usuarios
│   ├── reseñas.php                # Gestión de reseñas
│   ├── configuracion.php          # Configuración del sitio
│   └── assets/                    # Recursos del admin
│       ├── css/
│       └── js/
├── assets/                         # Recursos del frontend
│   ├── css/                       # Estilos CSS
│   ├── js/                        # JavaScript
│   └── images/                    # Imágenes del sitio
├── config/                         # Configuración
│   └── database.php               # Conexión a base de datos
├── includes/                       # Funciones del sistema
│   └── functions.php              # Funciones principales
├── api/                           # APIs del sistema
├── index.php                      # Página principal
├── login.php                      # Página de login
├── register.php                   # Página de registro
├── carrito.php                    # Carrito de compras
├── productos.php                  # Catálogo de productos
├── perfil.php                     # Perfil de usuario
├── favoritos.php                  # Productos favoritos
├── checkout.php                   # Finalización de compra
└── README.md                      # Este archivo
```

## 🔧 Configuración de la Base de Datos

### Archivo: `config/database.php`

```php
define('DB_HOST', 'localhost');        // Host de la base de datos
define('DB_NAME', 'dcaperu_db');       // Nombre de la base de datos
define('DB_USER', 'root');             // Usuario de MySQL
define('DB_PASS', '');                 // Contraseña de MySQL
```

### Tablas Principales

- **users**: Usuarios del sistema
- **products**: Productos del catálogo
- **categories**: Categorías de productos
- **orders**: Pedidos de clientes
- **order_items**: Items de cada pedido
- **cart**: Carrito de compras
- **favorites**: Productos favoritos
- **promotions**: Promociones activas
- **reviews**: Reseñas de productos y foro
- **site_config**: Configuración del sitio

## 🎨 Personalización

### Colores del Tema
Los colores principales se pueden modificar en `assets/css/style.css`:

```css
:root {
    --primary-color: #667eea;
    --secondary-color: #764ba2;
    --accent-color: #ffd700;
    --success-color: #2ecc71;
    --danger-color: #e74c3c;
}
```

### Logo y Marca
1. Reemplazar el texto "DCA Perú" en el header
2. Cambiar la imagen del logo en `assets/images/`
3. Actualizar información en la configuración del admin

### Configuración de WhatsApp
1. Ir a Admin → Configuración
2. Actualizar el número de WhatsApp
3. El botón flotante se actualizará automáticamente

### Configuración de Yape
1. Ir a Admin → Configuración
2. Subir la imagen QR de Yape
3. La imagen aparecerá en el carrito y checkout

## 📱 Características Responsive

- **Mobile First**: Diseño optimizado para móviles
- **Breakpoints**: 480px, 768px, 1024px
- **Navegación**: Menú hamburguesa en móviles
- **Imágenes**: Optimizadas para diferentes tamaños de pantalla
- **Formularios**: Adaptados para dispositivos táctiles

## 🔒 Seguridad

- **Contraseñas**: Hash con `password_hash()`
- **SQL Injection**: Prevenido con PDO y prepared statements
- **XSS**: Sanitización de inputs con `htmlspecialchars()`
- **CSRF**: Tokens de seguridad en formularios
- **Sesiones**: Gestión segura de sesiones de usuario

## 📊 Funcionalidades del Admin

### Dashboard
- Estadísticas en tiempo real
- Pedidos recientes
- Productos con bajo stock
- Acciones rápidas

### Gestión de Productos
- CRUD completo
- Subida de imágenes
- Gestión de stock
- Categorización
- Precios y descuentos

### Gestión de Pedidos
- Lista de todos los pedidos
- Cambio de estados
- Detalles completos
- Códigos de seguimiento

### Gestión de Usuarios
- Lista de clientes
- Información de contacto
- Historial de pedidos
- Estado de cuenta

### Configuración del Sitio
- Información de la empresa
- Número de WhatsApp
- QR de Yape
- Redes sociales
- Parámetros del sistema

## 🚀 Despliegue en Producción

### 1. Preparar el Servidor
- Servidor web (Apache/Nginx)
- PHP 7.4+
- MySQL 5.7+
- SSL certificado (recomendado)

### 2. Configurar la Base de Datos
- Crear base de datos en producción
- Importar estructura desde desarrollo
- Configurar usuario con permisos limitados

### 3. Configurar el Proyecto
- Actualizar `config/database.php` con datos de producción
- Configurar dominio en la configuración del admin
- Subir imágenes y recursos

### 4. Optimizaciones
- Habilitar caché del navegador
- Comprimir imágenes
- Minificar CSS y JavaScript
- Configurar CDN para recursos estáticos

## 🐛 Solución de Problemas

### Error de Conexión a Base de Datos
- Verificar credenciales en `config/database.php`
- Asegurar que MySQL esté ejecutándose
- Verificar permisos del usuario de base de datos

### Imágenes No Se Muestran
- Verificar permisos de la carpeta `assets/images/`
- Verificar rutas en el código
- Asegurar que las imágenes se subieron correctamente

### Error 500
- Verificar logs de error de PHP
- Verificar permisos de archivos
- Verificar sintaxis PHP

### Problemas de Sesión
- Verificar configuración de PHP
- Verificar permisos de escritura en `/tmp`
- Limpiar caché del navegador

## 📞 Soporte

Para soporte técnico o consultas:
- **Email**: soporte@dcaperu.com
- **WhatsApp**: +51 999 999 999
- **Documentación**: Consultar este README

## 📄 Licencia

Este proyecto está bajo la Licencia MIT. Ver archivo `LICENSE` para más detalles.

## 🤝 Contribuciones

Las contribuciones son bienvenidas. Por favor:
1. Fork el proyecto
2. Crea una rama para tu feature
3. Commit tus cambios
4. Push a la rama
5. Abre un Pull Request

## 📈 Roadmap

### Versión 2.0
- [ ] Sistema de cupones
- [ ] Múltiples métodos de pago
- [ ] Sistema de afiliados
- [ ] App móvil nativa
- [ ] Integración con redes sociales

### Versión 2.1
- [ ] Sistema de notificaciones push
- [ ] Chat en vivo
- [ ] Sistema de reseñas avanzado
- [ ] Analytics integrado
- [ ] Backup automático

## 🎯 Características Destacadas

✅ **Sistema Completo**: Frontend + Backend + Admin  
✅ **Responsive Design**: Optimizado para todos los dispositivos  
✅ **Carrito de Compras**: Funcionalidad completa  
✅ **Integración Yape**: Pago con QR  
✅ **Panel de Admin**: Gestión completa del sitio  
✅ **Sistema de Usuarios**: Registro y autenticación  
✅ **Gestión de Productos**: CRUD completo  
✅ **Sistema de Reseñas**: Calificaciones y comentarios  
✅ **Promociones**: Ofertas por fechas  
✅ **WhatsApp Integration**: Contacto directo  
✅ **SEO Optimizado**: Meta tags y estructura semántica  

---

**DCA Perú** - Tu tienda online de confianza 🚀

*Desarrollado con ❤️ para el mercado peruano*
