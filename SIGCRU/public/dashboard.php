<?php
require_once '../config/config.php';
require_once '../controllers/DashboardController.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$dashboardController = new DashboardController($conn);

// --- Mueve aquí el procesamiento del formulario ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_avatar'])) {
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $fileTmp = $_FILES['avatar']['tmp_name'];
        $fileName = basename($_FILES['avatar']['name']);
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($fileExt, $allowed)) {
            $newName = 'avatar' . $_SESSION['user_id'] . '_' . time() . '.' . $fileExt;
            $dest = '../src/assets/uploads/' . $newName;
            if (!is_dir('../src/assets/uploads/')) {
                mkdir('../src/assets/uploads/', 0755, true);
            }
            if (move_uploaded_file($fileTmp, $dest)) {
                // Actualizar en la base de datos
                $avatarPath = '../src/assets/uploads/' . $newName;
                $stmt = $conn->prepare("UPDATE estudiantes SET foto = :foto WHERE id_estudiante = :id");
                $stmt->bindParam(':foto', $avatarPath);
                $stmt->bindParam(':id', $_SESSION['user_id']);
                $stmt->execute();
                header("Location: dashboard.php?success=avatar");
                exit;
            } else {
                header("Location: dashboard.php?error=Error+al+subir+el+archivo");
                exit;
            }
        } else {
            header("Location: dashboard.php?error=Formato+de+imagen+no+permitido");
            exit;
        }
    } else {
        header("Location: dashboard.php?error=No+se+seleccionó+ningún+archivo");
        exit;
    }
}
// --- Fin del bloque movido ---

