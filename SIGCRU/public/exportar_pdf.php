<?php
require_once '../config/config.php';
session_start();

// Verificar si es administrador
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != 1) {
    header("Location: ../../index.php");
    exit;
}

// Obtener el tipo de exportación
$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';
// Generar código único para el reporte
$codigo_reporte = strtoupper(uniqid('REP-'));

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
            $titulo = "Reporte de Solicitudes";
            break;

        case 'estudiantes':
            $sql = "SELECT nombre, cedula, correo, carrera, telefono 
                    FROM estudiantes 
                    ORDER BY nombre";

            $stmt = $conn->query($sql);
            $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $cabeceras = ['Nombre', 'Cédula', 'Correo', 'Carrera', 'Teléfono'];
            $titulo = "Reporte de Estudiantes";
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
            $titulo = "Reporte de Préstamos";
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
            $titulo = "Reporte de Uso de Computadoras";
            break;

        default:
            throw new Exception("Tipo de exportación no válido");
    }
} catch (Exception $e) {
    $_SESSION['error'] = "Error al exportar: " . $e->getMessage();
    header("Location: admin_prestamos.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title><?php echo $titulo; ?></title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.7.0/jspdf.plugin.autotable.min.js"></script>
    <style>
    body {
        font-family: Arial, sans-serif;
        margin: 20px;
    }

    .header {
        text-align: center;
        margin-bottom: 20px;
    }

    .header h1 {
        color: #2c3e50;
        margin: 0;
    }

    .header p {
        color: #7f8c8d;
        margin: 5px 0;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }

    th,
    td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: left;
    }

    th {
        background-color: #3498db;
        color: white;
    }

    tr:nth-child(even) {
        background-color: #f2f2f2;
    }

    .footer {
        text-align: center;
        margin-top: 20px;
        color: #7f8c8d;
        font-size: 12px;
    }
    </style>
</head>

<body>
    <div class="header">
        <h1><?php echo $titulo; ?></h1>
        <p>Fecha de generación: <?php echo date('d/m/Y H:i:s'); ?></p>
    </div>

    <table id="tablaDatos">
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

    <div class="footer">
        <p>Biblioteca CRUBA - Sistema de Gestión</p>
    </div>

    <script>
    window.onload = function() {
        var doc = new jspdf.jsPDF();

        // Agregar logo
        // Agregar logo
        doc.addImage('../src/img/logoUnachi.jpg', 'JPEG', 14, 10, 25, 25);

        // Título principal
        doc.setFontSize(16);
        doc.setTextColor(44, 62, 80);
        doc.text('Biblioteca CRUBA - Reporte', 45, 20);

        // Fecha
        doc.setFontSize(10);
        doc.setTextColor(44, 62, 80);
        doc.text('Fecha: <?php echo date('d/m/Y H:i:s'); ?>', 45, 28);

        // Código de reporte
        doc.setFontSize(10);
        doc.setTextColor(255, 0, 0);
        doc.text('Código de reporte: <?php echo $codigo_reporte; ?>', 45, 36);

        // Datos del administrador
        doc.setFontSize(10);
        doc.setTextColor(44, 62, 80);
        doc.text(
            'Generado por: <?php echo addslashes(htmlspecialchars($_SESSION["user_name"] ?? "Administrador")); ?>',
            45, 44);
        // Tabla
        doc.autoTable({
            html: '#tablaDatos',
            startY: 55,
            theme: 'grid',
            headStyles: {
                fillColor: [52, 152, 219],
                textColor: 255,
                fontSize: 10,
                fontStyle: 'bold'
            },
            bodyStyles: {
                fontSize: 9
            },
            alternateRowStyles: {
                fillColor: [245, 245, 245]
            },
            styles: {
                cellPadding: 3,
                lineColor: [200, 200, 200],
                lineWidth: 0.1
            },
            margin: {
                top: 55
            }
        });


        // Pie de página
        doc.setFontSize(8);
        doc.text('Biblioteca CRUBA - Sistema de Gestión', 14, doc.internal.pageSize.height - 10);
        // Guardar PDF
        doc.save('<?php echo strtolower(str_replace(' ', '_', $titulo)); ?>_<?php echo date('Y-m-d'); ?>.pdf');
        alert('PDF generado y descargado exitosamente. Código de reporte: <?php echo $codigo_reporte; ?>');
    };
    </script>
</body>

</html>