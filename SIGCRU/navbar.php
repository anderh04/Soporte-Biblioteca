<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">Biblioteca CRUBA</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php"><i class="bi bi-house-door"></i> Inicio</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="libros.php"><i class="bi bi-book"></i> Libros</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="computadoras.php"><i class="bi bi-pc"></i> Computadoras</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="solicitudes.php"><i class="bi bi-journal-text"></i> Mis solicitudes</a>
                </li>
                <?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] == 1): ?>
                <li class="nav-item">
                    <a class="nav-link" href="admin.php"><i class="bi bi-speedometer2"></i> Admin</a>
                </li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav ms-auto">
                <?php if(isset($_SESSION['user_name'])): ?>
                <li class="nav-item">
                    <span class="nav-link"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right"></i> Cerrar sesión</a>
                </li>
            </ul>
        </div>
    </div>
</nav>