try {
    $estudiante = $dashboardController->getEstudianteInfo($_SESSION['user_id']);

    // Registrar salida si se solicita
    if (isset($_GET['action']) && $_GET['action'] == 'salida') {
        $dashboardController->registrarSalidaBiblioteca($_SESSION['user_id']);
        header("Location: dashboard.php?success=salida");
        exit;
    }

    $asistencia = $dashboardController->getAsistenciaHoy($_SESSION['user_id']);
    $computadoras = $dashboardController->getUsoComputadorasHoy($_SESSION['user_id']);
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biblioteca CRUBA - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        /* Estilos para el mensaje de bienvenida */
        .welcome-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            animation: fadeIn 0.5s ease-out;
        }

        .welcome-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
            width: 90%;
            max-width: 500px;
            overflow: hidden;
            transform: scale(0.8);
            opacity: 0;
            animation: popIn 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.4) forwards 0.3s;
            border: none;
        }

        .welcome-header {
            background: linear-gradient(135deg, #007bff, #3a7bd5);
            color: white;
            padding: 25px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .welcome-header::before {
            content: "";
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.2) 0%, rgba(255, 255, 255, 0) 70%);
            transform: rotate(30deg);
            animation: shine 3s infinite;
        }

        .welcome-body {
            padding: 30px;
            text-align: center;
            background: #f8f9fa;
        }

        .welcome-icon {
            font-size: 5rem;
            color: #007bff;
            margin-bottom: 20px;
            display: inline-block;
            animation: bounce 2s infinite;
        }

        .welcome-name {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 15px;
            color: #343a40;
            position: relative;
            display: inline-block;
        }

        .welcome-name::after {
            content: "";
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 3px;
            background: linear-gradient(90deg, #007bff, #00d4ff);
            border-radius: 3px;
        }

        .welcome-text {
            font-size: 1.2rem;
            color: #6c757d;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .welcome-button {
            background: linear-gradient(135deg, #007bff, #00a8ff);
            border: none;
            padding: 12px 30px;
            border-radius: 50px;
            color: white;
            font-weight: bold;
            font-size: 1.1rem;
            box-shadow: 0 5px 20px rgba(0, 120, 255, 0.4);
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .welcome-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 120, 255, 0.5);
        }

        .welcome-button::after {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.3), rgba(255, 255, 255, 0));
            opacity: 0;
            transition: opacity 0.3s;
        }

        .welcome-button:hover::after {
            opacity: 1;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes popIn {
            0% {
                transform: scale(0.8);
                opacity: 0;
            }

            60% {
                transform: scale(1.05);
                opacity: 1;
            }

            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        @keyframes bounce {

            0%,
            20%,
            50%,
            80%,
            100% {
                transform: translateY(0);
            }

            40% {
                transform: translateY(-20px);
            }

            60% {
                transform: translateY(-10px);
            }
        }

        @keyframes shine {
            0% {
                transform: translateX(-100%) rotate(30deg);
            }

            20% {
                transform: translateX(100%) rotate(30deg);
            }

            100% {
                transform: translateX(100%) rotate(30deg);
            }
        }

        /* Estilos para la foto de perfil */
        .profile-img {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border: 3px solid #0d6efd;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        /* Efecto hover para botones */
        .btn-hover-effect {
            transition: all 0.3s ease;
        }

        .btn-hover-effect:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body>
    <?php if (!isset($_SESSION['welcome_shown'])): ?>
        <div class="welcome-overlay">
            <div class="welcome-card">
                <div class="welcome-header">
                    <h2><i class="bi bi-book-half me-2"></i> Biblioteca CRUBA</h2>
                </div>
                <div class="welcome-body">
                    <div class="welcome-icon">
                        <i class="bi bi-emoji-smile"></i>
                    </div>
                    <div class="welcome-name">¡Hola, <?php echo htmlspecialchars($estudiante['nombre']); ?>!</div>
                    <div class="welcome-text">
                        Bienvenido al sistema de gestión de la biblioteca.<br>
                        Ahora puedes acceder a todos nuestros recursos académicos, reservar computadoras y gestionar tus
                        préstamos.
                    </div>
                    <button class="welcome-button" id="closeWelcome">
                        Comenzar <i class="bi bi-arrow-right ms-2"></i>
                    </button>
                </div>
            </div>
        </div>
        <?php $_SESSION['welcome_shown'] = true; ?>
        <script>
            // Cerrar al hacer clic en el botón
            document.getElementById('closeWelcome').addEventListener('click', function() {
                const overlay = document.querySelector('.welcome-overlay');
                overlay.style.animation = 'fadeIn 0.5s reverse forwards';
                setTimeout(() => {
                    overlay.remove();
                }, 500);
            });

            // Cerrar automáticamente después de 5 segundos
            setTimeout(() => {
                const overlay = document.querySelector('.welcome-overlay');
                if (overlay) {
                    overlay.style.animation = 'fadeIn 0.5s reverse forwards';
                    setTimeout(() => overlay.remove(), 500);
                }
            }, 5000);
        </script>

    <?php endif; ?>

    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">Biblioteca CRUBA</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
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
                        <a class="nav-link" href="solicitudes.php"><i class="bi bi-journal-text"></i> Mis
                            solicitudes</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="./logout.php"><i class="bi bi-box-arrow-right"></i> Cerrar sesión</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-4">

                <?php
                // Procesar cambio de avatar si se envió el formulario
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_avatar'])) {
                    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                        $fileTmp = $_FILES['avatar']['tmp_name'];
                        $fileName = basename($_FILES['avatar']['name']);
                        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                        if (in_array($fileExt, $allowed)) {
                            $newName = 'avatar_' . $SESSION['user_id'] . '' . time() . '.' . $fileExt;
                            $dest = '../src/assets/uploads/' . $newName;
                            if (!is_dir('../src/assets/uploads/')) {
                                mkdir('../src/assets/uploads/', 0777, true);
                            }
                            if (move_uploaded_file($fileTmp, $dest)) {
                                // Actualizar en la base de datos
                                $avatarPath = '../src/assets/uploads/' . $newName;
                                $stmt = $conn->prepare("UPDATE estudiantes SET foto = :foto WHERE id_estudiante = :id");
                                $stmt->bindParam(':foto', $avatarPath);
                                $stmt->bindParam(':id', $_SESSION['user_id']);
                                $stmt->execute();
                                header("Location: dashboard.php?success=avatar");
                                exit;
                            } else {
                                header("Location: dashboard.php?error=Error+al+subir+el+archivo");
                                exit;
                            }
                        } else {
                            header("Location: dashboard.php?error=Formato+de+imagen+no+permitido");
                            exit;
                        }
                    } else {
                        header("Location: dashboard.php?error=No+se+seleccionó+ningún+archivo");
                        exit;
                    }
                }
                ?>
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">Información del Estudiante</h5>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <img src="<?php echo htmlspecialchars($estudiante['foto']); ?>"
                                class="rounded-circle w-50 mb-2"
                                style="max-width: 150px; max-height: 150px; object-fit: cover;" alt="Foto perfil">
                        </div>
                        <h5 class="card-title"><?php echo htmlspecialchars($estudiante['nombre']); ?></h5>
                        <p class="card-text">
                            <strong>Cédula:</strong> <?php echo htmlspecialchars($estudiante['cedula']); ?><br>
                            <strong>Facultad:</strong> <?php echo htmlspecialchars($estudiante['facultad']); ?><br>
                            <strong>Escuela:</strong> <?php echo htmlspecialchars($estudiante['escuela']); ?>
                        </p>
                        <?php
                        // Mostrar mensaje de éxito si se cambió la foto
                        if (isset($_GET['success']) && $_GET['success'] == 'avatar') {
                            echo '<div class="alert alert-success py-1">Avatar actualizado correctamente.</div>';
                        }
                        // Mostrar errores si existen
                        if (isset($_GET['error'])) {
                            echo '<div class="alert alert-danger py-1">' . htmlspecialchars($_GET['error']) . '</div>';
                        }
                        ?>
                        <form action="dashboard.php" method="post" enctype="multipart/form-data">
                            <input type="file" name="avatar" accept="image/*" class="form-control mb-2" required>
                            <button type="submit" name="cambiar_avatar" class="btn btn-outline-secondary w-100 mb-2"
                                style="font-weight:bold;">
                                <i class="bi bi-person-circle"></i> Cambiar avatar
                            </button>
                        </form>
                    </div>
                </div>


                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">Acciones rápidas</h5>
                    </div>
                    <div class="card-body">
                        <a href="libros.php" class="btn btn-outline-primary w-100 mb-2">
                            <i class="bi bi-book"></i> Buscar libros
                        </a>
                        <a href="solicitudes.php" class="btn btn-outline-primary w-100 mb-2">
                            <i class="bi bi-journal-text"></i> Mis solicitudes
                        </a>
                        <a href="computadoras.php" class="btn btn-outline-primary w-100 mb-2">
                            <i class="bi bi-pc"></i> Usar computadora
                        </a>
                        <a href="dashboard.php?action=salida" class="btn btn-danger w-100">
                            <i class="bi bi-box-arrow-left"></i> Registrar salida
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">Mi actividad hoy</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($asistencia): ?>
                            <p><strong>Hora de entrada:</strong>
                                <?php echo htmlspecialchars($asistencia['hora_entrada']); ?></p>
                            <?php if ($asistencia['hora_salida']): ?>
                                <p><strong>Hora de salida:</strong> <?php echo htmlspecialchars($asistencia['hora_salida']); ?>
                                </p>
                            <?php else: ?>
                                <p class='text-success'><strong>Actualmente en la biblioteca</strong></p>
                            <?php endif; ?>
                        <?php else: ?>
                            <p>No has registrado entrada hoy.</p>
                        <?php endif; ?>

                        <?php if (count($computadoras) > 0): ?>
                            <h6 class='mt-4'>Uso de computadoras:</h6>
                            <?php foreach ($computadoras as $comp): ?>
                                <p>
                                    <strong>Computadora #<?php echo htmlspecialchars($comp['computadora_id']); ?></strong><br>
                                    Inicio: <?php echo htmlspecialchars($comp['hora_inicio']); ?><br>
                                    <?php if ($comp['hora_fin']): ?>
                                        Fin: <?php echo htmlspecialchars($comp['hora_fin']); ?>
                                    <?php else: ?>
                                        <span class='text-success'>En uso actualmente</span>
                                    <?php endif; ?>
                                </p>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">Mis solicitudes recientes</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        // Obtener las últimas 3 solicitudes del estudiante
                        try {
                            $stmt = $conn->prepare("SELECT s.*, l.titulo 
                                                  FROM solicitudes_libros s
                                                  JOIN libros l ON s.id_libro = l.id_libro
                                                  WHERE s.id_estudiante = :id_estudiante
                                                  ORDER BY s.fecha_solicitud DESC
                                                  LIMIT 3");
                            $stmt->bindParam(':id_estudiante', $_SESSION['user_id']);
                            $stmt->execute();
                            $solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            if (count($solicitudes) > 0) {
                                foreach ($solicitudes as $solicitud) {
                                    echo "<div class='mb-3 p-2 border rounded'>";
                                    echo "<h6>" . htmlspecialchars($solicitud['titulo']) . "</h6>";
                                    echo "<p><strong>Estado:</strong> " . htmlspecialchars($solicitud['estado']) . "<br>";
                                    echo "<strong>Fecha solicitud:</strong> " . htmlspecialchars($solicitud['fecha_solicitud']) . "</p>";
                                    echo "</div>";
                                }
                                echo "<a href='solicitudes.php' class='btn btn-primary mt-2'>Ver todas</a>";
                            } else {
                                echo "<p>No tienes solicitudes recientes.</p>";
                                echo "<a href='libros.php' class='btn btn-primary'>Buscar libros</a>";
                            }
                        } catch (PDOException $e) {
                            die("Error: " . $e->getMessage());
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>