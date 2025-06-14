<?php
require_once '../config/config.php';
session_start();
if(!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'administrador') {
    header('Location: ../index.php?error=no_admin');
    exit;
}

require_once 'funciones_escuelas.php'; // Si separas las funciones

$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

switch($accion) {
    case 'crear':
        $nombre = $_POST['nombre'] ?? '';
        $id_facultad = $_POST['id_facultad'] ?? 0;
        $descripcion = $_POST['descripcion'] ?? null;
        
        $resultado = crearEscuela($nombre, $id_facultad, $descripcion);
        if(is_numeric($resultado)) {
            $_SESSION['mensaje'] = 'Escuela creada correctamente';
        } else {
            $_SESSION['error'] = $resultado;
        }
        break;
        
    case 'editar':
        $id = $_POST['id_escuela'] ?? 0;
        $nombre = $_POST['nombre'] ?? '';
        $id_facultad = $_POST['id_facultad'] ?? 0;
        $descripcion = $_POST['descripcion'] ?? null;
        
        $resultado = actualizarEscuela($id, $nombre, $id_facultad, $descripcion);
        if($resultado === true) {
            $_SESSION['mensaje'] = 'Escuela actualizada correctamente';
        } else {
            $_SESSION['error'] = $resultado;
        }
        break;
        
    case 'eliminar':
        $id = $_GET['id'] ?? 0;
        
        $resultado = eliminarEscuela($id);
        if($resultado === true) {
            $_SESSION['mensaje'] = 'Escuela eliminada correctamente';
        } else {
            $_SESSION['error'] = $resultado;
        }
        break;
}

header('Location: admin.php');
exit;
?>