<?php
require_once '../config/config.php';
session_start();

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $cedula = $_POST['cedula'];
    $password = $_POST['password'];

    try {
        // Primero verificar si es estudiante
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
                $_SESSION['user_type'] = 'estudiante'; // Agregamos tipo de usuario

                // Registrar entrada a la biblioteca solo para estudiantes
                try {
                    registrarEntradaBiblioteca($user['id_estudiante'], $conn);
                } catch(Exception $e) {
                    error_log("Error al registrar entrada: " . $e->getMessage());
                    header("Location: ../../index.php?error=entrada");
                    exit;
                }

                header("Location: dashboard.php");
                exit;
            } else {
                header("Location: ../../index.php?error=credenciales");
                exit;
            }
        } else {
            // Si no es estudiante, verificar si es administrador
            $stmt = $conn->prepare("SELECT * FROM administradores WHERE cedula = :cedula");
            $stmt->bindParam(':cedula', $cedula);
            $stmt->execute();

            if($stmt->rowCount() == 1) {
                $admin = $stmt->fetch(PDO::FETCH_ASSOC);

                // Verificar contraseña para admin (podría ser diferente)
                if($password === 'admin123') { // Contraseña de ejemplo para admin
                    $_SESSION['user_id'] = $admin['id_admin'];
                    $_SESSION['user_name'] = $admin['nombre'];
                    $_SESSION['user_type'] = 'administrador'; // Tipo de usuario
                    
                    // Redirigir a dashboard de admin (puede ser diferente)
                      header("Location: admin_prestamos.php");
                    exit;
                } else {
                    header("Location: ../../index.php?error=credenciales");
                    exit;
                }
            } else {
                header("Location: ../../index.php?error=credenciales");
                exit;
            }
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