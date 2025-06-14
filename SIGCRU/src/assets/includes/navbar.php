<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container">
        <?php if(isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'administrador'): ?>
        <!-- Vista para administradores - Estilo mejorado -->
        <span class="navbar-brand fw-bold d-flex align-items-center">
            <i class="bi bi-book-half me-2"></i>
            <span>Biblioteca CRUBA</span>
        </span>

        <div class="d-flex align-items-center">
            <ul class="navbar-nav flex-row gap-3">
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center py-2" href="./admin.php">
                        <i class="bi bi-speedometer2 me-2"></i>
                        <span>Admin</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center py-2" href="./admin_prestamos.php">
                        <i class="bi bi-journal-text me-2"></i>
                        <span>Panel</span>
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center py-2" href="#" role="button"
                        data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle me-2"></i>
                        <span><?= htmlspecialchars($_SESSION['user_name']) ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i> Cerrar
                                sesión</a></li>
                    </ul>
                </li>
            </ul>
        </div>


        <?php
            // Obtener datos del estudiante si está logueado
            $foto_estudiante = '';
            if(isset($_SESSION['user_id'])) {
                // Conexión a la base de datos (ajusta según tu configuración)
                require_once 'conexion.php'; // Archivo con la conexión
                
                $stmt = $pdo->prepare("SELECT foto FROM estudiantes WHERE id_estudiante = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $estudiante = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if($estudiante && !empty($estudiante['foto'])) {
                    $foto_estudiante = $estudiante['foto'];
                }
            }
            ?>

        <?php else: ?>
        <!-- Vista para estudiantes/no logueados - Estilo mejorado -->
        <a class="navbar-brand fw-bold d-flex align-items-center" href="dashboard.php">
            <i class="bi bi-book-half me-2"></i>
            <span>Biblioteca CRUBA</span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
            aria-expanded="false">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center py-2 px-3 rounded" href="dashboard.php">
                        <i class="bi bi-house-door me-2"></i>
                        <span>Inicio</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center py-2 px-3 rounded" href="libros.php">
                        <i class="bi bi-book me-2"></i>
                        <span>Libros</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center py-2 px-3 rounded" href="computadoras.php">
                        <i class="bi bi-pc me-2"></i>
                        <span>Computadoras</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center py-2 px-3 rounded" href="solicitudes.php">
                        <i class="bi bi-journal-text me-2"></i>
                        <span>Mis solicitudes</span>
                    </a>
                </li>
            </ul>

            <?php if(isset($_SESSION['user_name'])): ?>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button"
                        data-bs-toggle="dropdown">
                        <?php if(!empty($foto_estudiante)): ?>
                        <img src="<?php echo htmlspecialchars($foto_estudiante); ?>" class="rounded-circle me-2"
                            width="30" height="30" alt="Foto de perfil" style="object-fit: cover;"
                            onerror="this.onerror=null; this.src='ruta/imagen_por_defecto.jpg'; this.className='bi bi-person-circle me-2'">
                        <?php else: ?>
                        <i class="bi bi-person-circle me-2"></i>
                        <?php endif; ?>

                        <span><?= htmlspecialchars($_SESSION['user_name']) ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="perfil.php"><i class="bi bi-person me-2"></i> Mi perfil</a>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item text-danger" href="logout.php"><i
                                    class="bi bi-box-arrow-right me-2"></i> Cerrar sesión</a></li>
                    </ul>
                </li>
            </ul>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</nav>

<style>
/* Estilos personalizados */
.navbar {
    padding: 0.5rem 0;
}

.nav-link {
    transition: all 0.3s ease;
    font-weight: 500;
}

.nav-link:hover,
.nav-link:focus {
    background-color: rgba(255, 255, 255, 0.15);
    transform: translateY(-2px);
}

.dropdown-menu {
    border: none;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.dropdown-item {
    padding: 0.5rem 1.5rem;
}

@media (max-width: 991.98px) {
    .navbar-collapse {
        padding: 1rem;
        background-color: var(--bs-primary);
        margin-top: 0.5rem;
        border-radius: 0.25rem;
    }

    .nav-item {
        margin-bottom: 0.5rem;
    }
}
</style>