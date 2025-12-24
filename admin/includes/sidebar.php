<aside class="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <i class="fas fa-store"></i>
            <span>DCA Perú</span>
        </div>
        <button class="sidebar-toggle" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
    </div>
    
    <nav class="sidebar-nav">
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="index.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="productos.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'productos.php' ? 'active' : ''; ?>">
                    <i class="fas fa-box"></i>
                    <span>Productos</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="categorias.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'categorias.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tags"></i>
                    <span>Categorías</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="promociones.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'promociones.php' ? 'active' : ''; ?>">
                    <i class="fas fa-percentage"></i>
                    <span>Promociones</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="pedidos.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'pedidos.php' ? 'active' : ''; ?>">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Pedidos</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="usuarios.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'usuarios.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    <span>Usuarios</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="reseñas.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reseñas.php' ? 'active' : ''; ?>">
                    <i class="fas fa-star"></i>
                    <span>Reseñas</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="configuracion.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'configuracion.php' ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i>
                    <span>Configuración</span>
                </a>
            </li>
        </ul>
    </nav>
    
    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar">
                <i class="fas fa-user"></i>
            </div>
            <div class="user-details">
                <span class="user-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?></span>
                <span class="user-role">Administrador</span>
            </div>
        </div>
        
        <div class="sidebar-actions">
            <a href="../index.php" class="btn btn-sm btn-secondary" title="Ver Sitio">
                <i class="fas fa-external-link-alt"></i>
            </a>
            <a href="../logout.php" class="btn btn-sm btn-danger" title="Cerrar Sesión">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </div>
</aside>

<div class="sidebar-overlay" onclick="toggleSidebar()"></div>
