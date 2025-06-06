<?php
require_once 'config.php';
session_start();

if(!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Obtener todas las solicitudes del estudiante
try {
    $stmt = $conn->prepare("SELECT s.*, l.titulo, l.autor, c.nombre as categoria 
                          FROM solicitudes_libros s
                          JOIN libros l ON s.id_libro = l.id_libro
                          JOIN categorias_libros c ON l.id_categoria = c.id_categoria
                          WHERE s.id_estudiante = :id_estudiante
                          ORDER BY s.fecha_solicitud DESC");
    $stmt->bindParam(':id_estudiante', $_SESSION['user_id']);
    $stmt->execute();
    $solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
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
</head>

<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <h2 class="mb-4">Mis Solicitudes de Libros</h2>

        <?php if(count($solicitudes) > 0): ?>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Libro</th>
                        <th>Autor</th>
                        <th>Categoría</th>
                        <th>Fecha solicitud</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($solicitudes as $solicitud): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($solicitud['titulo']); ?></td>
                        <td><?php echo htmlspecialchars($solicitud['autor']); ?></td>
                        <td><?php echo htmlspecialchars($solicitud['categoria']); ?></td>
                        <td><?php echo htmlspecialchars($solicitud['fecha_solicitud']); ?></td>
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
                            <button class="btn btn-sm btn-info" data-bs-toggle="modal"
                                data-bs-target="#modalDetalle<?php echo $solicitud['id_solicitud']; ?>">
                                <i class="bi bi-info-circle"></i> Detalles
                            </button>
                        </td>
                    </tr>

                    <!-- Modal de detalles -->
                    <div class="modal fade" id="modalDetalle<?php echo $solicitud['id_solicitud']; ?>" tabindex="-1"
                        aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Detalles de solicitud</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <p><strong>Libro:</strong> <?php echo htmlspecialchars($solicitud['titulo']); ?></p>
                                    <p><strong>Autor:</strong> <?php echo htmlspecialchars($solicitud['autor']); ?></p>
                                    <p><strong>Categoría:</strong>
                                        <?php echo htmlspecialchars($solicitud['categoria']); ?></p>
                                    <p><strong>Fecha solicitud:</strong>
                                        <?php echo htmlspecialchars($solicitud['fecha_solicitud']); ?></p>
                                    <p><strong>Estado:</strong> <?php echo htmlspecialchars($solicitud['estado']); ?>
                                    </p>

                                    <?php if($solicitud['motivo']): ?>
                                    <p><strong>Motivo:</strong> <?php echo htmlspecialchars($solicitud['motivo']); ?>
                                    </p>
                                    <?php endif; ?>

                                    <?php if($solicitud['respuesta']): ?>
                                    <p><strong>Respuesta:</strong>
                                        <?php echo htmlspecialchars($solicitud['respuesta']); ?></p>
                                    <?php endif; ?>

                                    <?php if($solicitud['fecha_aprobacion']): ?>
                                    <p><strong>Fecha aprobación:</strong>
                                        <?php echo htmlspecialchars($solicitud['fecha_aprobacion']); ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary"
                                        data-bs-dismiss="modal">Cerrar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="alert alert-info">
            No has realizado ninguna solicitud de libros todavía.
            <a href="libros.php" class="alert-link">Buscar libros</a>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>