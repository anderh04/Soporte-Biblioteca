-- phpMyAdmin SQL Dump
-- version 4.9.1
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost
-- Tiempo de generación: 09-06-2025 a las 16:30:59
-- Versión del servidor: 8.0.17
-- Versión de PHP: 7.3.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `cruba_library`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `administradores`
--

CREATE TABLE `administradores` (
  `id_admin` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `fecha_creacion` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `administradores`
--

INSERT INTO `administradores` (`id_admin`, `nombre`, `email`, `fecha_creacion`) VALUES
(1, 'María González', 'maria.gonzalez@biblioteca.edu', '2025-06-02 18:58:00'),
(2, 'Carlos Mendoza', 'carlos.mendoza@biblioteca.edu', '2025-06-02 18:58:00'),
(3, 'Laura Jiménez', 'laura.jimenez@biblioteca.edu', '2025-06-02 18:58:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asistencia_biblioteca`
--

CREATE TABLE `asistencia_biblioteca` (
  `id_asistencia` int(11) NOT NULL,
  `id_estudiante` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `hora_entrada` time NOT NULL,
  `hora_salida` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `asistencia_biblioteca`
--

INSERT INTO `asistencia_biblioteca` (`id_asistencia`, `id_estudiante`, `fecha`, `hora_entrada`, `hora_salida`) VALUES
(1, 1, '2023-10-01', '08:30:00', '12:45:00'),
(2, 2, '2023-10-01', '09:15:00', '11:30:00'),
(3, 3, '2023-10-02', '10:00:00', '14:20:00'),
(4, 4, '2023-10-02', '14:30:00', '17:45:00'),
(5, 5, '2023-10-03', '08:00:00', '10:30:00'),
(6, 6, '2023-10-03', '16:00:00', '18:15:00'),
(7, 1, '2025-06-02', '23:05:01', '23:06:36'),
(8, 1, '2025-06-02', '23:07:18', '23:09:03'),
(9, 1, '2025-06-02', '23:26:09', '23:34:34'),
(10, 1, '2025-06-02', '23:34:48', NULL),
(11, 1, '2025-06-04', '15:38:12', NULL),
(12, 2, '2025-06-06', '21:21:02', '21:25:34'),
(13, 2, '2025-06-06', '21:25:46', '21:27:03'),
(14, 1, '2025-06-06', '21:27:34', '21:27:52'),
(15, 2, '2025-06-06', '21:28:17', '21:28:32'),
(16, 3, '2025-06-06', '21:32:49', '21:33:05'),
(17, 1, '2025-06-06', '21:37:18', NULL),
(18, 1, '2025-06-07', '03:12:48', '03:14:31'),
(19, 1, '2025-06-09', '15:24:40', '15:25:07'),
(20, 1, '2025-06-09', '15:25:50', '15:26:00'),
(21, 1, '2025-06-09', '15:26:29', '15:28:39'),
(22, 3, '2025-06-09', '15:28:47', '15:54:40'),
(23, 1, '2025-06-09', '15:54:48', '15:56:52'),
(24, 1, '2025-06-09', '16:08:59', '16:14:45'),
(25, 1, '2025-06-09', '16:16:11', '16:23:35');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias_libros`
--

CREATE TABLE `categorias_libros` (
  `id_categoria` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `descripcion` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `categorias_libros`
--

INSERT INTO `categorias_libros` (`id_categoria`, `nombre`, `descripcion`) VALUES
(1, 'Ciencias', 'Libros sobre ciencias naturales y exactas'),
(2, 'Literatura', 'Obras literarias clásicas y contemporáneas'),
(3, 'Tecnología', 'Libros sobre tecnología e informática'),
(4, 'Salud', 'Libros sobre medicina y ciencias de la salud'),
(5, 'Historia', 'Libros sobre eventos históricos'),
(6, 'Negocios', 'Libros sobre administración y economía');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estudiantes`
--

CREATE TABLE `estudiantes` (
  `id_estudiante` int(11) NOT NULL,
  `foto` text NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `cedula` varchar(20) NOT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `tipo_sangre` varchar(5) DEFAULT NULL,
  `facultad` varchar(50) DEFAULT NULL,
  `escuela` varchar(50) DEFAULT NULL,
  `genero` enum('Masculino','Femenino') DEFAULT NULL,
  `correo` varchar(100) NOT NULL,
  `activo` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `estudiantes`
--

INSERT INTO `estudiantes` (`id_estudiante`, `foto`, `nombre`, `cedula`, `fecha_nacimiento`, `tipo_sangre`, `facultad`, `escuela`, `genero`, `correo`, `activo`) VALUES
(1, '', 'Juan Pérez', '001-1234567-8', '1998-05-15', 'O+', 'Ingeniería', 'Ing. Software', 'Masculino', 'juan.perez@cruba.edu.do', 1),
(2, '', 'María Rodríguez', '002-9876543-1', '1999-07-22', 'A-', 'Ciencias de la Salud', 'Medicina', 'Femenino', 'maria.rodriguez@cruba.edu.do', 1),
(3, '', 'Carlos García', '003-4567890-2', '2000-02-10', 'B+', 'Ciencias Económicas', 'Administración', 'Masculino', 'carlos.garcia@cruba.edu.do', 1),
(4, '', 'Ana Martínez', '004-3216549-7', '1997-11-30', 'AB+', 'Humanidades', 'Psicología', 'Femenino', 'ana.martinez@cruba.edu.do', 1),
(5, '', 'Luis Sánchez', '005-7891234-5', '1998-08-17', 'O-', 'Ingeniería', 'Ing. Civil', 'Masculino', 'luis.sanchez@cruba.edu.do', 1),
(6, '', 'Sofía Ramírez', '006-6549873-2', '1999-04-05', 'A+', 'Ciencias de la Salud', 'Enfermería', 'Femenino', 'sofia.ramirez@cruba.edu.do', 1),
(7, '', '', '', NULL, NULL, NULL, NULL, NULL, '', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_modificaciones`
--

CREATE TABLE `historial_modificaciones` (
  `id_historial` int(11) NOT NULL,
  `tipo` enum('asistencia','computadora','prestamo') NOT NULL,
  `id_registro` int(11) NOT NULL,
  `accion` varchar(50) NOT NULL,
  `motivo` text NOT NULL,
  `id_admin` int(11) NOT NULL,
  `fecha` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `historial_modificaciones`
--

INSERT INTO `historial_modificaciones` (`id_historial`, `tipo`, `id_registro`, `accion`, `motivo`, `id_admin`, `fecha`) VALUES
(1, 'computadora', 12, 'Registró fin de uso', 'te haz pasado del tiempo', 1, '2025-06-07 03:05:47');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `libros`
--

CREATE TABLE `libros` (
  `id_libro` int(11) NOT NULL,
  `titulo` varchar(100) NOT NULL,
  `autor` varchar(100) NOT NULL,
  `id_categoria` int(11) NOT NULL,
  `cantidad_disponible` int(11) NOT NULL DEFAULT '1',
  `anio_publicacion` int(11) DEFAULT NULL,
  `isbn` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `libros`
--

INSERT INTO `libros` (`id_libro`, `titulo`, `autor`, `id_categoria`, `cantidad_disponible`, `anio_publicacion`, `isbn`) VALUES
(1, 'Física Universitaria', 'Sears & Zemansky', 1, 5, 2016, '978-6073234567'),
(2, 'Cien años de soledad', 'Gabriel García Márquez', 2, 3, 1967, '978-0307474728'),
(3, 'Python para todos', 'Charles Severance', 3, 2, 2016, '978-0983555890'),
(4, 'Anatomía de Gray', 'Henry Gray', 4, 2, 2015, '978-8445822196'),
(5, 'Breve historia del mundo', 'Ernst Gombrich', 5, 3, 1999, '978-8430606171'),
(6, 'Padre Rico, Padre Pobre', 'Robert Kiyosaki', 6, 6, 1997, '978-1603961813');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `prestamos_libros`
--

CREATE TABLE `prestamos_libros` (
  `id_prestamo` int(11) NOT NULL,
  `id_solicitud` int(11) NOT NULL,
  `fecha_prestamo` datetime NOT NULL,
  `fecha_devolucion_esperada` datetime NOT NULL,
  `fecha_devolucion_real` datetime DEFAULT NULL,
  `estado` enum('Prestado','Devuelto','Atrasado','Perdido') DEFAULT 'Prestado',
  `observaciones` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `prestamos_libros`
--

INSERT INTO `prestamos_libros` (`id_prestamo`, `id_solicitud`, `fecha_prestamo`, `fecha_devolucion_esperada`, `fecha_devolucion_real`, `estado`, `observaciones`) VALUES
(1, 1, '2023-11-02 09:15:00', '2023-11-16 09:15:00', '2023-11-15 14:30:00', 'Devuelto', 'Libro en buen estado'),
(2, 2, '2023-11-03 10:20:00', '2023-11-17 10:20:00', '2023-11-20 11:45:00', 'Atrasado', 'Devuelto con 3 días de retraso'),
(3, 3, '2023-11-04 08:45:00', '2023-11-18 08:45:00', '2025-06-17 00:00:00', 'Prestado', 'Préstamo vigente'),
(4, 4, '2023-11-06 14:10:00', '2023-11-20 14:10:00', NULL, 'Prestado', 'Renovado por 1 semana'),
(5, 6, '2023-11-07 10:30:00', '2023-11-21 10:30:00', '2023-11-21 09:15:00', 'Devuelto', 'Sin observaciones'),
(6, 2, '2023-12-01 09:00:00', '2023-12-15 09:00:00', NULL, 'Prestado', 'Segundo préstamo del mismo libro');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `solicitudes_libros`
--

CREATE TABLE `solicitudes_libros` (
  `id_solicitud` int(11) NOT NULL,
  `id_estudiante` int(11) NOT NULL,
  `id_libro` int(11) NOT NULL,
  `fecha_solicitud` datetime NOT NULL,
  `fecha_aprobacion` datetime DEFAULT NULL,
  `estado` enum('Pendiente','Aprobada','Rechazada','Entregado','Devuelto') DEFAULT 'Pendiente',
  `motivo` text,
  `respuesta` text,
  `id_aprobador` int(11) DEFAULT NULL,
  `fecha_entrega` datetime DEFAULT NULL,
  `fecha_devolucion` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `solicitudes_libros`
--

INSERT INTO `solicitudes_libros` (`id_solicitud`, `id_estudiante`, `id_libro`, `fecha_solicitud`, `fecha_aprobacion`, `estado`, `motivo`, `respuesta`, `id_aprobador`, `fecha_entrega`, `fecha_devolucion`) VALUES
(1, 1, 3, '2023-10-01 08:15:00', '2023-10-01 08:30:00', 'Devuelto', 'Necesito para proyecto de programación', 'Aprobado para préstamo por 7 días', 3, '2025-06-23 00:00:00', '2025-06-27 00:00:00'),
(2, 2, 4, '2023-10-01 09:20:00', '2023-10-01 09:35:00', 'Devuelto', 'Estudio para examen de anatomía', 'Aprobado para préstamo por 5 días', NULL, NULL, NULL),
(3, 3, 1, '2023-10-02 10:05:00', '2023-10-02 10:20:00', 'Entregado', 'Referencia para tarea de física', 'Aprobado para préstamo por 3 días', NULL, NULL, NULL),
(4, 4, 2, '2023-10-02 14:40:00', '2023-10-02 14:55:00', 'Entregado', 'Lectura para clase de literatura', 'Aprobado para préstamo por 10 días', NULL, NULL, NULL),
(5, 5, 6, '2023-10-03 08:10:00', '2023-10-03 08:25:00', 'Aprobada', 'Investigación para proyecto de negocios', 'Aprobado para préstamo por 7 días', NULL, NULL, NULL),
(6, 6, 5, '2023-10-03 16:05:00', NULL, 'Pendiente', 'Interés personal en historia', NULL, NULL, NULL, NULL),
(7, 1, 4, '2025-06-04 15:40:25', NULL, 'Pendiente', 'bfukhjxuggk', NULL, NULL, NULL, NULL),
(8, 1, 4, '2025-06-07 03:12:15', NULL, 'Pendiente', 'tesis', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `uso_computadoras`
--

CREATE TABLE `uso_computadoras` (
  `id_uso` int(11) NOT NULL,
  `id_estudiante` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time DEFAULT NULL,
  `computadora_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `uso_computadoras`
--

INSERT INTO `uso_computadoras` (`id_uso`, `id_estudiante`, `fecha`, `hora_inicio`, `hora_fin`, `computadora_id`) VALUES
(1, 1, '2023-10-01', '09:00:00', '11:00:00', 5),
(2, 2, '2023-10-01', '10:30:00', '12:30:00', 7),
(3, 3, '2023-10-02', '08:45:00', '10:45:00', 3),
(4, 4, '2023-10-02', '13:00:00', '15:00:00', 1),
(5, 5, '2023-10-03', '14:00:00', '16:00:00', 2),
(6, 6, '2023-10-03', '15:30:00', '17:30:00', 4),
(7, 1, '2025-06-02', '23:06:00', '23:06:03', 10),
(8, 1, '2025-06-02', '23:18:49', '23:18:59', 11),
(9, 1, '2025-06-04', '15:38:44', '15:39:09', 1),
(10, 1, '2025-06-04', '15:39:32', '15:39:47', 4),
(11, 1, '2025-06-06', '22:12:41', '22:20:13', 6),
(12, 1, '2025-06-07', '02:55:31', '03:05:47', 1),
(13, 1, '2025-06-09', '16:11:51', '16:12:19', 5);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `administradores`
--
ALTER TABLE `administradores`
  ADD PRIMARY KEY (`id_admin`);

--
-- Indices de la tabla `asistencia_biblioteca`
--
ALTER TABLE `asistencia_biblioteca`
  ADD PRIMARY KEY (`id_asistencia`),
  ADD KEY `id_estudiante` (`id_estudiante`);

--
-- Indices de la tabla `categorias_libros`
--
ALTER TABLE `categorias_libros`
  ADD PRIMARY KEY (`id_categoria`);

--
-- Indices de la tabla `estudiantes`
--
ALTER TABLE `estudiantes`
  ADD PRIMARY KEY (`id_estudiante`),
  ADD UNIQUE KEY `cedula` (`cedula`);

--
-- Indices de la tabla `historial_modificaciones`
--
ALTER TABLE `historial_modificaciones`
  ADD PRIMARY KEY (`id_historial`),
  ADD KEY `id_admin` (`id_admin`);

--
-- Indices de la tabla `libros`
--
ALTER TABLE `libros`
  ADD PRIMARY KEY (`id_libro`),
  ADD KEY `id_categoria` (`id_categoria`);

--
-- Indices de la tabla `prestamos_libros`
--
ALTER TABLE `prestamos_libros`
  ADD PRIMARY KEY (`id_prestamo`),
  ADD KEY `id_solicitud` (`id_solicitud`);

--
-- Indices de la tabla `solicitudes_libros`
--
ALTER TABLE `solicitudes_libros`
  ADD PRIMARY KEY (`id_solicitud`),
  ADD KEY `id_estudiante` (`id_estudiante`),
  ADD KEY `id_libro` (`id_libro`),
  ADD KEY `id_aprobador` (`id_aprobador`);

--
-- Indices de la tabla `uso_computadoras`
--
ALTER TABLE `uso_computadoras`
  ADD PRIMARY KEY (`id_uso`),
  ADD KEY `id_estudiante` (`id_estudiante`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `administradores`
--
ALTER TABLE `administradores`
  MODIFY `id_admin` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `asistencia_biblioteca`
--
ALTER TABLE `asistencia_biblioteca`
  MODIFY `id_asistencia` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT de la tabla `categorias_libros`
--
ALTER TABLE `categorias_libros`
  MODIFY `id_categoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `estudiantes`
--
ALTER TABLE `estudiantes`
  MODIFY `id_estudiante` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `historial_modificaciones`
--
ALTER TABLE `historial_modificaciones`
  MODIFY `id_historial` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `libros`
--
ALTER TABLE `libros`
  MODIFY `id_libro` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `prestamos_libros`
--
ALTER TABLE `prestamos_libros`
  MODIFY `id_prestamo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `solicitudes_libros`
--
ALTER TABLE `solicitudes_libros`
  MODIFY `id_solicitud` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `uso_computadoras`
--
ALTER TABLE `uso_computadoras`
  MODIFY `id_uso` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `asistencia_biblioteca`
--
ALTER TABLE `asistencia_biblioteca`
  ADD CONSTRAINT `asistencia_biblioteca_ibfk_1` FOREIGN KEY (`id_estudiante`) REFERENCES `estudiantes` (`id_estudiante`);

--
-- Filtros para la tabla `historial_modificaciones`
--
ALTER TABLE `historial_modificaciones`
  ADD CONSTRAINT `historial_modificaciones_ibfk_1` FOREIGN KEY (`id_admin`) REFERENCES `administradores` (`id_admin`);

--
-- Filtros para la tabla `libros`
--
ALTER TABLE `libros`
  ADD CONSTRAINT `libros_ibfk_1` FOREIGN KEY (`id_categoria`) REFERENCES `categorias_libros` (`id_categoria`);

--
-- Filtros para la tabla `prestamos_libros`
--
ALTER TABLE `prestamos_libros`
  ADD CONSTRAINT `prestamos_libros_ibfk_1` FOREIGN KEY (`id_solicitud`) REFERENCES `solicitudes_libros` (`id_solicitud`);

--
-- Filtros para la tabla `solicitudes_libros`
--
ALTER TABLE `solicitudes_libros`
  ADD CONSTRAINT `solicitudes_libros_ibfk_1` FOREIGN KEY (`id_estudiante`) REFERENCES `estudiantes` (`id_estudiante`),
  ADD CONSTRAINT `solicitudes_libros_ibfk_2` FOREIGN KEY (`id_libro`) REFERENCES `libros` (`id_libro`),
  ADD CONSTRAINT `solicitudes_libros_ibfk_3` FOREIGN KEY (`id_aprobador`) REFERENCES `administradores` (`id_admin`);

--
-- Filtros para la tabla `uso_computadoras`
--
ALTER TABLE `uso_computadoras`
  ADD CONSTRAINT `uso_computadoras_ibfk_1` FOREIGN KEY (`id_estudiante`) REFERENCES `estudiantes` (`id_estudiante`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
