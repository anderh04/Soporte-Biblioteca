<?php
require_once 'config.php';
session_start();

if(!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Obtener información del estudiante
try {
    $stmt = $conn->prepare("SELECT * FROM estudiantes WHERE id_estudiante = :id");
    $stmt->bindParam(':id', $_SESSION['user_id']);
    $stmt->execute();
    $estudiante = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Registrar salida si se solicita
if(isset($_GET['action']) && $_GET['action'] == 'salida') {
    registrarSalidaBiblioteca($_SESSION['user_id'], $conn);
}

function registrarSalidaBiblioteca($id_estudiante, $conn) {
    try {
        $fecha_actual = date('Y-m-d');
        $hora_actual = date('H:i:s');
        
        $stmt = $conn->prepare("UPDATE asistencia_biblioteca 
                               SET hora_salida = :hora_salida 
                               WHERE id_estudiante = :id_estudiante 
                               AND fecha = :fecha 
                               AND hora_salida IS NULL");
        $stmt->bindParam(':hora_salida', $hora_actual);
        $stmt->bindParam(':id_estudiante', $id_estudiante);
        $stmt->bindParam(':fecha', $fecha_actual);
        $stmt->execute();
        
        // También registrar salida de computadoras si está usando una
        $stmt = $conn->prepare("UPDATE uso_computadoras 
                               SET hora_fin = :hora_fin 
                               WHERE id_estudiante = :id_estudiante 
                               AND fecha = :fecha 
                               AND hora_fin IS NULL");
        $stmt->bindParam(':hora_fin', $hora_actual);
        $stmt->bindParam(':id_estudiante', $id_estudiante);
        $stmt->bindParam(':fecha', $fecha_actual);
        $stmt->execute();
        
        header("Location: dashboard.php?success=salida");
        exit;
    } catch(PDOException $e) {
        die("Error al registrar salida: " . $e->getMessage());
    }
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
</head>

<body>
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
                        <a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right"></i> Cerrar sesión</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">Información del Estudiante</h5>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <img src="https://via.placeholder.com/150" class="rounded-circle" alt="Foto perfil">
                        </div>
                        <h5 class="card-title"><?php echo htmlspecialchars($estudiante['nombre']); ?></h5>
                        <p class="card-text">
                            <strong>Cédula:</strong> <?php echo htmlspecialchars($estudiante['cedula']); ?><br>
                            <strong>Facultad:</strong> <?php echo htmlspecialchars($estudiante['facultad']); ?><br>
                            <strong>Escuela:</strong> <?php echo htmlspecialchars($estudiante['escuela']); ?>
                        </p>
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
                        <?php
                        // Obtener registro de asistencia de hoy
                        try {
                            $fecha_actual = date('Y-m-d');
                            $stmt = $conn->prepare("SELECT * FROM asistencia_biblioteca 
                                                  WHERE id_estudiante = :id_estudiante 
                                                  AND fecha = :fecha");
                            $stmt->bindParam(':id_estudiante', $_SESSION['user_id']);
                            $stmt->bindParam(':fecha', $fecha_actual);
                            $stmt->execute();
                            $asistencia = $stmt->fetch(PDO::FETCH_ASSOC);
                            
                            if($asistencia) {
                                echo "<p><strong>Hora de entrada:</strong> " . htmlspecialchars($asistencia['hora_entrada']) . "</p>";
                                if($asistencia['hora_salida']) {
                                    echo "<p><strong>Hora de salida:</strong> " . htmlspecialchars($asistencia['hora_salida']) . "</p>";
                                } else {
                                    echo "<p class='text-success'><strong>Actualmente en la biblioteca</strong></p>";
                                }
                            } else {
                                echo "<p>No has registrado entrada hoy.</p>";
                            }
                        } catch(PDOException $e) {
                            die("Error: " . $e->getMessage());
                        }
                        
                        // Obtener uso de computadoras de hoy
                        try {
                            $stmt = $conn->prepare("SELECT * FROM uso_computadoras 
                                                  WHERE id_estudiante = :id_estudiante 
                                                  AND fecha = :fecha");
                            $stmt->bindParam(':id_estudiante', $_SESSION['user_id']);
                            $stmt->bindParam(':fecha', $fecha_actual);
                            $stmt->execute();
                            $computadoras = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            if(count($computadoras) > 0) {
                                echo "<h6 class='mt-4'>Uso de computadoras:</h6>";
                                foreach($computadoras as $comp) {
                                    echo "<p><strong>Computadora #" . htmlspecialchars($comp['computadora_id']) . "</strong><br>";
                                    echo "Inicio: " . htmlspecialchars($comp['hora_inicio']) . "<br>";
                                    if($comp['hora_fin']) {
                                        echo "Fin: " . htmlspecialchars($comp['hora_fin']) . "</p>";
                                    } else {
                                        echo "<span class='text-success'>En uso actualmente</span></p>";
                                    }
                                }
                            }
                        } catch(PDOException $e) {
                            die("Error: " . $e->getMessage());
                        }
                        ?>
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
                            
                            if(count($solicitudes) > 0) {
                                foreach($solicitudes as $solicitud) {
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
                        } catch(PDOException $e) {
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