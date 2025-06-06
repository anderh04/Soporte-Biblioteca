<?php
require_once 'config.php';
session_start();

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $cedula = $_POST['cedula'];
    $password = $_POST['password'];
    
    // En un sistema real, aquí deberías verificar las credenciales contra la base de datos
    // Este es un ejemplo simplificado
    
    try {
        $stmt = $conn->prepare("SELECT * FROM estudiantes WHERE cedula = :cedula");
        $stmt->bindParam(':cedula', $cedula);
        $stmt->execute();
        
        if($stmt->rowCount() == 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Verificar contraseña (en un sistema real debería estar hasheada)
            if($password === 'password123') { // Contraseña de ejemplo
                $_SESSION['user_id'] = $user['id_estudiante'];
                $_SESSION['user_name'] = $user['nombre'];
                $_SESSION['user_facultad'] = $user['facultad'];
                
                // Registrar entrada a la biblioteca
                registrarEntradaBiblioteca($user['id_estudiante'], $conn);
                
                header("Location: dashboard.php");
                exit;
            } else {
                header("Location: index.php?error=credenciales");
                exit;
            }
        } else {
            header("Location: index.php?error=credenciales");
            exit;
        }
    } catch(PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}

function registrarEntradaBiblioteca($id_estudiante, $conn) {
    try {
        $fecha_actual = date('Y-m-d');
        $hora_actual = date('H:i:s');
        
        // Verificar si ya tiene una entrada hoy sin salida
        $stmt = $conn->prepare("SELECT id_asistencia FROM asistencia_biblioteca 
                               WHERE id_estudiante = :id_estudiante 
                               AND fecha = :fecha 
                               AND hora_salida IS NULL");
        $stmt->bindParam(':id_estudiante', $id_estudiante);
        $stmt->bindParam(':fecha', $fecha_actual);
        $stmt->execute();
        
        if($stmt->rowCount() == 0) {
            // Registrar nueva entrada
            $insert = $conn->prepare("INSERT INTO asistencia_biblioteca 
                                    (id_estudiante, fecha, hora_entrada) 
                                    VALUES (:id_estudiante, :fecha, :hora_entrada)");
            $insert->bindParam(':id_estudiante', $id_estudiante);
            $insert->bindParam(':fecha', $fecha_actual);
            $insert->bindParam(':hora_entrada', $hora_actual);
            $insert->execute();
        }
    } catch(PDOException $e) {
        die("Error al registrar entrada: " . $e->getMessage());
    }
}
?>