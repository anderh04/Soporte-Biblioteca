<?php
require_once 'config.php';
session_start();

// Verificar si es administrador
if(!isset($_SESSION['user_id']) || $_SESSION['user_id'] != 1) {
    header("Location: index.php");
    exit;
}

// Procesar cambio de estado
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cambiar_estado'])) {
    $id_solicitud = $_POST['id_solicitud'];
    $nuevo_estado = $_POST['nuevo_estado'];
    $observaciones = $_POST['observaciones'];
    
    try {
        $conn->beginTransaction();
        
        // Actualizar estado de la solicitud
        $stmt = $conn->prepare("UPDATE solicitudes_libros SET estado = :estado WHERE id_solicitud = :id_solicitud");
        $stmt->bindParam(':estado', $nuevo_estado);
        $stmt->bindParam(':id_solicitud', $id_solicitud);
        $stmt->execute();
        
        // Si se aprueba, crear registro de préstamo
        if($nuevo_estado == 'Aprobada') {
            $fecha_actual = date('Y-m-d H:i:s');
            $fecha_devolucion = date('Y-m-d H:i:s', strtotime('+7 days'));
            
            $insert = $conn->prepare("INSERT INTO prestamos_libros 
                                     (id_solicitud, fecha_prestamo, fecha_devolucion_esperada, estado) 
                                     VALUES (:id_solicitud, :fecha_prestamo, :fecha_devolucion, 'Prestado')");
            $insert->bindParam(':id_solicitud', $id_solicitud);
            $insert->bindParam(':fecha_prestamo', $fecha_actual);
            $insert->bindParam(':fecha_devolucion', $fecha_devolucion);
            $insert->execute();
            
            // Actualizar fecha de aprobación
            $update = $conn->prepare("UPDATE solicitudes_libros 
                                    SET fecha_aprobacion = :fecha_aprobacion, 
                                        id_aprobador = :id_aprobador 
                                    WHERE id_solicitud = :id_solicitud");
            $update->bindParam(':fecha_aprobacion', $fecha_actual);
            $update->bindParam(':id_aprobador', $_SESSION['user_id']);
            $update->bindParam(':id_solicitud', $id_solicitud);
            $update->execute();
            
            // Enviar correo al estudiante
            $stmt = $conn->prepare("SELECT e.correo, l.titulo 
                                  FROM solicitudes_libros s
                                  JOIN estudiantes e ON s.id_estudiante = e.id_estudiante
                                  JOIN libros l ON s.id_libro = l.id_libro
                                  WHERE s.id_solicitud = :id_solicitud");
            $stmt->bindParam(':id_solicitud', $id_solicitud);
            $stmt->execute();
            $info = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $asunto = "Solicitud de libro aprobada";
            $mensaje = "Tu solicitud del libro '{$info['titulo']}' ha sido aprobada. Debes recogerlo en la biblioteca antes de " . date('d/m/Y', strtotime('+2 days'));
            
            enviarCorreo($info['correo'], $asunto, $mensaje);
        }
        
        // Si se devuelve, actualizar préstamo
        if($nuevo_estado == 'Devuelto') {
            $fecha_actual = date('Y-m-d H:i:s');
            
            $update = $conn->prepare("UPDATE prestamos_libros 
                                    SET fecha_devolucion_real = :fecha_devolucion,
                                        estado = 'Devuelto',
                                        observaciones = :observaciones
                                    WHERE id_solicitud = :id_solicitud");
            $update->bindParam(':fecha_devolucion', $fecha_actual);
            $update->bindParam(':observaciones', $observaciones);
            $update->bindParam(':id_solicitud', $id_solicitud);
            $update->execute();
            
            // Actualizar libro como disponible
            $update = $conn->prepare("UPDATE libros l
                                    JOIN solicitudes_libros s ON l.id_libro = s.id_libro
                                    SET l.cantidad_disponible = l.cantidad_disponible + 1
                                    WHERE s.id_solicitud = :id_solicitud");
            $update->bindParam(':id_solicitud', $id_solicitud);
            $update->execute();
        }
        
        $conn->commit();
        $_SESSION['mensaje'] = "Estado actualizado correctamente.";
    } catch(PDOException $e) {
        $conn->rollBack();
        $_SESSION['error'] = "Error al actualizar estado: " . $e->getMessage();
    }
    
    header("Location: admin_prestamos.php");
    exit;
}

// Obtener todas las solicitudes con filtros
$filtro_estado = isset($_GET['estado']) ? $_GET['estado'] : '';
$filtro_busqueda = isset($_GET['busqueda']) ? $_GET['busqueda'] : '';

$where = "WHERE 1=1";
$params = [];

if(!empty($filtro_estado)) {
    $where .= " AND s.estado = :estado";
    $params[':estado'] = $filtro_estado;
}

if(!empty($filtro_busqueda)) {
    $where .= " AND (e.nombre LIKE :busqueda OR e.cedula LIKE :busqueda OR l.titulo LIKE :busqueda)";
    $params[':busqueda'] = "%$filtro_busqueda%";
}

try {
    $sql = "SELECT s.*, e.nombre as estudiante, e.cedula, l.titulo as libro, 
                   p.fecha_prestamo, p.fecha_devolucion_esperada, p.fecha_devolucion_real, p.estado as estado_prestamo,
                   a.nombre as aprobador
            FROM solicitudes_libros s
            JOIN estudiantes e ON s.id_estudiante = e.id_estudiante
            JOIN libros l ON s.id_libro = l.id_libro
            LEFT JOIN prestamos_libros p ON s.id_solicitud = p.id_solicitud
            LEFT JOIN administradores a ON s.id_aprobador = a.id_admin
            $where
            ORDER BY s.fecha_solicitud DESC";
    
    $stmt = $conn->prepare($sql);
    
    foreach($params as $key => &$val) {
        $stmt->bindParam($key, $val);
    }
    
    $stmt->execute();
    $solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Obtener conteo por estado para estadísticas
try {
    $stmt = $conn->query("SELECT estado, COUNT(*) as cantidad FROM solicitudes_libros GROUP BY estado");
    $estadisticas_estados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $conn->query("SELECT 
                         COUNT(*) as total,
                         SUM(CASE WHEN p.estado = 'Atrasado' THEN 1 ELSE 0 END) as atrasados,
                         SUM(CASE WHEN p.estado = 'Perdido' THEN 1 ELSE 0 END) as perdidos
                         FROM prestamos_libros p");
    $estadisticas_prestamos = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biblioteca CRUBA - Gestión de Préstamos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container mt-4">
        <h2 class="mb-4">Gestión de Préstamos de Libros</h2>
        
        <?php if(isset($_SESSION['mensaje'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['mensaje']; unset($_SESSION['mensaje']); ?></div>
        <?php endif; ?>
        
        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card text-white bg-primary">
                    <div class="card-body text-center">
                        <h5 class="card-title">Total Solicitudes</h5>
                        <p class="card-text display-4"><?php echo count($solicitudes); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-warning">
                    <div class="card-body text-center">
                        <h5 class="card-title">Préstamos Atrasados</h5>
                        <p class="card-text display-4"><?php echo $estadisticas_prestamos['atrasados']; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-danger">
                    <div class="card-body text-center">
                        <h5 class="card-title">Libros Perdidos</h5>
                        <p class="card-text display-4"><?php echo $estadisticas_prestamos['perdidos']; ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Filtros</h5>
            </div>
            <div class="card-body">
                <form method="get" action="admin_prestamos.php">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="estado" class="form-label">Estado</label>
                                <select class="form-select" id="estado" name="estado">
                                    <option value="">Todos los estados</option>
                                    <option value="Pendiente" <?php if($filtro_estado == 'Pendiente') echo 'selected'; ?>>Pendiente</option>
                                    <option value="Aprobada" <?php if($filtro_estado == 'Aprobada') echo 'selected'; ?>>Aprobada</option>
                                    <option value="Rechazada" <?php if($filtro_estado == 'Rechazada') echo 'selected'; ?>>Rechazada</option>
                                    <option value="Entregado" <?php if($filtro_estado == 'Entregado') echo 'selected'; ?>>Entregado</option>
                                    <option value="Devuelto" <?php if($filtro_estado == 'Devuelto') echo 'selected'; ?>>Devuelto</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="busqueda" class="form-label">Buscar (estudiante, cédula o libro)</label>
                                <input type="text" class="form-control" id="busqueda" name="busqueda" 
                                       value="<?php echo htmlspecialchars($filtro_busqueda); ?>">
                            </div>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Listado de Solicitudes</h5>
                <div>
                    <a href="generar_reporte.php?tipo=pdf" class="btn btn-danger me-2">
                        <i class="bi bi-file-earmark-pdf"></i> PDF
                    </a>
                    <a href="generar_reporte.php?tipo=excel" class="btn btn-success">
                        <i class="bi bi-file-earmark-excel"></i> Excel
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Estudiante</th>
                                <th>Cédula</th>
                                <th>Libro</th>
                                <th>Fecha Solicitud</th>
                                <th>Estado</th>
                                <th>Fecha Préstamo</th>
                                <th>Fecha Devolución</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($solicitudes as $solicitud): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($solicitud['estudiante']); ?></td>
                                    <td><?php echo htmlspecialchars($solicitud['cedula']); ?></td>
                                    <td><?php echo htmlspecialchars($solicitud['libro']); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($solicitud['fecha_solicitud'])); ?></td>
                                    <td>
                                        <span class="badge 
                                            <?php 
                                            switch($solicitud['estado']) {
                                                case 'Aprobada': echo 'bg-success'; break;
                                                case 'Rechazada': echo 'bg-danger'; break;
                                                case 'Entregado': echo 'bg-primary'; break;
                                                case 'Devuelto': echo 'bg-secondary'; break;
                                                default: echo 'bg-warning text-dark';
                                            }
                                            ?>">
                                            <?php echo htmlspecialchars($solicitud['estado']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if($solicitud['fecha_prestamo']): ?>
                                            <?php echo date('d/m/Y', strtotime($solicitud['fecha_prestamo'])); ?>
                                        <?php else: ?>
                                            --
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($solicitud['fecha_devolucion_real']): ?>
                                            <?php echo date('d/m/Y', strtotime($solicitud['fecha_devolucion_real'])); ?>
                                        <?php elseif($solicitud['fecha_devolucion_esperada']): ?>
                                            <?php echo date('d/m/Y', strtotime($solicitud['fecha_devolucion_esperada'])); ?>
                                            <?php if(strtotime($solicitud['fecha_devolucion_esperada']) < time()): ?>
                                                <span class="badge bg-danger">Atrasado</span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            --
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" 
                                                data-bs-target="#modalCambiarEstado<?php echo $solicitud['id_solicitud']; ?>">
                                            <i class="bi bi-pencil"></i> Cambiar estado
                                        </button>
                                    </td>
                                </tr>
                                
                                <!-- Modal para cambiar estado -->
                                <div class="modal fade" id="modalCambiarEstado<?php echo $solicitud['id_solicitud']; ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Cambiar estado de solicitud</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <form method="post" action="admin_prestamos.php">
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label">Solicitud de <?php echo htmlspecialchars($solicitud['estudiante']); ?></label>
                                                        <p><strong>Libro:</strong> <?php echo htmlspecialchars($solicitud['libro']); ?></p>
                                                        <p><strong>Estado actual:</strong> <?php echo htmlspecialchars($solicitud['estado']); ?></p>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label for="nuevo_estado<?php echo $solicitud['id_solicitud']; ?>" class="form-label">Nuevo estado</label>
                                                        <select class="form-select" id="nuevo_estado<?php echo $solicitud['id_solicitud']; ?>" name="nuevo_estado" required>
                                                            <option value="Pendiente" <?php if($solicitud['estado'] == 'Pendiente') echo 'selected'; ?>>Pendiente</option>
                                                            <option value="Aprobada" <?php if($solicitud['estado'] == 'Aprobada') echo 'selected'; ?>>Aprobada</option>
                                                            <option value="Rechazada" <?php if($solicitud['estado'] == 'Rechazada') echo 'selected'; ?>>Rechazada</option>
                                                            <option value="Entregado" <?php if($solicitud['estado'] == 'Entregado') echo 'selected'; ?>>Entregado</option>
                                                            <option value="Devuelto" <?php if($solicitud['estado'] == 'Devuelto') echo 'selected'; ?>>Devuelto</option>
                                                        </select>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label for="observaciones<?php echo $solicitud['id_solicitud']; ?>" class="form-label">Observaciones</label>
                                                        <textarea class="form-control" id="observaciones<?php echo $solicitud['id_solicitud']; ?>" 
                                                                  name="observaciones" rows="3"><?php echo htmlspecialchars($solicitud['observaciones'] ?? ''); ?></textarea>
                                                    </div>
                                                    
                                                    <input type="hidden" name="id_solicitud" value="<?php echo $solicitud['id_solicitud']; ?>">
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                    <button type="submit" name="cambiar_estado" class="btn btn-primary">Guardar cambios</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Estadísticas por Estado</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="estadosChart" height="300"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Préstamos Activos</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="prestamosChart" height="300"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Gráfico de estados
        const estadosCtx = document.getElementById('estadosChart').getContext('2d');
        const estadosChart = new Chart(estadosCtx, {
            type: 'pie',
            data: {
                labels: [<?php foreach($estadisticas_estados as $e) echo "'" . $e['estado'] . "',"; ?>],
                datasets: [{
                    data: [<?php foreach($estadisticas_estados as $e) echo $e['cantidad'] . ","; ?>],
                    backgroundColor: [
                        'rgba(255, 206, 86, 0.7)',
                        'rgba(75, 192, 192, 0.7)',
                        'rgba(255, 99, 132, 0.7)',
                        'rgba(54, 162, 235, 0.7)',
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
        
        // Gráfico de préstamos
        const prestamosCtx = document.getElementById('prestamosChart').getContext('2d');
        const prestamosChart = new Chart(prestamosCtx, {
            type: 'doughnut',
            data: {
                labels: ['En tiempo', 'Atrasados', 'Perdidos'],
                datasets: [{
                    data: [
                        <?php echo $estadisticas_prestamos['total'] - $estadisticas_prestamos['atrasados'] - $estadisticas_prestamos['perdidos']; ?>,
                        <?php echo $estadisticas_prestamos['atrasados']; ?>,
                        <?php echo $estadisticas_prestamos['perdidos']; ?>
                    ],
                    backgroundColor: [
                        'rgba(75, 192, 192, 0.7)',
                        'rgba(255, 206, 86, 0.7)',
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
    </script>
</body>
</html>