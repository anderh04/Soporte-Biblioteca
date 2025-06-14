<?php
require_once "../config/config.php";

class ComputadorasController
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function getUsoActual($id_estudiante)
    {
        try {
            $fecha_actual = date('Y-m-d');
            $stmt = $this->conn->prepare("SELECT * FROM uso_computadoras 
                                        WHERE id_estudiante = :id_estudiante 
                                        AND fecha = :fecha 
                                        AND hora_fin IS NULL");
            $stmt->bindParam(':id_estudiante', $id_estudiante);
            $stmt->bindParam(':fecha', $fecha_actual);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Error al verificar uso actual: " . $e->getMessage());
        }
    }

    public function iniciarUso($id_estudiante, $computadora_id)
    {
        try {
            $fecha_actual = date('Y-m-d');
            $hora_actual = date('H:i:s');

            // Verificar si la computadora está disponible
            $stmt = $this->conn->prepare("SELECT * FROM uso_computadoras 
                                        WHERE computadora_id = :computadora_id 
                                        AND fecha = :fecha 
                                        AND hora_fin IS NULL");
            $stmt->bindParam(':computadora_id', $computadora_id);
            $stmt->bindParam(':fecha', $fecha_actual);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                throw new Exception("La computadora #$computadora_id ya está en uso.");
            }

            // Registrar uso
            $insert = $this->conn->prepare("INSERT INTO uso_computadoras 
                                        (id_estudiante, fecha, hora_inicio, computadora_id) 
                                        VALUES (:id_estudiante, :fecha, :hora_inicio, :computadora_id)");
            $insert->bindParam(':id_estudiante', $id_estudiante);
            $insert->bindParam(':fecha', $fecha_actual);
            $insert->bindParam(':hora_inicio', $hora_actual);
            $insert->bindParam(':computadora_id', $computadora_id);
            $insert->execute();

            return true;
        } catch (PDOException $e) {
            throw new Exception("Error al iniciar uso de computadora: " . $e->getMessage());
        }
    }

    public function finalizarUso($id_estudiante)
    {
        try {
            $fecha_actual = date('Y-m-d');
            $hora_actual = date('H:i:s');

            // Finalizar uso
            $update = $this->conn->prepare("UPDATE uso_computadoras 
                                        SET hora_fin = :hora_fin 
                                        WHERE id_estudiante = :id_estudiante 
                                        AND fecha = :fecha 
                                        AND hora_fin IS NULL");
            $update->bindParam(':hora_fin', $hora_actual);
            $update->bindParam(':id_estudiante', $id_estudiante);
            $update->bindParam(':fecha', $fecha_actual);
            $update->execute();

            return true;
        } catch (PDOException $e) {
            throw new Exception("Error al finalizar uso de computadora: " . $e->getMessage());
        }
    }

    public function getHistorialUso($id_estudiante, $limite = 10)
    {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM uso_computadoras 
                                        WHERE id_estudiante = :id_estudiante
                                        ORDER BY fecha DESC, hora_inicio DESC
                                        LIMIT :limite");
            $stmt->bindParam(':id_estudiante', $id_estudiante);
            $stmt->bindParam(':limite', $limite, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Error al obtener historial de uso: " . $e->getMessage());
        }
    }
}