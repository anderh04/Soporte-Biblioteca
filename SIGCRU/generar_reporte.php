<?php
require_once 'config.php';
session_start();

// Verificar si es administrador
if(!isset($_SESSION['user_id']) || $_SESSION['user_id'] != 1) {
    header("Location: index.php");
    exit;
}

// Obtener parámetros de filtrado (los mismos que en admin_prestamos.php)
$filtro_estado = isset($_GET['estado']) ? $_GET['estado'] : '';
$filtro_busqueda = isset($_GET['busqueda']) ? $_GET['busqueda'] : '';
$tipo_reporte = isset($_GET['tipo']) ? $_GET['tipo'] : 'pdf';

// Aplicar filtros
$where = "WHERE 1=1";
$params = [];

if(!empty($filtro_estado)) {
    $where .= " AND s.estado = :estado";
    $params[':estado'] = $filtro_estado;
}

if(!empty($filtro_busqueda)) {
    $where .= " AND (e.nombre LIKE :busqueda OR e.cedula LIKE :busqueda OR l.titulo LIKE :busqueda)";
    $params[':busqueda'] = "%$filtro_busqueda%";
}

try {
    $sql = "SELECT s.*, e.nombre as estudiante, e.cedula, l.titulo as libro, 
                   p.fecha_prestamo, p.fecha_devolucion_esperada, p.fecha_devolucion_real, p.estado as estado_prestamo,
                   a.nombre as aprobador
            FROM solicitudes_libros s
            JOIN estudiantes e ON s.id_estudiante = e.id_estudiante
            JOIN libros l ON s.id_libro = l.id_libro
            LEFT JOIN prestamos_libros p ON s.id_solicitud = p.id_solicitud
            LEFT JOIN administradores a ON s.id_aprobador = a.id_admin
            $where
            ORDER BY s.fecha_solicitud DESC";
    
    $stmt = $conn->prepare($sql);
    
    foreach($params as $key => &$val) {
        $stmt->bindParam($key, $val);
    }
    
    $stmt->execute();
    $solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Generar reporte en PDF
if($tipo_reporte == 'pdf') {
    // Incluye TCPDF si no usas Composer
    if (!class_exists('TCPDF')) {
        $tcpdfPath = __DIR__ . '/tcpdf/tcpdf.php';
        if (!file_exists($tcpdfPath)) {
            die("Error: No se encuentra la librería TCPDF en '$tcpdfPath'. Descárgala desde https://tcpdf.org/ y colócala en la carpeta 'tcpdf'.");
        }
        require_once $tcpdfPath;
    }

    // Definir constantes TCPDF si no están definidas
    if (!defined('PDF_UNIT')) define('PDF_UNIT', 'mm');
    if (!defined('PDF_PAGE_FORMAT')) define('PDF_PAGE_FORMAT', 'A4');
    if (!defined('PDF_FONT_NAME_MAIN')) define('PDF_FONT_NAME_MAIN', 'helvetica');
    if (!defined('PDF_FONT_SIZE_MAIN')) define('PDF_FONT_SIZE_MAIN', 10);
    if (!defined('PDF_FONT_NAME_DATA')) define('PDF_FONT_NAME_DATA', 'helvetica');
    if (!defined('PDF_FONT_SIZE_DATA')) define('PDF_FONT_SIZE_DATA', 8);
    if (!defined('PDF_FONT_MONOSPACED')) define('PDF_FONT_MONOSPACED', 'courier');

    // Asegúrate de que la clase TCPDF esté cargada antes de instanciarla
    if (!class_exists('TCPDF')) {
        $tcpdfPath = __DIR__ . '/tcpdf/tcpdf.php';
        if (!file_exists($tcpdfPath)) {
            die("Error: No se encuentra la librería TCPDF en '$tcpdfPath'. Descárgala desde https://tcpdf.org/ y colócala en la carpeta 'tcpdf'.");
        }
        require_once $tcpdfPath;
    }
    // Si TCPDF sigue sin estar disponible, muestra un error claro
    if (!class_exists('TCPDF')) {
        die("Error: La clase TCPDF no está disponible. Asegúrate de que el archivo 'tcpdf.php' esté en la carpeta 'tcpdf'.");
    }
    $pdf = new TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetCreator('Biblioteca CRUBA');
    $pdf->SetAuthor('Biblioteca CRUBA');
    $pdf->SetTitle('Reporte de Préstamos');
    $pdf->SetSubject('Reporte de Préstamos de Libros');
    $pdf->SetKeywords('PDF, CRUBA, Biblioteca, Préstamos');

    $pdf->setHeaderData('', 0, 'Biblioteca CRUBA', 'Reporte de Préstamos de Libros');
    $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
    $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
    $pdf->SetMargins(15, 25, 15);
    $pdf->SetHeaderMargin(10);
    $pdf->SetFooterMargin(10);
    $pdf->SetAutoPageBreak(TRUE, 25);

    $pdf->AddPage();

    // Contenido del reporte
    $html = '<h2>Reporte de Préstamos de Libros</h2>';
    $html .= '<p><strong>Fecha:</strong> ' . date('d/m/Y H:i:s') . '</p>';
    $html .= '<p><strong>Total registros:</strong> ' . count($solicitudes) . '</p>';

    if(!empty($filtro_estado)) {
        $html .= '<p><strong>Filtrado por estado:</strong> ' . htmlspecialchars($filtro_estado) . '</p>';
    }

    if(!empty($filtro_busqueda)) {
        $html .= '<p><strong>Filtrado por búsqueda:</strong> ' . htmlspecialchars($filtro_busqueda) . '</p>';
    }

    $html .= '<table border="1" cellpadding="5">
                <tr>
                    <th>Estudiante</th>
                    <th>Cédula</th>
                    <th>Libro</th>
                    <th>Fecha Solicitud</th>
                    <th>Estado</th>
                    <th>Fecha Préstamo</th>
                    <th>Fecha Devolución</th>
                </tr>';

    foreach($solicitudes as $solicitud) {
        $html .= '<tr>
                    <td>' . htmlspecialchars($solicitud['estudiante']) . '</td>
                    <td>' . htmlspecialchars($solicitud['cedula']) . '</td>
                    <td>' . htmlspecialchars($solicitud['libro']) . '</td>
                    <td>' . date('d/m/Y H:i', strtotime($solicitud['fecha_solicitud'])) . '</td>
                    <td>' . htmlspecialchars($solicitud['estado']) . '</td>
                    <td>' . ($solicitud['fecha_prestamo'] ? date('d/m/Y', strtotime($solicitud['fecha_prestamo'])) : '--') . '</td>
                    <td>' . ($solicitud['fecha_devolucion_real'] ? date('d/m/Y', strtotime($solicitud['fecha_devolucion_real'])) : 
                            ($solicitud['fecha_devolucion_esperada'] ? date('d/m/Y', strtotime($solicitud['fecha_devolucion_esperada'])) : '--')) . '</td>
                </tr>';
    }

    $html .= '</table>';

    $pdf->writeHTML($html, true, false, true, false, '');

    $pdf->Output('reporte_prestamos_' . date('Ymd_His') . '.pdf', 'D');
    exit;
}

