<?php
require_once '../config/config.php';
session_start();
if(!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'administrador') {
    header('Location: ../index.php?error=no_admin');
    exit;
}

require_once 'funciones_facultades.php'; // Si separas las funciones

$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

switch($accion) {
    case 'crear':
        $nombre = $_POST['nombre'] ?? '';
        $descripcion = $_POST['descripcion'] ?? null;
        
        $resultado = crearFacultad($nombre, $descripcion);
        if(is_numeric($resultado)) {
            $_SESSION['mensaje'] = 'Facultad creada correctamente';
        } else {
            $_SESSION['error'] = $resultado;
        }
        break;
        
    case 'editar':
        $id = $_POST['id_facultad'] ?? 0;
        $nombre = $_POST['nombre'] ?? '';
        $descripcion = $_POST['descripcion'] ?? null;
        
        $resultado = actualizarFacultad($id, $nombre, $descripcion);
        if($resultado === true) {
            $_SESSION['mensaje'] = 'Facultad actualizada correctamente';
        } else {
            $_SESSION['error'] = $resultado;
        }
        break;
        
    case 'eliminar':
        $id = $_GET['id'] ?? 0;
        
        $resultado = eliminarFacultad($id);
        if($resultado === true) {
            $_SESSION['mensaje'] = 'Facultad eliminada correctamente';
        } else {
            $_SESSION['error'] = $resultado;
        }
        break;
}

header('Location: admin.php');
exit;
?>