<?php
require_once "../config/config.php";

class LibrosController
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function getCategorias()
    {
        try {
            $stmt = $this->conn->query("SELECT * FROM categorias_libros ORDER BY nombre");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Error al obtener categorías: " . $e->getMessage());
        }
    }

    public function buscarLibros($busqueda = null, $categoria = null)
    {
        try {
            $where = "WHERE l.cantidad_disponible > 0";
            $params = [];

            if ($busqueda && !empty($busqueda)) {
                $busqueda = "%" . $busqueda . "%";
                $where .= " AND (l.titulo LIKE :busqueda OR l.autor LIKE :busqueda)";
                $params[':busqueda'] = $busqueda;
            }

            if ($categoria && !empty($categoria)) {
                $where .= " AND l.id_categoria = :categoria";
                $params[':categoria'] = $categoria;
            }

            $sql = "SELECT l.*, c.nombre as categoria 
                    FROM libros l
                    JOIN categorias_libros c ON l.id_categoria = c.id_categoria
                    $where
                    ORDER BY l.titulo";

            $stmt = $this->conn->prepare($sql);

            foreach ($params as $key => &$val) {
                $stmt->bindParam($key, $val);
            }

            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Error al buscar libros: " . $e->getMessage());
        }
    }

    public function solicitarLibro($id_estudiante, $id_libro, $motivo)
    {
        try {
            // Verificar disponibilidad
            $stmt = $this->conn->prepare("SELECT cantidad_disponible FROM libros WHERE id_libro = :id_libro");
            $stmt->bindParam(':id_libro', $id_libro);
            $stmt->execute();
            $libro = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$libro || $libro['cantidad_disponible'] <= 0) {
                throw new Exception("El libro no está disponible actualmente.");
            }

            // Crear solicitud
            $fecha_actual = date('Y-m-d H:i:s');
            $insert = $this->conn->prepare("INSERT INTO solicitudes_libros 
                                        (id_estudiante, id_libro, fecha_solicitud, motivo, estado) 
                                        VALUES (:id_estudiante, :id_libro, :fecha_solicitud, :motivo, 'Pendiente')");
            $insert->bindParam(':id_estudiante', $id_estudiante);
            $insert->bindParam(':id_libro', $id_libro);
            $insert->bindParam(':fecha_solicitud', $fecha_actual);
            $insert->bindParam(':motivo', $motivo);
            $insert->execute();

            // Reducir cantidad disponible
            $update = $this->conn->prepare("UPDATE libros SET cantidad_disponible = cantidad_disponible - 1 WHERE id_libro = :id_libro");
            $update->bindParam(':id_libro', $id_libro);
            $update->execute();

            return true;
        } catch (PDOException $e) {
            throw new Exception("Error al procesar la solicitud: " . $e->getMessage());
        }
    }
}