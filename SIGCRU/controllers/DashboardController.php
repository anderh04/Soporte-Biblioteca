<?php
require_once '../config/config.php';

class DashboardController
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function getEstudianteInfo($id_estudiante)
    {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM estudiantes WHERE id_estudiante = :id");
            $stmt->bindParam(':id', $id_estudiante);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Error al obtener información del estudiante: " . $e->getMessage());
        }
    }

    public function registrarSalidaBiblioteca($id_estudiante)
    {
        try {
            $fecha_actual = date('Y-m-d');
            $hora_actual = date('H:i:s');

            // Registrar salida de biblioteca
            $stmt = $this->conn->prepare("UPDATE asistencia_biblioteca 
                                        SET hora_salida = :hora_salida 
                                        WHERE id_estudiante = :id_estudiante 
                                        AND fecha = :fecha 
                                        AND hora_salida IS NULL");
            $stmt->bindParam(':hora_salida', $hora_actual);
            $stmt->bindParam(':id_estudiante', $id_estudiante);
            $stmt->bindParam(':fecha', $fecha_actual);
            $stmt->execute();

            // Registrar salida de computadoras
            $stmt = $this->conn->prepare("UPDATE uso_computadoras 
                                        SET hora_fin = :hora_fin 
                                        WHERE id_estudiante = :id_estudiante 
                                        AND fecha = :fecha 
                                        AND hora_fin IS NULL");
            $stmt->bindParam(':hora_fin', $hora_actual);
            $stmt->bindParam(':id_estudiante', $id_estudiante);
            $stmt->bindParam(':fecha', $fecha_actual);
            $stmt->execute();

            return true;
        } catch (PDOException $e) {
            throw new Exception("Error al registrar salida: " . $e->getMessage());
        }
    }

    public function getAsistenciaHoy($id_estudiante)
    {
        try {
            $fecha_actual = date('Y-m-d');
            $stmt = $this->conn->prepare("SELECT * FROM asistencia_biblioteca 
                                        WHERE id_estudiante = :id_estudiante 
                                        AND fecha = :fecha");
            $stmt->bindParam(':id_estudiante', $id_estudiante);
            $stmt->bindParam(':fecha', $fecha_actual);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Error al obtener asistencia: " . $e->getMessage());
        }
    }

    public function getUsoComputadorasHoy($id_estudiante)
    {
        try {
            $fecha_actual = date('Y-m-d');
            $stmt = $this->conn->prepare("SELECT * FROM uso_computadoras 
                                        WHERE id_estudiante = :id_estudiante 
                                        AND fecha = :fecha");
            $stmt->bindParam(':id_estudiante', $id_estudiante);
            $stmt->bindParam(':fecha', $fecha_actual);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Error al obtener uso de computadoras: " . $e->getMessage());
        }
    }
}