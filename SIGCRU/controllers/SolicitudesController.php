<?php
require_once "../config/config.php";

class SolicitudesController
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function getSolicitudesEstudiante($id_estudiante)
    {
        try {
            $stmt = $this->conn->prepare("SELECT s.*, l.titulo, l.autor, c.nombre as categoria 
                                        FROM solicitudes_libros s
                                        JOIN libros l ON s.id_libro = l.id_libro
                                        JOIN categorias_libros c ON l.id_categoria = c.id_categoria
                                        WHERE s.id_estudiante = :id_estudiante
                                        ORDER BY s.fecha_solicitud DESC");
            $stmt->bindParam(':id_estudiante', $id_estudiante);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Error al obtener solicitudes: " . $e->getMessage());
        }
    }

    public function getDetalleSolicitud($id_solicitud, $id_estudiante)
    {
        try {
            $stmt = $this->conn->prepare("SELECT s.*, l.titulo, l.autor, c.nombre as categoria 
                                        FROM solicitudes_libros s
                                        JOIN libros l ON s.id_libro = l.id_libro
                                        JOIN categorias_libros c ON l.id_categoria = c.id_categoria
                                        WHERE s.id_solicitud = :id_solicitud 
                                        AND s.id_estudiante = :id_estudiante");
            $stmt->bindParam(':id_solicitud', $id_solicitud);
            $stmt->bindParam(':id_estudiante', $id_estudiante);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Error al obtener detalle de solicitud: " . $e->getMessage());
        }
    }

    public function cancelarSolicitud($id_solicitud, $id_estudiante) {
        // Verificar que la solicitud pertenece al estudiante y está pendiente
        $sql = "SELECT estado FROM solicitudes WHERE id_solicitud = ? AND id_estudiante = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id_solicitud, $id_estudiante]);
        $solicitud = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$solicitud) {
            throw new Exception("Solicitud no encontrada o no pertenece al estudiante");
        }
        
        if ($solicitud['estado'] != 'Pendiente') {
            throw new Exception("Solo se pueden cancelar solicitudes pendientes");
        }
        
        // Actualizar estado a cancelado
        $sql = "UPDATE solicitudes SET estado = 'Cancelada', fecha_cancelacion = NOW() 
                WHERE id_solicitud = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$id_solicitud]);
    }

    public function marcarComoEntregado($id_solicitud, $id_estudiante) {
        // Verificar que la solicitud está en estado Disponible
        $sql = "SELECT estado, fecha_disponible FROM solicitudes 
                WHERE id_solicitud = ? AND id_estudiante = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id_solicitud, $id_estudiante]);
        $solicitud = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$solicitud) {
            throw new Exception("Solicitud no encontrada o no pertenece al estudiante");
        }
        
        if ($solicitud['estado'] != 'Disponible') {
            throw new Exception("El libro no está disponible para retiro");
        }
        
        // Verificar que no haya pasado la fecha límite de retiro
        $fechaDisponible = new DateTime($solicitud['fecha_disponible']);
        $fechaLimite = (clone $fechaDisponible)->modify('+3 days');
        $hoy = new DateTime();
        
        if ($hoy > $fechaLimite) {
            throw new Exception("El período de retiro ha expirado");
        }
        
        // Actualizar estado a Entregado y establecer fechas
        $sql = "UPDATE solicitudes SET 
                estado = 'Entregado', 
                fecha_entrega = NOW(), 
                fehca_aprobacion = DATE_ADD(NOW(), INTERVAL 7 DAY)
                WHERE id_solicitud = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$id_solicitud]);
    }
    

    public function getEstadoBadgeClass($estado)
    {
        $clases = [
        'Pendiente' => 'bg-warning',
        'Aceptada' => 'bg-success',
        'Rechazada' => 'bg-danger',
        'Disponible' => 'bg-info',
        'Entregado' => 'bg-primary',
        'Devuelto' => 'bg-secondary',
        'Cancelada' => 'bg-dark',
        'Vencido' => 'bg-danger'
    ];
    
    return $clases[$estado] ?? 'bg-secondary';

    }
}