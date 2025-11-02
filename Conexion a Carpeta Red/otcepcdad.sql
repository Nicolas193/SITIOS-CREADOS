-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 18-02-2024 a las 18:32:34
-- Versión del servidor: 10.4.28-MariaDB
-- Versión de PHP: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `otcepcdad`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estadotarea`
--

CREATE TABLE `estadotarea` (
  `id` int(11) NOT NULL,
  `estado` varchar(250) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `estadotarea`
--

INSERT INTO `estadotarea` (`id`, `estado`) VALUES
(0, 'proceso'),
(1, 'Estado Finalizado'),
(2, 'Devolucion'),
(3, 'Devolucion Finalizado'),
(4, 'Devolver Finaliza'),
(5, 'Derivar'),
(6, 'Corregir');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `registrodetareas`
--

CREATE TABLE `registrodetareas` (
  `id` int(11) NOT NULL,
  `fecha_solicitud` date DEFAULT NULL,
  `responsable` varchar(255) DEFAULT NULL,
  `tipo_tarea` varchar(255) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `archivos` text DEFAULT NULL,
  `archivos2` text DEFAULT NULL,
  `dirigido_a` varchar(50) DEFAULT NULL,
  `campo1` int(255) DEFAULT NULL,
  `campo2` varchar(255) DEFAULT NULL,
  `id_persona_asignada` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `registrodetareas`
--

INSERT INTO `registrodetareas` (`id`, `fecha_solicitud`, `responsable`, `tipo_tarea`, `descripcion`, `archivos`, `archivos2`, `dirigido_a`, `campo1`, `campo2`, `id_persona_asignada`) VALUES
(180, '2024-02-16', 'Indistinto', 'Tarea', 'frerfe', '1708128058_', '1708128058_', 'Lucas', 0, '0', '001_Lucas'),
(182, '0000-00-00', 'Indistinto', 'Tarea', '', '1708130283_', '1708130283_', 'Nicolas', 3, '1', '001_Lucas'),
(183, '0000-00-00', 'Indistinto', 'Tarea', 'hthfghgf', '1708132846_1708122397_Implementación SAS VI para  Oficina de Transparencia.docx', '1708132846_Generacion_Redes_2.sas.txt', 'Belen', 5, '0', '001_Lucas'),
(184, '2024-02-23', 'Indistinto', 'Tarea', 'ytyyty', '1708132885_', '1708132885_', 'Nicolas', 3, '1', '001_Lucas'),
(185, '2024-02-17', 'Indistinto', 'Tarea', 'hgffghgfhghfghf fghhfgfghgfhgfh fghfghghfghfghf IUIIIIIIIIIIII', '1708189536_', '1708189536_', 'Santiago', 0, '0', '001_Santiago'),
(186, '2024-02-18', 'Indistinto', 'Tarea', 'fghgghfghcf fghghfhffghghf hfghfghgsrasased adserrd jghghff gfd', '1708189599_', '1708189599_', 'Santiago', 2, '1', '001_Santiago'),
(187, '0000-00-00', 'Indistinto', 'Tarea', '', '1708189616_', '1708189616_', 'Nicolas', 1, '0', '001_Lucas'),
(188, '0000-00-00', 'Indistinto', 'Tarea', '', '1708189626_', '1708189626_', 'Santiago', 1, '0', '001_Santiago');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `contraseña` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `usuario`, `contraseña`) VALUES
(1, 'GermanArcos', 'Ministerio.2024'),
(2, 'LucasPalacio', 'Ministerio.2024'),
(3, 'CristianAdrian', 'Ministerio.2024'),
(4, 'NicolasMaciel', 'Ministerio.2024'),
(5, 'MariaBelen', 'Ministerio.2024'),
(6, 'SantiagoChamorro', 'Ministerio.2024');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `registrodetareas`
--
ALTER TABLE `registrodetareas`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `registrodetareas`
--
ALTER TABLE `registrodetareas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=189;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
