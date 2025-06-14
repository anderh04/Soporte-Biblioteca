-- Creación de la base de datos
CREATE DATABASE IF NOT EXISTS cruba_library;
USE cruba_library;

-- Tabla de estudiantes (basada en el carnet)
CREATE TABLE estudiantes (
    id_estudiante INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    cedula VARCHAR(20) NOT NULL UNIQUE,
    fecha_nacimiento DATE,
    tipo_sangre VARCHAR(5),
    facultad VARCHAR(50),
    escuela VARCHAR(50),
    genero ENUM('Masculino', 'Femenino'),
    correo VARCHAR(100) NOT NULL,
    activo BOOLEAN DEFAULT TRUE
);

-- Tabla de categorías de libros
CREATE TABLE categorias_libros (
    id_categoria INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    descripcion TEXT
);

-- Tabla de libros
CREATE TABLE libros (
    id_libro INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(100) NOT NULL,
    autor VARCHAR(100) NOT NULL,
    id_categoria INT NOT NULL,
    cantidad_disponible INT NOT NULL DEFAULT 1,
    anio_publicacion INT,
    isbn VARCHAR(20),
    FOREIGN KEY (id_categoria) REFERENCES categorias_libros(id_categoria)
);

-- Tabla de asistencia a la biblioteca
CREATE TABLE asistencia_biblioteca (
    id_asistencia INT AUTO_INCREMENT PRIMARY KEY,
    id_estudiante INT NOT NULL,
    fecha DATE NOT NULL,
    hora_entrada TIME NOT NULL,
    hora_salida TIME,
    FOREIGN KEY (id_estudiante) REFERENCES estudiantes(id_estudiante)
);

-- Tabla de uso de computadoras
CREATE TABLE uso_computadoras (
    id_uso INT AUTO_INCREMENT PRIMARY KEY,
    id_estudiante INT NOT NULL,
    fecha DATE NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin TIME,
    computadora_id INT NOT NULL,
    FOREIGN KEY (id_estudiante) REFERENCES estudiantes(id_estudiante)
);

-- Tabla de solicitudes de libros
CREATE TABLE solicitudes_libros (
    id_solicitud INT AUTO_INCREMENT PRIMARY KEY,
    id_estudiante INT NOT NULL,
    id_libro INT NOT NULL,
    fecha_solicitud DATETIME NOT NULL,
    fecha_aprobacion DATETIME,
    estado ENUM('Pendiente', 'Aprobada', 'Rechazada', 'Entregado', 'Devuelto') DEFAULT 'Pendiente',
    motivo TEXT,
    respuesta TEXT,
    FOREIGN KEY (id_estudiante) REFERENCES estudiantes(id_estudiante),
    FOREIGN KEY (id_libro) REFERENCES libros(id_libro)
);