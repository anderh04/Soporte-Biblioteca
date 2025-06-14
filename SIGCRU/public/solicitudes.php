<?php
require_once '../config/config.php';
require_once '../controllers/SolicitudesController.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

$solicitudesController = new SolicitudesController($conn);

try {
    // Obtener todas las solicitudes del estudiante
    $solicitudes = $solicitudesController->getSolicitudesEstudiante($_SESSION['user_id']);

    // Procesar cancelación de solicitud
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cancelar_solicitud'])) {
        $id_solicitud = $_POST['id_solicitud'];
        try {
            $solicitudesController->cancelarSolicitud($id_solicitud, $_SESSION['user_id']);
            $_SESSION['mensaje'] = "Solicitud cancelada correctamente.";
            header("Location: solicitudes.php");
            exit;
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header("Location: solicitudes.php");
            exit;
        }
    }
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biblioteca CRUBA - Mis Solicitudes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .badge-estado {
            font-size: 0.9rem;
            padding: 0.35em 0.65em;
        }
    </style>
</head>

<body>
    <?php include '../src/assets/includes/navbar.php'; ?>

    <div class="container mt-4">
        <h2 class="mb-4">Mis Solicitudes de Libros</h2>

        <?php if (isset($_SESSION['mensaje'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['mensaje'];
                                                unset($_SESSION['mensaje']); ?></div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error'];
                                            unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <ul class="nav nav-tabs mb-4" id="solicitudesTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="pendientes-tab" data-bs-toggle="tab" data-bs-target="#pendientes" type="button" role="tab">Pendientes</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="aceptadas-tab" data-bs-toggle="tab" data-bs-target="#aceptadas" type="button" role="tab">Aceptadas</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="rechazadas-tab" data-bs-toggle="tab" data-bs-target="#rechazadas" type="button" role="tab">Rechazadas</button>
            </li>
        </ul>

        <div class="tab-content" id="solicitudesTabContent">
            <!-- Tab Pendientes -->
            <div class="tab-pane fade show active" id="pendientes" role="tabpanel">
                <?php
                $pendientes = array_filter($solicitudes, function ($s) {
                    return $s['estado'] == 'Pendiente';
                });
                ?>

                <?php if (count($pendientes) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Libro</th>
                                    <th>Fecha solicitud</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pendientes as $solicitud): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($solicitud['titulo']); ?></td>
                                        <td><?php echo htmlspecialchars($solicitud['fecha_solicitud']); ?></td>
                                        <td>
                                            <span class="badge badge-estado bg-warning">
                                                <?php echo htmlspecialchars($solicitud['estado']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#modalDetalle<?php echo $solicitud['id_solicitud']; ?>">
                                                <i class="bi bi-info-circle"></i> Detalles
                                            </button>
                                            <form method="post" action="solicitudes.php" class="d-inline">
                                                <input type="hidden" name="id_solicitud" value="<?php echo $solicitud['id_solicitud']; ?>">
                                                <button type="submit" name="cancelar_solicitud" class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro de cancelar esta solicitud?')">
                                                    <i class="bi bi-x-circle"></i> Cancelar
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        No tienes solicitudes pendientes.
                    </div>
                <?php endif; ?>
            </div>

            <!-- Tab Aceptadas -->
            <div class="tab-pane fade" id="aceptadas" role="tabpanel">
                <?php
                $aceptadas = array_filter($solicitudes, function ($s) {
                    return in_array($s['estado'], ['Aceptada', 'Disponible', 'Entregado']);
                });
                ?>

                <?php if (count($aceptadas) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Libro</th>
                                    <th>Fecha aceptación</th>
                                    <th>Estado</th>
                                    <th>Disponible hasta</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($aceptadas as $solicitud): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($solicitud['titulo']); ?></td>
                                        <td><?php echo htmlspecialchars($solicitud['fecha_aprobacion']); ?></td>
                                        <td>
                                            <span class="badge badge-estado <?php
                                                                            echo $solicitud['estado'] == 'Aceptada' ? 'bg-success' : ($solicitud['estado'] == 'Disponible' ? 'bg-info' : 'bg-primary');
                                                                            ?>">
                                                <?php echo htmlspecialchars($solicitud['estado']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($solicitud['fecha_disponible']): ?>
                                                <?php
                                                $fechaFin = new DateTime($solicitud['fecha_disponible']);
                                                $fechaFin->modify('+3 days'); // 3 días para retirar
                                                echo $fechaFin->format('d/m/Y');
                                                ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#modalDetalle<?php echo $solicitud['id_solicitud']; ?>">
                                                <i class="bi bi-info-circle"></i> Detalles
                                            </button>

                                            <?php if ($solicitud['estado'] == 'Disponible'): ?>
                                                <form method="post" action="solicitudes.php" class="d-inline">
                                                    <input type="hidden" name="id_solicitud" value="<?php echo $solicitud['id_solicitud']; ?>">
                                                    <button type="submit" name="marcar_entregado" class="btn btn-sm btn-success">
                                                        <i class="bi bi-check-circle"></i> Marcar como entregado
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        No tienes solicitudes aceptadas.
                    </div>
                <?php endif; ?>
            </div>

            <!-- Tab Rechazadas -->
            <div class="tab-pane fade" id="rechazadas" role="tabpanel">
                <?php
                $rechazadas = array_filter($solicitudes, function ($s) {
                    return $s['estado'] == 'Rechazada';
                });
                ?>

                <?php if (count($rechazadas) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Libro</th>
                                    <th>Fecha solicitud</th>
                                    <th>Fecha respuesta</th>
                                    <th>Motivo</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rechazadas as $solicitud): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($solicitud['titulo']); ?></td>
                                        <td><?php echo htmlspecialchars($solicitud['fecha_solicitud']); ?></td>
                                        <td><?php echo htmlspecialchars($solicitud['fecha_aprobacion']); ?></td>
                                        <td><?php echo htmlspecialchars($solicitud['motivo']); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#modalDetalle<?php echo $solicitud['id_solicitud']; ?>">
                                                <i class="bi bi-info-circle"></i> Detalles
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        No tienes solicitudes rechazadas.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Modales de detalles (igual que antes) -->
        <?php foreach ($solicitudes as $solicitud): ?>
            <div class="modal fade" id="modalDetalle<?php echo $solicitud['id_solicitud']; ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Detalles de solicitud: <?php echo htmlspecialchars($solicitud['titulo']); ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Libro:</strong> <?php echo htmlspecialchars($solicitud['titulo']); ?></p>
                                    <p><strong>Autor:</strong> <?php echo htmlspecialchars($solicitud['autor']); ?></p>
                                    <p><strong>Categoría:</strong> <?php echo htmlspecialchars($solicitud['categoria']); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Fecha solicitud:</strong> <?php echo htmlspecialchars($solicitud['fecha_solicitud']); ?></p>
                                    <p><strong>Estado:</strong>
                                        <span class="badge <?php echo $solicitudesController->getEstadoBadgeClass($solicitud['estado']); ?>">
                                            <?php echo htmlspecialchars($solicitud['estado']); ?>
                                        </span>
                                    </p>

                                    <?php if ($solicitud['estado'] != 'Pendiente'): ?>
                                        <p><strong>Respuesta:</strong> <?php echo htmlspecialchars($solicitud['respuesta']); ?></p>
                                        <p><strong>Fecha respuesta:</strong> <?php echo htmlspecialchars($solicitud['fecha_aprobacion']); ?></p>
                                    <?php endif; ?>

                                    <?php if ($solicitud['estado'] == 'Disponible'): ?>
                                        <p class="text-info"><strong>Disponible hasta:</strong>
                                            <?php
                                            $fechaFin = new DateTime($solicitud['fecha_disponible']);
                                            $fechaFin->modify('+3 days');
                                            echo $fechaFin->format('d/m/Y');
                                            ?>
                                        </p>
                                    <?php elseif ($solicitud['estado'] == 'Entregado'): ?>
                                        <p><strong>Fecha entrega:</strong> <?php echo htmlspecialchars($solicitud['fecha_entrega']); ?></p>
                                        <p><strong>Devolución estimada:</strong> <?php echo htmlspecialchars($solicitud['fecha_devolucion_estimada']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php if ($solicitud['estado'] == 'Rechazada' && !empty($solicitud['motivo'])): ?>
                                <div class="alert alert-danger mt-3">
                                    <h5>Motivo del rechazo:</h5>
                                    <p><?php echo htmlspecialchars($solicitud['motivo']); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Activar el tab correspondiente si hay hash en la URL
        document.addEventListener('DOMContentLoaded', function() {
            if (window.location.hash) {
                const tabTrigger = new bootstrap.Tab(document.querySelector(
                    [data - bs - target = "${window.location.hash}"]
                ));
                tabTrigger.show();
            }
        });
    </script>
</body>

</html>