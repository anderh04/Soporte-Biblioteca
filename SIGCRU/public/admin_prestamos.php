<?php
require_once '../config/config.php';
session_start();

// Verificar si es administrador
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != 1) {
    header("Location: ../../index.php");
    exit;
}

// Procesar cambio de estado de préstamo (existente)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cambiar_estado'])) {
    // [Mantener el código existente para cambio de estado de préstamos]
}

// Procesar modificación de asistencia
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['modificar_asistencia'])) {
    $id_asistencia = $_POST['id_asistencia'];
    $accion = $_POST['accion'];
    $motivo = $_POST['motivo'];

    try {
        $conn->beginTransaction();

        $fecha_actual = date('Y-m-d H:i:s');

        if ($accion == 'registrar_salida') {
            // Registrar salida
            $stmt = $conn->prepare("UPDATE asistencia_biblioteca 
                                  SET hora_salida = :hora_salida 
                                  WHERE id_asistencia = :id_asistencia");
            $stmt->bindParam(':hora_salida', $fecha_actual);
            $stmt->bindParam(':id_asistencia', $id_asistencia);
            $stmt->execute();

            // Registrar en el historial
            $stmt = $conn->prepare("INSERT INTO historial_modificaciones 
                                  (tipo, id_registro, accion, motivo, id_admin, fecha) 
                                  VALUES ('asistencia', :id_asistencia, 'Registró salida', :motivo, :id_admin, :fecha)");
            $stmt->bindParam(':id_asistencia', $id_asistencia);
            $stmt->bindParam(':motivo', $motivo);
            $stmt->bindParam(':id_admin', $_SESSION['user_id']);
            $stmt->bindParam(':fecha', $fecha_actual);
            $stmt->execute();

            $_SESSION['mensaje'] = "Salida registrada correctamente.";
        } elseif ($accion == 'eliminar_registro') {
            // Eliminar registro
            $stmt = $conn->prepare("DELETE FROM asistencia_biblioteca WHERE id_asistencia = :id_asistencia");
            $stmt->bindParam(':id_asistencia', $id_asistencia);
            $stmt->execute();

            // Registrar en el historial
            $stmt = $conn->prepare("INSERT INTO historial_modificaciones 
                                  (tipo, id_registro, accion, motivo, id_admin, fecha) 
                                  VALUES ('asistencia', :id_asistencia, 'Eliminó registro', :motivo, :id_admin, :fecha)");
            $stmt->bindParam(':id_asistencia', $id_asistencia);
            $stmt->bindParam(':motivo', $motivo);
            $stmt->bindParam(':id_admin', $_SESSION['user_id']);
            $stmt->bindParam(':fecha', $fecha_actual);
            $stmt->execute();

            $_SESSION['mensaje'] = "Registro de asistencia eliminado correctamente.";
        }

        $conn->commit();
    } catch (PDOException $e) {
        $conn->rollBack();
        $_SESSION['error'] = "Error al modificar asistencia: " . $e->getMessage();
    }

    header("Location: admin_prestamos.php");
    exit;
}

// Procesar modificación de uso de computadoras
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['modificar_computadora'])) {
    $id_uso = $_POST['id_uso'];
    $accion = $_POST['accion'];
    $motivo = $_POST['motivo'];

    try {
        $conn->beginTransaction();

        $fecha_actual = date('Y-m-d H:i:s');

        if ($accion == 'registrar_fin') {
            // Registrar fin de uso
            $stmt = $conn->prepare("UPDATE uso_computadoras 
                                  SET hora_fin = :hora_fin 
                                  WHERE id_uso = :id_uso");
            $stmt->bindParam(':hora_fin', $fecha_actual);
            $stmt->bindParam(':id_uso', $id_uso);
            $stmt->execute();

            // Registrar en el historial
            $stmt = $conn->prepare("INSERT INTO historial_modificaciones 
                                  (tipo, id_registro, accion, motivo, id_admin, fecha) 
                                  VALUES ('computadora', :id_uso, 'Registró fin de uso', :motivo, :id_admin, :fecha)");
            $stmt->bindParam(':id_uso', $id_uso);
            $stmt->bindParam(':motivo', $motivo);
            $stmt->bindParam(':id_admin', $_SESSION['user_id']);
            $stmt->bindParam(':fecha', $fecha_actual);
            $stmt->execute();

            $_SESSION['mensaje'] = "Fin de uso de computadora registrado correctamente.";
        } elseif ($accion == 'eliminar_registro') {
            // Eliminar registro
            $stmt = $conn->prepare("DELETE FROM uso_computadoras WHERE id_uso = :id_uso");
            $stmt->bindParam(':id_uso', $id_uso);
            $stmt->execute();

            // Registrar en el historial
            $stmt = $conn->prepare("INSERT INTO historial_modificaciones 
                                  (tipo, id_registro, accion, motivo, id_admin, fecha) 
                                  VALUES ('computadora', :id_uso, 'Eliminó registro', :motivo, :id_admin, :fecha)");
            $stmt->bindParam(':id_uso', $id_uso);
            $stmt->bindParam(':motivo', $motivo);
            $stmt->bindParam(':id_admin', $_SESSION['user_id']);
            $stmt->bindParam(':fecha', $fecha_actual);
            $stmt->execute();

            $_SESSION['mensaje'] = "Registro de uso de computadora eliminado correctamente.";
        }

        $conn->commit();
    } catch (PDOException $e) {
        $conn->rollBack();
        $_SESSION['error'] = "Error al modificar uso de computadora: " . $e->getMessage();
    }

    header("Location: admin_prestamos.php");
    exit;
}

// Obtener todas las solicitudes con filtros
$filtro_estado = isset($_GET['estado']) ? $_GET['estado'] : '';
$filtro_busqueda = isset($_GET['busqueda']) ? $_GET['busqueda'] : '';

$where = "WHERE 1=1";
$params = [];

if (!empty($filtro_estado)) {
    $where .= " AND s.estado = :estado";
    $params[':estado'] = $filtro_estado;
}

if (!empty($filtro_busqueda)) {
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

    foreach ($params as $key => &$val) {
        $stmt->bindParam($key, $val);
    }

    $stmt->execute();
    $solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
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
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Obtener estudiantes actualmente en la biblioteca
try {
    $fecha_actual = date('Y-m-d');
    $stmt = $conn->prepare("SELECT a.id_asistencia, e.nombre, e.cedula, e.facultad, e.correo, a.hora_entrada 
                          FROM asistencia_biblioteca a
                          JOIN estudiantes e ON a.id_estudiante = e.id_estudiante
                          WHERE a.fecha = :fecha AND a.hora_salida IS NULL
                          ORDER BY a.hora_entrada DESC");
    $stmt->bindParam(':fecha', $fecha_actual);
    $stmt->execute();
    $en_biblioteca = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Obtener estudiantes actualmente usando computadoras (incluyendo id_uso)
try {
    $stmt = $conn->prepare("SELECT u.id_uso, e.nombre, e.cedula, e.facultad, e.correo, u.hora_inicio, u.computadora_id 
                          FROM uso_computadoras u
                          JOIN estudiantes e ON u.id_estudiante = e.id_estudiante
                          WHERE u.fecha = :fecha AND u.hora_fin IS NULL
                          ORDER BY u.hora_inicio DESC");
    $stmt->bindParam(':fecha', $fecha_actual);
    $stmt->execute();
    $en_computadoras = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
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
    <style>
        .card-header {
            font-weight: bold;
        }

        .badge {
            font-size: 0.9em;
        }

        .table-responsive {
            max-height: 300px;
            overflow-y: auto;
        }
    </style>
</head>

<body>
    <?php include '../src/assets/includes/navbar.php'; ?>

    <div class="container mt-4">
        <h2 class="mb-4">Gestión de Préstamos de Libros</h2>

        <?php if (isset($_SESSION['mensaje'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['mensaje'];
                                                unset($_SESSION['mensaje']); ?></div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error'];
                                            unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <!-- Sección: Estudiantes activos -->
        <div class="row mb-4">
            <!-- Estudiantes en la biblioteca -->
            <div class="col-md-6 mb-3">
                <div class="card h-100">
                    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-people-fill"></i> Estudiantes en la Biblioteca
                            <span class="badge bg-white text-info"><?php echo count($en_biblioteca); ?></span>
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (count($en_biblioteca) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Estudiante</th>
                                            <th>Cédula</th>
                                            <th>Facultad</th>
                                            <th>Hora Entrada</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($en_biblioteca as $estudiante): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($estudiante['nombre']); ?></td>
                                                <td><?php echo htmlspecialchars($estudiante['cedula']); ?></td>
                                                <td><?php echo htmlspecialchars($estudiante['facultad']); ?></td>
                                                <td><?php echo date('H:i', strtotime($estudiante['hora_entrada'])); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal"
                                                        data-bs-target="#modalAsistencia<?php echo $estudiante['id_asistencia']; ?>">
                                                        <i class="bi bi-gear"></i> Modificar
                                                    </button>
                                                </td>
                                            </tr>

                                            <!-- Modal para modificar asistencia -->
                                            <div class="modal fade"
                                                id="modalAsistencia<?php echo $estudiante['id_asistencia']; ?>" tabindex="-1"
                                                aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Modificar Asistencia</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                                aria-label="Close"></button>
                                                        </div>
                                                        <form method="post" action="admin_prestamos.php">
                                                            <div class="modal-body">
                                                                <div class="mb-3">
                                                                    <label class="form-label">Estudiante:
                                                                        <?php echo htmlspecialchars($estudiante['nombre']); ?></label>
                                                                    <p><strong>Cédula:</strong>
                                                                        <?php echo htmlspecialchars($estudiante['cedula']); ?>
                                                                    </p>
                                                                    <p><strong>Hora entrada:</strong>
                                                                        <?php echo date('H:i', strtotime($estudiante['hora_entrada'])); ?>
                                                                    </p>
                                                                </div>

                                                                <div class="mb-3">
                                                                    <label class="form-label">Acción a realizar</label>
                                                                    <select class="form-select" name="accion" required>
                                                                        <option value="registrar_salida">Registrar salida
                                                                        </option>
                                                                        <option value="eliminar_registro">Eliminar registro
                                                                        </option>
                                                                    </select>
                                                                </div>

                                                                <div class="mb-3 modal-motivo">
                                                                    <label
                                                                        for="motivoAsistencia<?php echo $estudiante['id_asistencia']; ?>"
                                                                        class="form-label">Motivo de la modificación</label>
                                                                    <textarea class="form-control"
                                                                        id="motivoAsistencia<?php echo $estudiante['id_asistencia']; ?>"
                                                                        name="motivo" required></textarea>
                                                                </div>

                                                                <input type="hidden" name="id_asistencia"
                                                                    value="<?php echo $estudiante['id_asistencia']; ?>">
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary"
                                                                    data-bs-dismiss="modal">Cancelar</button>
                                                                <button type="submit" name="modificar_asistencia"
                                                                    class="btn btn-primary">Confirmar</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info m-3">No hay estudiantes registrados en la biblioteca actualmente.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Estudiantes en computadoras -->
            <div class="col-md-6 mb-3">
                <div class="card h-100">
                    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-pc-display"></i> Estudiantes en Computadoras
                            <span class="badge bg-white text-success"><?php echo count($en_computadoras); ?></span>
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (count($en_computadoras) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Estudiante</th>
                                            <th>Cédula</th>
                                            <th>Facultad</th>
                                            <th>Computadora</th>
                                            <th>Hora Inicio</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($en_computadoras as $estudiante): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($estudiante['nombre']); ?></td>
                                                <td><?php echo htmlspecialchars($estudiante['cedula']); ?></td>
                                                <td><?php echo htmlspecialchars($estudiante['facultad']); ?></td>
                                                <td>#<?php echo htmlspecialchars($estudiante['computadora_id']); ?></td>
                                                <td><?php echo date('H:i', strtotime($estudiante['hora_inicio'])); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal"
                                                        data-bs-target="#modalComputadora<?php echo $estudiante['id_uso']; ?>">
                                                        <i class="bi bi-gear"></i> Modificar
                                                    </button>
                                                </td>
                                            </tr>

                                            <!-- Modal para modificar uso de computadora -->
                                            <div class="modal fade" id="modalComputadora<?php echo $estudiante['id_uso']; ?>"
                                                tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Modificar Uso de Computadora</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                                aria-label="Close"></button>
                                                        </div>
                                                        <form method="post" action="admin_prestamos.php">
                                                            <div class="modal-body">
                                                                <div class="mb-3">
                                                                    <label class="form-label">Estudiante:
                                                                        <?php echo htmlspecialchars($estudiante['nombre']); ?></label>
                                                                    <p><strong>Cédula:</strong>
                                                                        <?php echo htmlspecialchars($estudiante['cedula']); ?>
                                                                    </p>
                                                                    <p><strong>Computadora #:</strong>
                                                                        <?php echo htmlspecialchars($estudiante['computadora_id']); ?>
                                                                    </p>
                                                                    <p><strong>Hora inicio:</strong>
                                                                        <?php echo date('H:i', strtotime($estudiante['hora_inicio'])); ?>
                                                                    </p>
                                                                </div>

                                                                <div class="mb-3">
                                                                    <label class="form-label">Acción a realizar</label>
                                                                    <select class="form-select" name="accion" required>
                                                                        <option value="registrar_fin">Registrar fin de uso
                                                                        </option>
                                                                        <option value="eliminar_registro">Eliminar registro
                                                                        </option>
                                                                    </select>
                                                                </div>

                                                                <div class="mb-3 modal-motivo">
                                                                    <label
                                                                        for="motivoComputadora<?php echo $estudiante['id_uso']; ?>"
                                                                        class="form-label">Motivo de la modificación</label>
                                                                    <textarea class="form-control"
                                                                        id="motivoComputadora<?php echo $estudiante['id_uso']; ?>"
                                                                        name="motivo" required></textarea>
                                                                </div>

                                                                <input type="hidden" name="id_uso"
                                                                    value="<?php echo $estudiante['id_uso']; ?>">
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary"
                                                                    data-bs-dismiss="modal">Cancelar</button>
                                                                <button type="submit" name="modificar_computadora"
                                                                    class="btn btn-primary">Confirmar</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info m-3">No hay estudiantes usando computadoras actualmente.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sección: Estadísticas y filtros -->
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

        <!-- Sección: Filtros -->
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
                                    <option value="Pendiente"
                                        <?php if ($filtro_estado == 'Pendiente') echo 'selected'; ?>>Pendiente</option>
                                    <option value="Aprobada" <?php if ($filtro_estado == 'Aprobada') echo 'selected'; ?>>
                                        Aprobada</option>
                                    <option value="Rechazada"
                                        <?php if ($filtro_estado == 'Rechazada') echo 'selected'; ?>>Rechazada</option>
                                    <option value="Entregado"
                                        <?php if ($filtro_estado == 'Entregado') echo 'selected'; ?>>Entregado</option>
                                    <option value="Devuelto" <?php if ($filtro_estado == 'Devuelto') echo 'selected'; ?>>
                                        Devuelto</option>
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

        <!-- Sección: Listado de solicitudes -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Listado de Solicitudes</h5>
                <div>
                    <a href="exportar_pdf.php?tipo=solicitudes" class="btn btn-danger me-2">
                        <i class="bi bi-file-earmark-pdf"></i> PDF
                    </a>
                    <a href="exportar_csv.php?tipo=solicitudes" class="btn btn-success">
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
                            <?php foreach ($solicitudes as $solicitud): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($solicitud['estudiante']); ?></td>
                                    <td><?php echo htmlspecialchars($solicitud['cedula']); ?></td>
                                    <td><?php echo htmlspecialchars($solicitud['libro']); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($solicitud['fecha_solicitud'])); ?></td>
                                    <td>
                                        <span class="badge 
                                            <?php
                                            switch ($solicitud['estado']) {
                                                case 'Aprobada':
                                                    echo 'bg-success';
                                                    break;
                                                case 'Rechazada':
                                                    echo 'bg-danger';
                                                    break;
                                                case 'Entregado':
                                                    echo 'bg-primary';
                                                    break;
                                                case 'Devuelto':
                                                    echo 'bg-secondary';
                                                    break;
                                                default:
                                                    echo 'bg-warning text-dark';
                                            }
                                            ?>">
                                            <?php echo htmlspecialchars($solicitud['estado']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($solicitud['fecha_prestamo']): ?>
                                            <?php echo date('d/m/Y', strtotime($solicitud['fecha_prestamo'])); ?>
                                        <?php else: ?>
                                            --
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($solicitud['fecha_devolucion_real']): ?>
                                            <?php echo date('d/m/Y', strtotime($solicitud['fecha_devolucion_real'])); ?>
                                        <?php elseif ($solicitud['fecha_devolucion_esperada']): ?>
                                            <?php echo date('d/m/Y', strtotime($solicitud['fecha_devolucion_esperada'])); ?>
                                            <?php if (strtotime($solicitud['fecha_devolucion_esperada']) < time()): ?>
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
                                <div class="modal fade" id="modalCambiarEstado<?php echo $solicitud['id_solicitud']; ?>"
                                    tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Cambiar estado de solicitud</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                    aria-label="Close"></button>
                                            </div>
                                            <form method="post" action="admin_prestamos.php">
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label">Solicitud de
                                                            <?php echo htmlspecialchars($solicitud['estudiante']); ?></label>
                                                        <p><strong>Libro:</strong>
                                                            <?php echo htmlspecialchars($solicitud['libro']); ?></p>
                                                        <p><strong>Estado actual:</strong>
                                                            <?php echo htmlspecialchars($solicitud['estado']); ?></p>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label for="nuevo_estado<?php echo $solicitud['id_solicitud']; ?>"
                                                            class="form-label">Nuevo estado</label>
                                                        <select class="form-select"
                                                            id="nuevo_estado<?php echo $solicitud['id_solicitud']; ?>"
                                                            name="nuevo_estado" required>
                                                            <option value="Pendiente"
                                                                <?php if ($solicitud['estado'] == 'Pendiente') echo 'selected'; ?>>
                                                                Pendiente</option>
                                                            <option value="Aprobada"
                                                                <?php if ($solicitud['estado'] == 'Aprobada') echo 'selected'; ?>>
                                                                Aprobada</option>
                                                            <option value="Rechazada"
                                                                <?php if ($solicitud['estado'] == 'Rechazada') echo 'selected'; ?>>
                                                                Rechazada</option>
                                                            <option value="Entregado"
                                                                <?php if ($solicitud['estado'] == 'Entregado') echo 'selected'; ?>>
                                                                Entregado</option>
                                                            <option value="Devuelto"
                                                                <?php if ($solicitud['estado'] == 'Devuelto') echo 'selected'; ?>>
                                                                Devuelto</option>
                                                        </select>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label for="observaciones<?php echo $solicitud['id_solicitud']; ?>"
                                                            class="form-label">Observaciones</label>
                                                        <textarea class="form-control"
                                                            id="observaciones<?php echo $solicitud['id_solicitud']; ?>"
                                                            name="observaciones"
                                                            rows="3"><?php echo htmlspecialchars($solicitud['observaciones'] ?? ''); ?></textarea>
                                                    </div>

                                                    <input type="hidden" name="id_solicitud"
                                                        value="<?php echo $solicitud['id_solicitud']; ?>">
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary"
                                                        data-bs-dismiss="modal">Cancelar</button>
                                                    <button type="submit" name="cambiar_estado"
                                                        class="btn btn-primary">Guardar cambios</button>
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

        <!-- Sección: Gráficos estadísticos -->
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
                labels: [<?php foreach ($estadisticas_estados as $e) echo "'" . $e['estado'] . "',"; ?>],
                datasets: [{
                    data: [<?php foreach ($estadisticas_estados as $e) echo $e['cantidad'] . ","; ?>],
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