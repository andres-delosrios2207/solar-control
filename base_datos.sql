CREATE DATABASE IF NOT EXISTS solar_control_db;
USE solar_control_db;

CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS citas (
    id_cita VARCHAR(20) PRIMARY KEY,
    cliente VARCHAR(100) NOT NULL,
    telefono VARCHAR(20),
    direccion VARCHAR(150),
    fecha DATE NOT NULL,
    hora TIME,
    tipo_cita VARCHAR(50),
    asesor_asignado VARCHAR(100),
    estudio_area VARCHAR(255),
    planos_casa VARCHAR(255),
    viable VARCHAR(20),
    checklist VARCHAR(255)
);