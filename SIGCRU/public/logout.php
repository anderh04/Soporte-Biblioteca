<?php
session_start();

// Registrar salida si está logueado
if(isset($_SESSION['user_id'])) {
    require_once '../config/config.php';
    
    try {
        $fecha_actual = date('Y-m-d');
        $hora_actual = date('H:i:s');
        
        // Registrar salida de biblioteca
        $stmt = $conn->prepare("UPDATE asistencia_biblioteca 
                               SET hora_salida = :hora_salida 
                               WHERE id_estudiante = :id_estudiante 
                               AND fecha = :fecha 
                               AND hora_salida IS NULL");
        $stmt->bindParam(':hora_salida', $hora_actual);
        $stmt->bindParam(':id_estudiante', $_SESSION['user_id']);
        $stmt->bindParam(':fecha', $fecha_actual);
        $stmt->execute();
        
        // Registrar salida de computadoras si está usando una
        $stmt = $conn->prepare("UPDATE uso_computadoras 
                               SET hora_fin = :hora_fin 
                               WHERE id_estudiante = :id_estudiante 
                               AND fecha = :fecha 
                               AND hora_fin IS NULL");
        $stmt->bindParam(':hora_fin', $hora_actual);
        $stmt->bindParam(':id_estudiante', $_SESSION['user_id']);
        $stmt->bindParam(':fecha', $fecha_actual);
        $stmt->execute();
    } catch(PDOException $e) {
        // No detenemos el logout aunque falle el registro de salida
        error_log("Error al registrar salida: " . $e->getMessage());
    }
}

// Destruir sesión
session_unset();
session_destroy();

// Redirigir a inicio
header("Location: ../index.php");
exit;
?>