<?php
require_once 'config.php';
session_start();

// Verificar si es administrador (en un sistema real deberías tener una columna de rol en la tabla de usuarios)
if(!isset($_SESSION['user_id']) || $_SESSION['user_id'] != 1) {
    header("Location: index.php");
    exit;
}

// Obtener estadísticas
try {
    // Total estudiantes registrados
    $stmt = $conn->query("SELECT COUNT(*) as total FROM estudiantes");
    $total_estudiantes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Estudiantes por género
    $stmt = $conn->query("SELECT genero, COUNT(*) as cantidad FROM estudiantes GROUP BY genero");
    $generos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Facultades con más uso
    $stmt = $conn->query("SELECT e.facultad, COUNT(*) as cantidad 
                         FROM asistencia_biblioteca a
                         JOIN estudiantes e ON a.id_estudiante = e.id_estudiante
                         GROUP BY e.facultad 
                         ORDER BY cantidad DESC 
                         LIMIT 5");
    $facultades = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Turnos con más uso
    $stmt = $conn->query("SELECT 
                         CASE 
                             WHEN HOUR(hora_entrada) BETWEEN 7 AND 12 THEN 'Matutino'
                             WHEN HOUR(hora_entrada) BETWEEN 13 AND 17 THEN 'Vespertino'
                             ELSE 'Nocturno'
                         END as turno,
                         COUNT(*) as cantidad
                         FROM asistencia_biblioteca
                         GROUP BY turno
                         ORDER BY cantidad DESC");
    $turnos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Categorías de libros más solicitadas
    $stmt = $conn->query("SELECT c.nombre, COUNT(*) as cantidad 
                         FROM solicitudes_libros s
                         JOIN libros l ON s.id_libro = l.id_libro
                         JOIN categorias_libros c ON l.id_categoria = c.id_categoria
                         GROUP BY c.nombre 
                         ORDER BY cantidad DESC 
                         LIMIT 5");
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Uso de computadoras
    $stmt = $conn->query("SELECT COUNT(*) as sesiones, 
                         AVG(TIMESTAMPDIFF(MINUTE, hora_inicio, hora_fin)) as promedio_minutos
                         FROM uso_computadoras
                         WHERE hora_fin IS NOT NULL");
    $uso_computadoras = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biblioteca CRUBA - Administración</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <h2 class="mb-4">Panel de Administración</h2>

        <div class="row">
            <div class="col-md-3 mb-4">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <h5 class="card-title">Estudiantes</h5>
                        <p class="card-text display-4"><?php echo $total_estudiantes; ?></p>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-4">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <h5 class="card-title">Sesiones computadoras</h5>
                        <p class="card-text display-4"><?php echo $uso_computadoras['sesiones']; ?></p>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-4">
                <div class="card text-white bg-info">
                    <div class="card-body">
                        <h5 class="card-title">Promedio uso (min)</h5>
                        <p class="card-text display-4"><?php echo round($uso_computadoras['promedio_minutos']); ?></p>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-4">
                <div class="card text-white bg-warning">
                    <div class="card-body">
                        <h5 class="card-title">Solicitudes libros</h5>
                        <p class="card-text display-4"><?php 
                            $stmt = $conn->query("SELECT COUNT(*) as total FROM solicitudes_libros");
                            echo $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                        ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Estudiantes por género</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="generoChart" height="200"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Uso por turno</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="turnoChart" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Facultades con más uso</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="facultadChart" height="200"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Categorías más solicitadas</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="categoriaChart" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Gráfico de género
    const generoCtx = document.getElementById('generoChart').getContext('2d');
    const generoChart = new Chart(generoCtx, {
        type: 'pie',
        data: {
            labels: [<?php foreach($generos as $g) echo "'" . $g['genero'] . "',"; ?>],
            datasets: [{
                data: [<?php foreach($generos as $g) echo $g['cantidad'] . ","; ?>],
                backgroundColor: [
                    'rgba(54, 162, 235, 0.7)',
                    'rgba(255, 99, 132, 0.7)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Gráfico de turnos
    const turnoCtx = document.getElementById('turnoChart').getContext('2d');
    const turnoChart = new Chart(turnoCtx, {
        type: 'bar',
        data: {
            labels: [<?php foreach($turnos as $t) echo "'" . $t['turno'] . "',"; ?>],
            datasets: [{
                label: 'Visitas',
                data: [<?php foreach($turnos as $t) echo $t['cantidad'] . ","; ?>],
                backgroundColor: 'rgba(75, 192, 192, 0.7)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
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

    // Gráfico de facultades
    const facultadCtx = document.getElementById('facultadChart').getContext('2d');
    const facultadChart = new Chart(facultadCtx, {
        type: 'bar',
        data: {
            labels: [<?php foreach($facultades as $f) echo "'" . $f['facultad'] . "',"; ?>],
            datasets: [{
                label: 'Visitas',
                data: [<?php foreach($facultades as $f) echo $f['cantidad'] . ","; ?>],
                backgroundColor: 'rgba(153, 102, 255, 0.7)',
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

    // Gráfico de categorías
    const categoriaCtx = document.getElementById('categoriaChart').getContext('2d');
    const categoriaChart = new Chart(categoriaCtx, {
        type: 'doughnut',
        data: {
            labels: [<?php foreach($categorias as $c) echo "'" . $c['nombre'] . "',"; ?>],
            datasets: [{
                data: [<?php foreach($categorias as $c) echo $c['cantidad'] . ","; ?>],
                backgroundColor: [
                    'rgba(255, 99, 132, 0.7)',
                    'rgba(54, 162, 235, 0.7)',
                    'rgba(255, 206, 86, 0.7)',
                    'rgba(75, 192, 192, 0.7)',
                    'rgba(153, 102, 255, 0.7)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
    </script>
</body>

</html>