<?php
require_once 'config.php';

function enviarCorreo($destinatario, $asunto, $mensaje) {
    // En un sistema real, implementarías el envío de correo aquí
    // Esto es un ejemplo simplificado que solo registra en un archivo de log
    $log = date('Y-m-d H:i:s') . " - Para: $destinatario - Asunto: $asunto\n";
    file_put_contents('correos.log', $log, FILE_APPEND);
    
    // En producción usarías algo como PHPMailer o la función mail() de PHP
    // mail($destinatario, $asunto, $mensaje);
}

function obtenerEstudiantePorCedula($cedula, $conn) {
    try {
        $stmt = $conn->prepare("SELECT * FROM estudiantes WHERE cedula = :cedula");
        $stmt->bindParam(':cedula', $cedula);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}

function obtenerAsistenciaDiaria($conn) {
    try {
        $fecha_actual = date('Y-m-d');
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM asistencia_biblioteca WHERE fecha = :fecha");
        $stmt->bindParam(':fecha', $fecha_actual);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    } catch(PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}

function obtenerComputadorasEnUso($conn) {
    try {
        $fecha_actual = date('Y-m-d');
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM uso_computadoras 
                              WHERE fecha = :fecha AND hora_fin IS NULL");
        $stmt->bindParam(':fecha', $fecha_actual);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    } catch(PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}
?>