// Generar reporte en Excel
if($tipo_reporte == 'excel') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="reporte_prestamos_' . date('Ymd_His') . '.xls"');
    header('Cache-Control: max-age=0');
    
    echo '<table border="1">
            <tr>
                <th colspan="7">Biblioteca CRUBA - Reporte de Préstamos de Libros</th>
            </tr>
            <tr>
                <th colspan="7">Fecha: ' . date('d/m/Y H:i:s') . '</th>
            </tr>';
    
    if(!empty($filtro_estado)) {
        echo '<tr>
                <th colspan="7">Filtrado por estado: ' . htmlspecialchars($filtro_estado) . '</th>
            </tr>';
    }
    
    if(!empty($filtro_busqueda)) {
        echo '<tr>
                <th colspan="7">Filtrado por búsqueda: ' . htmlspecialchars($filtro_busqueda) . '</th>
            </tr>';
    }
    
    echo '<tr>
            <th>Estudiante</th>
            <th>Cédula</th>
            <th>Libro</th>
            <th>Fecha Solicitud</th>
            <th>Estado</th>
            <th>Fecha Préstamo</th>
            <th>Fecha Devolución</th>
        </tr>';
    
    foreach($solicitudes as $solicitud) {
        echo '<tr>
                <td>' . htmlspecialchars($solicitud['estudiante']) . '</td>
                <td>' . htmlspecialchars($solicitud['cedula']) . '</td>
                <td>' . htmlspecialchars($solicitud['libro']) . '</td>
                <td>' . date('d/m/Y H:i', strtotime($solicitud['fecha_solicitud'])) . '</td>
                <td>' . htmlspecialchars($solicitud['estado']) . '</td>
                <td>' . ($solicitud['fecha_prestamo'] ? date('d/m/Y', strtotime($solicitud['fecha_prestamo'])) : '--') . '</td>
                <td>' . ($solicitud['fecha_devolucion_real'] ? date('d/m/Y', strtotime($solicitud['fecha_devolucion_real'])) : 
                        ($solicitud['fecha_devolucion_esperada'] ? date('d/m/Y', strtotime($solicitud['fecha_devolucion_esperada'])) : '--')) . '</td>
            </tr>';
    }
    
    echo '</table>';
    exit;
}
?>