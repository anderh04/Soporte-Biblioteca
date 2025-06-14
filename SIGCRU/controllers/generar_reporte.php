<?php
require_once __DIR__ . '../config/config.php';
require_once __DIR__ . '../ReportesController.php';
session_start();

// Verificar si es administrador
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != 1) {
    header("Location: ../index.php");
    exit;
}

// Obtener parámetros de filtrado
$filtro_estado = isset($_GET['estado']) ? $_GET['estado'] : '';
$filtro_busqueda = isset($_GET['busqueda']) ? $_GET['busqueda'] : '';
$tipo_reporte = isset($_GET['tipo']) ? $_GET['tipo'] : 'pdf';

try {
    $reportesController = new reportesController($conn);
    $filename = $reportesController->generarReportePrestamos($filtro_estado, $filtro_busqueda, $tipo_reporte);

    // Enviar el archivo al navegador
    if ($tipo_reporte === 'pdf') {
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment;filename="' . basename($filename) . '"');
    } else {
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . basename($filename) . '"');
        header('Cache-Control: max-age=0');
    }

    readfile($filename);
    $reportesController->limpiarArchivosTemporales();
    exit;
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header("Location: ../admin_prestamos.php");
    exit;
}