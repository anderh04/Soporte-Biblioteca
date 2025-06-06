<?php
require_once 'config.php';
session_start();

if(!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Verificar si ya está usando una computadora
try {
    $fecha_actual = date('Y-m-d');
    $stmt = $conn->prepare("SELECT * FROM uso_computadoras 
                          WHERE id_estudiante = :id_estudiante 
                          AND fecha = :fecha 
                          AND hora_fin IS NULL");
    $stmt->bindParam(':id_estudiante', $_SESSION['user_id']);
    $stmt->bindParam(':fecha', $fecha_actual);
    $stmt->execute();
    $uso_actual = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Procesar inicio de uso de computadora
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['iniciar_uso'])) {
    $computadora_id = $_POST['computadora_id'];
    
    try {
        $fecha_actual = date('Y-m-d');
        $hora_actual = date('H:i:s');
        
        // Verificar si la computadora está disponible
        $stmt = $conn->prepare("SELECT * FROM uso_computadoras 
                              WHERE computadora_id = :computadora_id 
                              AND fecha = :fecha 
                              AND hora_fin IS NULL");
        $stmt->bindParam(':computadora_id', $computadora_id);
        $stmt->bindParam(':fecha', $fecha_actual);
        $stmt->execute();
        
        if($stmt->rowCount() == 0) {
            // Registrar uso
            $insert = $conn->prepare("INSERT INTO uso_computadoras 
                                    (id_estudiante, fecha, hora_inicio, computadora_id) 
                                    VALUES (:id_estudiante, :fecha, :hora_inicio, :computadora_id)");
            $insert->bindParam(':id_estudiante', $_SESSION['user_id']);
            $insert->bindParam(':fecha', $fecha_actual);
            $insert->bindParam(':hora_inicio', $hora_actual);
            $insert->bindParam(':computadora_id', $computadora_id);
            $insert->execute();
            
            $_SESSION['mensaje'] = "Uso de computadora #$computadora_id iniciado correctamente.";
            header("Location: computadoras.php");
            exit;
        } else {
            $_SESSION['error'] = "La computadora #$computadora_id ya está en uso.";
            header("Location: computadoras.php");
            exit;
        }
    } catch(PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}

// Procesar fin de uso de computadora
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['finalizar_uso'])) {
    try {
        $fecha_actual = date('Y-m-d');
        $hora_actual = date('H:i:s');
        
        // Finalizar uso
        $update = $conn->prepare("UPDATE uso_computadoras 
                                SET hora_fin = :hora_fin 
                                WHERE id_estudiante = :id_estudiante 
                                AND fecha = :fecha 
                                AND hora_fin IS NULL");
        $update->bindParam(':hora_fin', $hora_actual);
        $update->bindParam(':id_estudiante', $_SESSION['user_id']);
        $update->bindParam(':fecha', $fecha_actual);
        $update->execute();
        
        $_SESSION['mensaje'] = "Uso de computadora finalizado correctamente.";
        header("Location: computadoras.php");
        exit;
    } catch(PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}

// Obtener historial de uso
try {
    $stmt = $conn->prepare("SELECT * FROM uso_computadoras 
                          WHERE id_estudiante = :id_estudiante
                          ORDER BY fecha DESC, hora_inicio DESC
                          LIMIT 10");
    $stmt->bindParam(':id_estudiante', $_SESSION['user_id']);
    $stmt->execute();
    $historial = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biblioteca CRUBA - Computadoras</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>

<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <h2 class="mb-4">Uso de Computadoras</h2>

        <?php if(isset($_SESSION['mensaje'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['mensaje']; unset($_SESSION['mensaje']); ?></div>
        <?php endif; ?>

        <?php if(isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Estado actual</h5>
                    </div>
                    <div class="card-body">
                        <?php if($uso_actual): ?>
                        <div class="alert alert-info">
                            <p>Actualmente estás usando la computadora
                                #<?php echo htmlspecialchars($uso_actual['computadora_id']); ?></p>
                            <p><strong>Hora de inicio:</strong>
                                <?php echo htmlspecialchars($uso_actual['hora_inicio']); ?></p>

                            <form method="post" action="computadoras.php">
                                <button type="submit" name="finalizar_uso" class="btn btn-danger">
                                    Finalizar uso
                                </button>
                            </form>
                        </div>
                        <?php else: ?>
                        <p>No estás usando ninguna computadora actualmente.</p>

                        <form method="post" action="computadoras.php" class="mt-3">
                            <div class="mb-3">
                                <label for="computadora_id" class="form-label">Número de computadora</label>
                                <select class="form-select" id="computadora_id" name="computadora_id" required>
                                    <?php for($i = 1; $i <= 20; $i++): ?>
                                    <option value="<?php echo $i; ?>">Computadora #<?php echo $i; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <button type="submit" name="iniciar_uso" class="btn btn-primary">
                                Iniciar uso
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Historial de uso</h5>
                    </div>
                    <div class="card-body">
                        <?php if(count($historial) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Computadora</th>
                                        <th>Hora inicio</th>
                                        <th>Hora fin</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($historial as $registro): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($registro['fecha']); ?></td>
                                        <td>#<?php echo htmlspecialchars($registro['computadora_id']); ?></td>
                                        <td><?php echo htmlspecialchars($registro['hora_inicio']); ?></td>
                                        <td><?php echo $registro['hora_fin'] ? htmlspecialchars($registro['hora_fin']) : '--'; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <p>No hay registros de uso de computadoras.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>