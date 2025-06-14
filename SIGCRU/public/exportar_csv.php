<?php
require_once '../config/config.php';
session_start();

// Verificar si es administrador
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != 1) {
    header("Location: ../../index.php");
    exit;
}

// Función mejorada para exportar a CSV
function exportarACSV($datos, $cabeceras, $nombreArchivo) {
    // Configurar headers para forzar a Excel a abrir correctamente el archivo
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Abrir el output
    $output = fopen('php://output', 'w');
    
    // Añadir BOM (Byte Order Mark) para UTF-8 (mejor compatibilidad con Excel)
    fwrite($output, "\xEF\xBB\xBF");
    
    // Escribir encabezados con formato mejorado
    fputcsv($output, $cabeceras);
    
    // Escribir datos con formato consistente
    foreach ($datos as $fila) {
        // Formatear fechas y campos especiales para mejor visualización en Excel
        $filaFormateada = array_map(function($valor) {
            // Si es una fecha conocida, darle formato estándar
            if (preg_match('/^\d{4}-\d{2}-\d{2}/', $valor)) {
                return date('d/m/Y H:i', strtotime($valor));
            }
            // Escapar fórmulas de Excel que podrían ser peligrosas
            if (in_array(substr($valor, 0, 1), ['=', '+', '-', '@'])) {
                return "'" . $valor;
            }
            return $valor;
        }, $fila);
        
        fputcsv($output, $filaFormateada);
    }

    fclose($output);
    exit;
}

function exportarAXLS($datos, $cabeceras, $nombreArchivo) {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"$nombreArchivo\"");
    header("Pragma: no-cache");
    header("Expires: 0");

    echo "<html xmlns:o=\"urn:schemas-microsoft-com:office:office\" xmlns:x=\"urn:schemas-microsoft-com:office:excel\">";
    echo "<head><meta charset=\"UTF-8\">";
    echo "<style>
          .watermark {
              position: absolute;
              opacity: 0.2;
              z-index: -1;
              top: 50%;
              left: 50%;
              transform: translate(-50%, -50%);
              width: 300px;
          }
          .content {
              position: relative;
              z-index: 1;
          }
          </style>";
    echo "</head>";
    echo "<body>";
    
    // Marca de agua (logo semitransparente al fondo)
    echo "<div class='watermark'>";
    echo "<img src='https://a.calameoassets.com/6553018/picture.jpg?_u=210211223634' alt='Logo' style='width: 100%;'>";
    echo "</div>";
    
    // Contenido principal
    echo "<div class='content'>";
    
    // Encabezado textual
    echo "<div style='text-align: center; margin-bottom: 20px;'>";
    echo "<h2>Centro Regional Universitario de Barú</h2>";
    echo "<h3>Reporte de Datos</h3>";
    echo "</div>";
    
    // Tabla de datos
    echo "<table border='1' style='width: 100%; border-collapse: collapse;'>";
    echo "<tr style='background-color: #0074D9; color: white;'>";
    foreach ($cabeceras as $cabecera) {
        echo "<th style='padding: 8px;'>" . htmlspecialchars($cabecera) . "</th>";
    }
    echo "</tr>";
    
    $contador = 0;
    foreach ($datos as $fila) {
        $color = ($contador % 2 == 0) ? "#FFFFFF" : "#F9F9F9";
        echo "<tr style='background-color: $color;'>";
        foreach ($fila as $valor) {
            if (preg_match('/^\d{4}-\d{2}-\d{2}/', $valor)) {
                $valor = date('d/m/Y H:i', strtotime($valor));
            }
            echo "<td style='padding: 6px;'>" . htmlspecialchars($valor) . "</td>";
        }
        echo "</tr>";
        $contador++;
    }
    
    echo "</table>";
    echo "</div>"; // Cierre del div.content
    echo "</body></html>";
    exit;
}

// Obtener parámetros
$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';
$descargar = isset($_GET['descargar']) ? true : false;
$formato = isset($_GET['formato']) ? $_GET['formato'] : 'excel'; // Por defecto Excel para mejor formato

