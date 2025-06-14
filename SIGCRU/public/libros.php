<?php
require_once '../config/config.php';
require_once '../controllers/LibrosController.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

$librosController = new LibrosController($conn);

try {
    // Obtener categorías para el filtro
    $categorias = $librosController->getCategorias();

    // Procesar búsqueda/filtro
    $busqueda = isset($_GET['busqueda']) ? $_GET['busqueda'] : null;
    $categoria = isset($_GET['categoria']) ? $_GET['categoria'] : null;
    $libros = $librosController->buscarLibros($busqueda, $categoria);

    // Procesar solicitud de libro
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['solicitar_libro'])) {
        $id_libro = $_POST['id_libro'];
        $motivo = $_POST['motivo'];

        try {
            $librosController->solicitarLibro($_SESSION['user_id'], $id_libro, $motivo);
            $_SESSION['mensaje'] = "Solicitud enviada correctamente. Te notificaremos por correo cuando sea revisada.";
            header("Location: libros.php");
            exit;
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header("Location: libros.php");
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
    <title>Biblioteca CRUBA - Libros</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>

<body>
    <?php include '../src/assets/includes/navbar.php'; ?>

    <div class="container mt-4">
        <h2 class="mb-4">Catálogo de Libros</h2>

        <?php if (isset($_SESSION['mensaje'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['mensaje'];
                                                unset($_SESSION['mensaje']); ?></div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?php echo $_SESSION['error'];
                                            unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-body">
                <form method="get" action="libros.php">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="busqueda" class="form-label">Buscar por título o autor</label>
                                <input type="text" class="form-control" id="busqueda" name="busqueda"
                                    value="<?php echo isset($_GET['busqueda']) ? htmlspecialchars($_GET['busqueda']) : ''; ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="categoria" class="form-label">Categoría</label>
                                <select class="form-select" id="categoria" name="categoria">
                                    <option value="">Todas las categorías</option>
                                    <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?php echo $categoria['id_categoria']; ?>"
                                        <?php if (isset($_GET['categoria']) && $_GET['categoria'] == $categoria['id_categoria']) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($categoria['nombre']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">Buscar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="row">
            <?php if (count($libros) > 0): ?>
            <?php foreach ($libros as $libro): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><?php echo htmlspecialchars($libro['titulo']); ?></h5>
                    </div>
                    <div class="card-body">
                        <p class="card-text">
                            <strong>Autor:</strong> <?php echo htmlspecialchars($libro['autor']); ?><br>
                            <strong>Categoría:</strong> <?php echo htmlspecialchars($libro['categoria']); ?><br>
                            <strong>Año:</strong> <?php echo htmlspecialchars($libro['anio_publicacion']); ?><br>
                            <strong>Disponibles:</strong> <?php echo htmlspecialchars($libro['cantidad_disponible']); ?>
                        </p>
                    </div>
                    <div class="card-footer bg-transparent">
                        <button class="btn btn-primary w-100" data-bs-toggle="modal"
                            data-bs-target="#modalSolicitar<?php echo $libro['id_libro']; ?>">
                            Solicitar libro
                        </button>
                    </div>
                </div>
            </div>

            <!-- Modal para solicitar libro -->
            <div class="modal fade" id="modalSolicitar<?php echo $libro['id_libro']; ?>" tabindex="-1"
                aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Solicitar: <?php echo htmlspecialchars($libro['titulo']); ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form method="post" action="libros.php">
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label for="motivo<?php echo $libro['id_libro']; ?>" class="form-label">Motivo de la
                                        solicitud</label>
                                    <textarea class="form-control" id="motivo<?php echo $libro['id_libro']; ?>"
                                        name="motivo" rows="3" required></textarea>
                                </div>
                                <input type="hidden" name="id_libro" value="<?php echo $libro['id_libro']; ?>">
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary"
                                    data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" name="solicitar_libro" class="btn btn-primary">Enviar
                                    solicitud</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info">
                    No se encontraron libros disponibles con los criterios de búsqueda seleccionados.
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>