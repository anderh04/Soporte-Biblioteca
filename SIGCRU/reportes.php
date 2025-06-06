<?php
require_once 'config.php';
session_start();

// Verificar si es administrador
if(!isset($_SESSION['user_id']) || $_SESSION['user_id'] != 1) {
    header("Location: index.php");
    exit;
}

// Procesar filtros de fechas
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : date('Y-m-01');
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : date('Y-m-d');

// Obtener estadísticas filtradas
try {
    // Asistencia por día
    $stmt = $conn->prepare("SELECT fecha, COUNT(*) as cantidad 
                           FROM asistencia_biblioteca 
                           WHERE fecha BETWEEN :fecha_inicio AND :fecha_fin
                           GROUP BY fecha
                           ORDER BY fecha");
    $stmt->bindParam(':fecha_inicio', $fecha_inicio);
    $stmt->bindParam(':fecha_fin', $fecha_fin);
    $stmt->execute();
    $asistencia_diaria = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Top facultades
    $stmt = $conn->prepare("SELECT e.facultad, COUNT(*) as cantidad 
                           FROM asistencia_biblioteca a
                           JOIN estudiantes e ON a.id_estudiante = e.id_estudiante
                           WHERE a.fecha BETWEEN :fecha_inicio AND :fecha_fin
                           GROUP BY e.facultad 
                           ORDER BY cantidad DESC 
                           LIMIT 5");
    $stmt->bindParam(':fecha_inicio', $fecha_inicio);
    $stmt->bindParam(':fecha_fin', $fecha_fin);
    $stmt->execute();
    $top_facultades = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Uso de computadoras
    $stmt = $conn->prepare("SELECT COUNT(*) as sesiones, 
                           AVG(TIMESTAMPDIFF(MINUTE, hora_inicio, hora_fin)) as promedio_minutos
                           FROM uso_computadoras
                           WHERE fecha BETWEEN :fecha_inicio AND :fecha_fin
                           AND hora_fin IS NOT NULL");
    $stmt->bindParam(':fecha_inicio', $fecha_inicio);
    $stmt->bindParam(':fecha_fin', $fecha_fin);
    $stmt->execute();
    $uso_computadoras = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Libros más solicitados
    $stmt = $conn->prepare("SELECT l.titulo, COUNT(*) as cantidad 
                           FROM solicitudes_libros s
                           JOIN libros l ON s.id_libro = l.id_libro
                           WHERE s.fecha_solicitud BETWEEN :fecha_inicio AND :fecha_fin
                           GROUP BY l.titulo 
                           ORDER BY cantidad DESC 
                           LIMIT 5");
    $stmt->bindParam(':fecha_inicio', $fecha_inicio);
    $stmt->bindParam(':fecha_fin', $fecha_fin);
    $stmt->execute();
    $top_libros = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biblioteca CRUBA - Reportes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <h2 class="mb-4">Reportes Estadísticos</h2>

        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Filtros</h5>
            </div>
            <div class="card-body">
                <form method="get" action="reportes.php">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="fecha_inicio" class="form-label">Fecha inicio</label>
                                <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio"
                                    value="<?php echo htmlspecialchars($fecha_inicio); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="fecha_fin" class="form-label">Fecha fin</label>
                                <input type="date" class="form-control" id="fecha_fin" name="fecha_fin"
                                    value="<?php echo htmlspecialchars($fecha_fin); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">Aplicar filtros</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Asistencia diaria</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="asistenciaChart" height="300"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Top facultades</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="facultadChart" height="300"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Uso de computadoras</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card bg-light mb-3">
                                    <div class="card-body text-center">
                                        <h5 class="card-title">Sesiones</h5>
                                        <p class="card-text display-4"><?php echo $uso_computadoras['sesiones']; ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-light mb-3">
                                    <div class="card-body text-center">
                                        <h5 class="card-title">Promedio (min)</h5>
                                        <p class="card-text display-4">
                                            <?php echo round($uso_computadoras['promedio_minutos']); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Libros más solicitados</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Libro</th>
                                        <th>Solicitudes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($top_libros as $libro): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($libro['titulo']); ?></td>
                                        <td><?php echo $libro['cantidad']; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Gráfico de asistencia diaria
    const asistenciaCtx = document.getElementById('asistenciaChart').getContext('2d');
    const asistenciaChart = new Chart(asistenciaCtx, {
        type: 'line',
        data: {
            labels: [<?php foreach($asistencia_diaria as $a) echo "'" . $a['fecha'] . "',"; ?>],
            datasets: [{
                label: 'Asistencia diaria',
                data: [<?php foreach($asistencia_diaria as $a) echo $a['cantidad'] . ","; ?>],
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1,
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Gráfico de facultades
    const facultadCtx = document.getElementById('facultadChart').getContext('2d');
    const facultadChart = new Chart(facultadCtx, {
        type: 'bar',
        data: {
            labels: [<?php foreach($top_facultades as $f) echo "'" . $f['facultad'] . "',"; ?>],
            datasets: [{
                label: 'Visitas',
                data: [<?php foreach($top_facultades as $f) echo $f['cantidad'] . ","; ?>],
                backgroundColor: 'rgba(75, 192, 192, 0.7)',
                borderWidth: 1
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            scales: {
                x: {
                    beginAtZero: true
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
    </script>
</body>

</html>