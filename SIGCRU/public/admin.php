<?php
require_once '../config/config.php';
session_start();
if(!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'administrador') {
    header('Location: ../index.php?error=no_admin');
    exit;
}

// Funciones para Facultades
function crearFacultad($nombre, $descripcion = null) {
    global $conn;
    try {
        $stmt = $conn->prepare("INSERT INTO facultades (nombre, descripcion) VALUES (:nombre, :descripcion)");
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':descripcion', $descripcion);
        $stmt->execute();
        return $conn->lastInsertId();
    } catch(PDOException $e) {
        return "Error al crear facultad: " . $e->getMessage();
    }
}

function obtenerFacultades() {
    global $conn;
    try {
        $stmt = $conn->query("SELECT * FROM facultades ORDER BY nombre");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return "Error al obtener facultades: " . $e->getMessage();
    }
}

function obtenerFacultadPorId($id) {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT * FROM facultades WHERE id_facultad = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return "Error al obtener facultad: " . $e->getMessage();
    }
}

function actualizarFacultad($id, $nombre, $descripcion = null) {
    global $conn;
    try {
        $stmt = $conn->prepare("UPDATE facultades SET nombre = :nombre, descripcion = :descripcion WHERE id_facultad = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':descripcion', $descripcion);
        return $stmt->execute();
    } catch(PDOException $e) {
        return "Error al actualizar facultad: " . $e->getMessage();
    }
}

function eliminarFacultad($id) {
    global $conn;
    try {
        $escuelas = contarEscuelasPorFacultad($id);
        if ($escuelas > 0) {
            return "No se puede eliminar la facultad porque tiene escuelas asociadas";
        }
        
        $stmt = $conn->prepare("DELETE FROM facultades WHERE id_facultad = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    } catch(PDOException $e) {
        return "Error al eliminar facultad: " . $e->getMessage();
    }
}

// Funciones para Escuelas
function crearEscuela($nombre, $id_facultad, $descripcion = null) {
    global $conn;
    try {
        if (!obtenerFacultadPorId($id_facultad)) {
            return "La facultad especificada no existe";
        }
        
        $stmt = $conn->prepare("INSERT INTO escuelas (nombre, id_facultad, descripcion) VALUES (:nombre, :id_facultad, :descripcion)");
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':id_facultad', $id_facultad, PDO::PARAM_INT);
        $stmt->bindParam(':descripcion', $descripcion);
        $stmt->execute();
        return $conn->lastInsertId();
    } catch(PDOException $e) {
        return "Error al crear escuela: " . $e->getMessage();
    }
}

function obtenerEscuelas() {
    global $conn;
    try {
        $stmt = $conn->query("SELECT e.*, f.nombre as facultad_nombre 
                             FROM escuelas e 
                             JOIN facultades f ON e.id_facultad = f.id_facultad 
                             ORDER BY e.nombre");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return "Error al obtener escuelas: " . $e->getMessage();
    }
}

function obtenerEscuelasPorFacultad($id_facultad) {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT * FROM escuelas WHERE id_facultad = :id_facultad ORDER BY nombre");
        $stmt->bindParam(':id_facultad', $id_facultad, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return "Error al obtener escuelas: " . $e->getMessage();
    }
}

function contarEscuelasPorFacultad($id_facultad) {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM escuelas WHERE id_facultad = :id_facultad");
        $stmt->bindParam(':id_facultad', $id_facultad, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    } catch(PDOException $e) {
        return "Error al contar escuelas: " . $e->getMessage();
    }
}

function obtenerEscuelaPorId($id) {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT e.*, f.nombre as facultad_nombre 
                              FROM escuelas e 
                              JOIN facultades f ON e.id_facultad = f.id_facultad 
                              WHERE e.id_escuela = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return "Error al obtener escuela: " . $e->getMessage();
    }
}

function actualizarEscuela($id, $nombre, $id_facultad, $descripcion = null) {
    global $conn;
    try {
        $stmt = $conn->prepare("UPDATE escuelas SET nombre = :nombre, id_facultad = :id_facultad, descripcion = :descripcion WHERE id_escuela = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':id_facultad', $id_facultad, PDO::PARAM_INT);
        $stmt->bindParam(':descripcion', $descripcion);
        return $stmt->execute();
    } catch(PDOException $e) {
        return "Error al actualizar escuela: " . $e->getMessage();
    }
}

function eliminarEscuela($id) {
    global $conn;
    try {
        // Verificar si hay estudiantes asociados a esta escuela
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM estudiantes WHERE escuela = (SELECT nombre FROM escuelas WHERE id_escuela = :id)");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['total'] > 0) {
            return "No se puede eliminar la escuela porque tiene estudiantes asociados";
        }
        
        $stmt = $conn->prepare("DELETE FROM escuelas WHERE id_escuela = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    } catch(PDOException $e) {
        return "Error al eliminar escuela: " . $e->getMessage();
    }
}

// Obtener estadísticas
try {
    // Total estudiantes registrados
    $stmt = $conn->query("SELECT COUNT(*) as total FROM estudiantes");
    $total_estudiantes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Estudiantes por género
    $stmt = $conn->query("SELECT genero, COUNT(*) as cantidad FROM estudiantes WHERE genero IS NOT NULL GROUP BY genero");
    $generos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Facultades con más uso
    $stmt = $conn->query("SELECT f.nombre as facultad, COUNT(*) as cantidad 
                         FROM asistencia_biblioteca a
                         JOIN estudiantes e ON a.id_estudiante = e.id_estudiante
                         JOIN escuelas es ON e.escuela = es.nombre
                         JOIN facultades f ON es.id_facultad = f.id_facultad
                         GROUP BY f.nombre 
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
    
    // Obtener todas las facultades y escuelas para el panel de administración
    $todas_facultades = obtenerFacultades();
    $todas_escuelas = obtenerEscuelas();
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
    <?php include '../src/assets/includes/navbar.php'; ?>

    <div class="container mt-4">
        <h2 class="mb-4">Panel de Administración</h2>

        <!-- Menú de navegación para administración -->
        <ul class="nav nav-tabs mb-4" id="adminTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="stats-tab" data-bs-toggle="tab" data-bs-target="#stats"
                    type="button" role="tab">Estadísticas</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="facultades-tab" data-bs-toggle="tab" data-bs-target="#facultades"
                    type="button" role="tab">Facultades</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="escuelas-tab" data-bs-toggle="tab" data-bs-target="#escuelas" type="button"
                    role="tab">Escuelas</button>
            </li>
        </ul>

        <div class="tab-content" id="adminTabsContent">
            <!-- Pestaña de Estadísticas -->
            <div class="tab-pane fade show active" id="stats" role="tabpanel">
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
                                <p class="card-text display-4">
                                    <?php echo round($uso_computadoras['promedio_minutos']); ?></p>
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

            <!-- Pestaña de Facultades -->
            <div class="tab-pane fade" id="facultades" role="tabpanel">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Gestión de Facultades</h5>
                    </div>
                    <div class="card-body">
                        <button class="btn btn-primary mb-3" data-bs-toggle="modal"
                            data-bs-target="#nuevaFacultadModal">
                            <i class="bi bi-plus-circle"></i> Nueva Facultad
                        </button>

                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre</th>
                                        <th>Descripción</th>
                                        <th>Escuelas</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($todas_facultades as $facultad): ?>
                                    <tr>
                                        <td><?= $facultad['id_facultad'] ?></td>
                                        <td><?= htmlspecialchars($facultad['nombre']) ?></td>
                                        <td><?= htmlspecialchars($facultad['descripcion'] ?? 'N/A') ?></td>
                                        <td><?= contarEscuelasPorFacultad($facultad['id_facultad']) ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal"
                                                data-bs-target="#editarFacultadModal"
                                                data-id="<?= $facultad['id_facultad'] ?>"
                                                data-nombre="<?= htmlspecialchars($facultad['nombre']) ?>"
                                                data-descripcion="<?= htmlspecialchars($facultad['descripcion'] ?? '') ?>">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger"
                                                onclick="confirmarEliminarFacultad(<?= $facultad['id_facultad'] ?>)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pestaña de Escuelas -->
            <div class="tab-pane fade" id="escuelas" role="tabpanel">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Gestión de Escuelas</h5>
                    </div>
                    <div class="card-body">
                        <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#nuevaEscuelaModal">
                            <i class="bi bi-plus-circle"></i> Nueva Escuela
                        </button>

                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre</th>
                                        <th>Facultad</th>
                                        <th>Descripción</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($todas_escuelas as $escuela): ?>
                                    <tr>
                                        <td><?= $escuela['id_escuela'] ?></td>
                                        <td><?= htmlspecialchars($escuela['nombre']) ?></td>
                                        <td><?= htmlspecialchars($escuela['facultad_nombre']) ?></td>
                                        <td><?= htmlspecialchars($escuela['descripcion'] ?? 'N/A') ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal"
                                                data-bs-target="#editarEscuelaModal"
                                                data-id="<?= $escuela['id_escuela'] ?>"
                                                data-nombre="<?= htmlspecialchars($escuela['nombre']) ?>"
                                                data-facultad="<?= $escuela['id_facultad'] ?>"
                                                data-descripcion="<?= htmlspecialchars($escuela['descripcion'] ?? '') ?>">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger"
                                                onclick="confirmarEliminarEscuela(<?= $escuela['id_escuela'] ?>)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
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

    <!-- Modal para nueva facultad -->
    <div class="modal fade" id="nuevaFacultadModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nueva Facultad</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="gestion_facultades.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="accion" value="crear">
                        <div class="mb-3">
                            <label for="nombreFacultad" class="form-label">Nombre</label>
                            <input type="text" class="form-control" id="nombreFacultad" name="nombre" required>
                        </div>
                        <div class="mb-3">
                            <label for="descripcionFacultad" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcionFacultad" name="descripcion"
                                rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para editar facultad -->
    <div class="modal fade" id="editarFacultadModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Facultad</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="gestion_facultades.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="accion" value="editar">
                        <input type="hidden" name="id_facultad" id="editFacultadId">
                        <div class="mb-3">
                            <label for="editNombreFacultad" class="form-label">Nombre</label>
                            <input type="text" class="form-control" id="editNombreFacultad" name="nombre" required>
                        </div>
                        <div class="mb-3">
                            <label for="editDescripcionFacultad" class="form-label">Descripción</label>
                            <textarea class="form-control" id="editDescripcionFacultad" name="descripcion"
                                rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para nueva escuela -->
    <div class="modal fade" id="nuevaEscuelaModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nueva Escuela</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="gestion_escuelas.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="accion" value="crear">
                        <div class="mb-3">
                            <label for="nombreEscuela" class="form-label">Nombre</label>
                            <input type="text" class="form-control" id="nombreEscuela" name="nombre" required>
                        </div>
                        <div class="mb-3">
                            <label for="facultadEscuela" class="form-label">Facultad</label>
                            <select class="form-select" id="facultadEscuela" name="id_facultad" required>
                                <?php foreach($todas_facultades as $facultad): ?>
                                <option value="<?= $facultad['id_facultad'] ?>">
                                    <?= htmlspecialchars($facultad['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="descripcionEscuela" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcionEscuela" name="descripcion"
                                rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para editar escuela -->
    <div class="modal fade" id="editarEscuelaModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Escuela</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="gestion_escuelas.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="accion" value="editar">
                        <input type="hidden" name="id_escuela" id="editEscuelaId">
                        <div class="mb-3">
                            <label for="editNombreEscuela" class="form-label">Nombre</label>
                            <input type="text" class="form-control" id="editNombreEscuela" name="nombre" required>
                        </div>
                        <div class="mb-3">
                            <label for="editFacultadEscuela" class="form-label">Facultad</label>
                            <select class="form-select" id="editFacultadEscuela" name="id_facultad" required>
                                <?php foreach($todas_facultades as $facultad): ?>
                                <option value="<?= $facultad['id_facultad'] ?>">
                                    <?= htmlspecialchars($facultad['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editDescripcionEscuela" class="form-label">Descripción</label>
                            <textarea class="form-control" id="editDescripcionEscuela" name="descripcion"
                                rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Gráficos (el mismo código anterior)
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

    // Configurar modales de edición
    document.getElementById('editarFacultadModal').addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const id = button.getAttribute('data-id');
        const nombre = button.getAttribute('data-nombre');
        const descripcion = button.getAttribute('data-descripcion');

        document.getElementById('editFacultadId').value = id;
        document.getElementById('editNombreFacultad').value = nombre;
        document.getElementById('editDescripcionFacultad').value = descripcion;
    });

    document.getElementById('editarEscuelaModal').addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const id = button.getAttribute('data-id');
        const nombre = button.getAttribute('data-nombre');
        const facultad = button.getAttribute('data-facultad');
        const descripcion = button.getAttribute('data-descripcion');

        document.getElementById('editEscuelaId').value = id;
        document.getElementById('editNombreEscuela').value = nombre;
        document.getElementById('editFacultadEscuela').value = facultad;
        document.getElementById('editDescripcionEscuela').value = descripcion;
    });

    // Funciones para confirmar eliminación
    function confirmarEliminarFacultad(id) {
        if (confirm('¿Estás seguro de que deseas eliminar esta facultad?')) {
            window.location.href = `gestion_facultades.php?accion=eliminar&id=${id}`;
        }
    }

    function confirmarEliminarEscuela(id) {
        if (confirm('¿Estás seguro de que deseas eliminar esta escuela?')) {
            window.location.href = `gestion_escuelas.php?accion=eliminar&id=${id}`;
        }
    }
    </script>
</body>

</html>