try {
    switch ($tipo) {
        case 'solicitudes':
            $sql = "SELECT e.nombre as estudiante, e.cedula, l.titulo as libro, 
                           s.fecha_solicitud, s.estado, p.fecha_prestamo, 
                           p.fecha_devolucion_esperada, p.fecha_devolucion_real
                    FROM solicitudes_libros s
                    JOIN estudiantes e ON s.id_estudiante = e.id_estudiante
                    JOIN libros l ON s.id_libro = l.id_libro
                    LEFT JOIN prestamos_libros p ON s.id_solicitud = p.id_solicitud
                    ORDER BY s.fecha_solicitud DESC";

            $stmt = $conn->query($sql);
            $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $cabeceras = [
                'Estudiante',
                'Cédula',
                'Libro',
                'Fecha Solicitud',
                'Estado',
                'Fecha Préstamo',
                'Fecha Devolución Esperada',
                'Fecha Devolución Real'
            ];

            $nombreArchivo = 'solicitudes_' . date('Y-m-d') . ($formato == 'excel' ? '.xls' : '.csv');
            $titulo = 'Solicitudes de Libros';
            break;

        case 'estudiantes':
            $sql = "SELECT nombre, cedula, correo, carrera, telefono 
                    FROM estudiantes 
                    ORDER BY nombre";

            $stmt = $conn->query($sql);
            $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $cabeceras = ['Nombre', 'Cédula', 'Correo', 'Carrera', 'Teléfono'];
            $nombreArchivo = 'estudiantes_' . date('Y-m-d') . ($formato == 'excel' ? '.xls' : '.csv');
            $titulo = 'Estudiantes Registrados';
            break;

        case 'prestamos':
            $sql = "SELECT e.nombre as estudiante, l.titulo as libro, 
                           p.fecha_prestamo, p.fecha_devolucion_esperada, 
                           p.fecha_devolucion_real, p.estado
                    FROM prestamos_libros p
                    JOIN solicitudes_libros s ON p.id_solicitud = s.id_solicitud
                    JOIN estudiantes e ON s.id_estudiante = e.id_estudiante
                    JOIN libros l ON s.id_libro = l.id_libro
                    ORDER BY p.fecha_prestamo DESC";

            $stmt = $conn->query($sql);
            $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $cabeceras = [
                'Estudiante',
                'Libro',
                'Fecha Préstamo',
                'Fecha Devolución Esperada',
                'Fecha Devolución Real',
                'Estado'
            ];
            $nombreArchivo = 'prestamos_' . date('Y-m-d') . ($formato == 'excel' ? '.xls' : '.csv');
            $titulo = 'Préstamos de Libros';
            break;

        case 'computadoras':
            $sql = "SELECT e.nombre as estudiante, c.numero_computadora, 
                           u.hora_inicio, u.hora_fin, u.estado
                    FROM uso_computadoras u
                    JOIN estudiantes e ON u.id_estudiante = e.id_estudiante
                    JOIN computadoras c ON u.id_computadora = c.id_computadora
                    ORDER BY u.hora_inicio DESC";

            $stmt = $conn->query($sql);
            $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $cabeceras = [
                'Estudiante',
                'Número Computadora',
                'Hora Inicio',
                'Hora Fin',
                'Estado'
            ];
            $nombreArchivo = 'computadoras_' . date('Y-m-d') . ($formato == 'excel' ? '.xls' : '.csv');
            $titulo = 'Uso de Computadoras';
            break;

        default:
            throw new Exception("Tipo de exportación no válido");
    }

    if ($descargar) {
        if ($formato == 'excel') {
            exportarAXLS($datos, $cabeceras, $nombreArchivo);
        } else {
            exportarACSV($datos, $cabeceras, $nombreArchivo);
        }
    }
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exportar Datos - <?php echo $titulo; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    body {
        font-family: Arial, sans-serif;
        background: #f4f4f4;
        margin: 0;
        padding: 20px;
    }

    .container {
        max-width: 95%;
        margin: 0 auto;
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    h2 {
        color: #333;
        border-bottom: 2px solid #0074D9;
        padding-bottom: 10px;
    }

    .btn-group {
        margin: 20px 0;
    }

    .btn {
        display: inline-block;
        padding: 10px 15px;
        margin-right: 10px;
        border-radius: 4px;
        text-decoration: none;
        font-weight: bold;
        transition: all 0.3s;
    }

    .btn-excel {
        background: #1D6F42;
        color: white;
    }

    .btn-excel:hover {
        background: #165732;
    }

    .btn-csv {
        background: #0074D9;
        color: white;
    }

    .btn-csv:hover {
        background: #005bb5;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    th,
    td {
        border: 1px solid #ddd;
        padding: 12px;
        text-align: left;
    }

    th {
        background-color: #0074D9;
        color: white;
    }

    tr:nth-child(even) {
        background-color: #f2f2f2;
    }

    tr:hover {
        background-color: #e9e9e9;
    }

    .info {
        margin: 20px 0;
        padding: 15px;
        background: #e7f3fe;
        border-left: 6px solid #0074D9;
    }
    </style>
</head>

<body>
    <div class="container">
        <h2><?php echo $titulo; ?></h2>

        <div class="info">
            <p>Vista previa de los datos que se exportarán. Seleccione el formato deseado:</p>
        </div>

        <div class="btn-group">
            <a href="?tipo=<?php echo $tipo; ?>&descargar=1&formato=excel" class="btn btn-excel">
                <i class="fas fa-file-excel"></i> Exportar a Excel
            </a>
            <a href="?tipo=<?php echo $tipo; ?>&descargar=1&formato=csv" class="btn btn-csv">
                <i class="fas fa-file-csv"></i> Exportar a CSV
            </a>
        </div>

        <table>
            <thead>
                <tr>
                    <?php foreach ($cabeceras as $cabecera): ?>
                    <th><?php echo htmlspecialchars($cabecera); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($datos as $fila): ?>
                <tr>
                    <?php foreach ($fila as $valor): ?>
                    <td><?php echo htmlspecialchars($valor); ?></td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>

</html>
<?php
} catch (Exception $e) {
    $_SESSION['error'] = "Error al exportar: " . $e->getMessage();
    header("Location: admin_prestamos.php");
    exit;
}