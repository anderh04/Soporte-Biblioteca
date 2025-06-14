<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../controllers/ReportesController.php';

// Verificar si el usuario está autenticado
if (!isset($_SESSION['id_estudiante'])) {
    header('Location: login.php');
    exit();
}
 
// Obtener fechas del filtro
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : date('Y-m-01');
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : date('Y-m-d');

// Manejar exportaciones
if (isset($_POST['exportar'])) {
    try {
        if ($_POST['exportar'] === 'excel') {
            $filename = $reportesController->exportarExcel($fecha_inicio, $fecha_fin);
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . basename($filename) . '"');
            header('Cache-Control: max-age=0');
            readfile($filename);
            $reportesController->limpiarArchivosTemporales();
            exit();
        } elseif ($_POST['exportar'] === 'pdf') {
            $filename = $reportesController->exportarPDF($fecha_inicio, $fecha_fin);
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment;filename="' . basename($filename) . '"');
            readfile($filename);
            $reportesController->limpiarArchivosTemporales();
            exit();
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

// Obtener estadísticas
try {
    $estadisticas = $reportesController->getEstadisticas($fecha_inicio, $fecha_fin);
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    $estadisticas = [
        'asistencia_diaria' => [],
        'top_facultades' => [],
        'uso_computadoras' => ['sesiones' => 0, 'promedio_minutos' => 0],
        'top_libros' => []
    ];
}

// Limpiar archivos temporales antiguos
$reportesController->limpiarArchivosTemporales();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - Biblioteca CRUBA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/styles.css" rel="stylesheet">
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <h2>Reportes de Biblioteca</h2>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Filtro de fechas -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label for="fecha_inicio" class="form-label">Fecha Inicio</label>
                        <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio"
                            value="<?php echo $fecha_inicio; ?>" max="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="fecha_fin" class="form-label">Fecha Fin</label>
                        <input type="date" class="form-control" id="fecha_fin" name="fecha_fin"
                            value="<?php echo $fecha_fin; ?>" max="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Filtrar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Botones de exportación -->
        <div class="mb-4">
            <button type="button" class="btn btn-success" onclick="exportarExcel()">
                <i class="fas fa-file-excel"></i> Exportar a Excel
            </button>
            <button type="button" class="btn btn-danger ms-2" onclick="exportarPDF()">
                <i class="fas fa-file-pdf"></i> Exportar a PDF
            </button>
        </div>

        <!-- Gráficos -->
        <div class="row">
            <!-- Asistencia Diaria -->
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">Asistencia Diaria</h5>
                        <canvas id="asistenciaChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Top Facultades -->
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">Top Facultades</h5>
                        <canvas id="facultadesChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Uso de Computadoras -->
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">Uso de Computadoras</h5>
                        <div class="row">
                            <div class="col-6">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h6>Total Sesiones</h6>
                                        <h3><?php echo $estadisticas['uso_computadoras']['sesiones']; ?></h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h6>Promedio Minutos</h6>
                                        <h3><?php echo round($estadisticas['uso_computadoras']['promedio_minutos']); ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Libros -->
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">Libros más Solicitados</h5>
                        <canvas id="librosChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/exceljs/4.3.0/exceljs.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
    <script type="module">
        import ReportesController from './js/ReportesController.js';

        // Crear instancia del controlador
        const reportesController = new ReportesController();

        // Obtener fechas del formulario
        function getFechas() {
            const fechaInicio = document.getElementById('fecha_inicio').value;
            const fechaFin = document.getElementById('fecha_fin').value;
            return {
                fechaInicio,
                fechaFin
            };
        }

        // Función para exportar a Excel
        window.exportarExcel = async function() {
            try {
                const {
                    fechaInicio,
                    fechaFin
                } = getFechas();
                await reportesController.exportarExcel(fechaInicio, fechaFin);
            } catch (error) {
                alert('Error al exportar a Excel: ' + error.message);
            }
        };

        // Función para exportar a PDF
        window.exportarPDF = async function() {
            try {
                const {
                    fechaInicio,
                    fechaFin
                } = getFechas();
                await reportesController.exportarPDF(fechaInicio, fechaFin);
            } catch (error) {
                alert('Error al exportar a PDF: ' + error.message);
            }
        };

        // Datos para los gráficos
        const asistenciaData = {
            labels: <?php echo json_encode(array_column($estadisticas['asistencia_diaria'], 'fecha')); ?>,
            datasets: [{
                label: 'Asistencia',
                data: <?php echo json_encode(array_column($estadisticas['asistencia_diaria'], 'cantidad')); ?>,
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1,
                tension: 0.4
            }]
        };

        const facultadesData = {
            labels: <?php echo json_encode(array_column($estadisticas['top_facultades'], 'facultad')); ?>,
            datasets: [{
                label: 'Cantidad',
                data: <?php echo json_encode(array_column($estadisticas['top_facultades'], 'cantidad')); ?>,
                backgroundColor: [
                    'rgba(255, 99, 132, 0.2)',
                    'rgba(54, 162, 235, 0.2)',
                    'rgba(255, 206, 86, 0.2)',
                    'rgba(75, 192, 192, 0.2)',
                    'rgba(153, 102, 255, 0.2)'
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)'
                ],
                borderWidth: 1
            }]
        };

        const librosData = {
            labels: <?php echo json_encode(array_column($estadisticas['top_libros'], 'titulo')); ?>,
            datasets: [{
                label: 'Solicitudes',
                data: <?php echo json_encode(array_column($estadisticas['top_libros'], 'cantidad')); ?>,
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1
            }]
        };

        // Configuración común para los gráficos
        const chartConfig = {
            type: 'bar',
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        };

        // Crear gráficos
        new Chart(document.getElementById('asistenciaChart'), {
            ...chartConfig,
            type: 'line',
            data: asistenciaData
        });

        new Chart(document.getElementById('facultadesChart'), {
            ...chartConfig,
            data: facultadesData
        });

        new Chart(document.getElementById('librosChart'), {
            ...chartConfig,
            data: librosData
        });

        // Validación de fechas en el cliente
        document.querySelector('form').addEventListener('submit', function(e) {
            const fechaInicio = document.getElementById('fecha_inicio').value;
            const fechaFin = document.getElementById('fecha_fin').value;

            if (fechaInicio && fechaFin && fechaInicio > fechaFin) {
                e.preventDefault();
                alert('La fecha de inicio debe ser anterior a la fecha fin');
            }
        });
    </script>
</body>

</html>