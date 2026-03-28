-- ============================================
-- BASE DE DATOS: notas
-- Centro Educativo La Florida
-- Sistema de Información para Administración de Notas
-- ============================================

CREATE DATABASE IF NOT EXISTS notas DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE notas;

-- Tabla genero
CREATE TABLE IF NOT EXISTS genero (
    idGenero INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    descripcion VARCHAR(15) NOT NULL,
    PRIMARY KEY (idGenero)
) ENGINE=InnoDB;

INSERT INTO genero (descripcion) VALUES ('Masculino'), ('Femenino');

-- Tabla documento
CREATE TABLE IF NOT EXISTS documento (
    idDocumento INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    tipoDocumento VARCHAR(20) NOT NULL,
    PRIMARY KEY (idDocumento)
) ENGINE=InnoDB;

INSERT INTO documento (tipoDocumento) VALUES ('Tarjeta de Identidad'), ('Cédula de Ciudadanía'), ('Pasaporte'), ('Registro Civil');

-- Tabla nivelusuario
CREATE TABLE IF NOT EXISTS nivelusuario (
    idNivel INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    descripcion VARCHAR(45) NOT NULL,
    PRIMARY KEY (idNivel)
) ENGINE=InnoDB;

INSERT INTO nivelusuario (descripcion) VALUES ('Administrador'), ('Docente'), ('Estudiante');

-- Tabla estado_actual
CREATE TABLE IF NOT EXISTS estado_actual (
    idEstado INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    descripcion VARCHAR(15) NOT NULL,
    PRIMARY KEY (idEstado)
) ENGINE=InnoDB;

INSERT INTO estado_actual (descripcion) VALUES ('Activo'), ('Retirado'), ('Graduado');

-- Tabla personas
CREATE TABLE IF NOT EXISTS personas (
    identificacion INT(10) UNSIGNED NOT NULL,
    tipoDocumento INT(10) UNSIGNED DEFAULT NULL,
    primerNombre VARCHAR(15) NOT NULL,
    segundoNombre VARCHAR(15) DEFAULT NULL,
    primerApellido VARCHAR(15) NOT NULL,
    segundoApellido VARCHAR(15) DEFAULT NULL,
    fechaNacimiento DATE DEFAULT NULL,
    lugarNacimiento VARCHAR(45) DEFAULT NULL,
    direccion VARCHAR(45) DEFAULT NULL,
    idUbicacion INT(10) UNSIGNED DEFAULT NULL,
    ciudad VARCHAR(15) DEFAULT NULL,
    departamento VARCHAR(20) DEFAULT NULL,
    email VARCHAR(45) DEFAULT NULL,
    telefono VARCHAR(15) DEFAULT NULL,
    idGenero INT(10) UNSIGNED DEFAULT NULL,
    funcion VARCHAR(15) DEFAULT NULL,
    documento VARCHAR(20) DEFAULT NULL,
    PRIMARY KEY (identificacion),
    KEY tipo_doc (tipoDocumento),
    KEY ubicacion_per (idUbicacion),
    KEY genero_per (idGenero),
    CONSTRAINT fk_personas_genero FOREIGN KEY (idGenero) REFERENCES genero (idGenero) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_personas_documento FOREIGN KEY (tipoDocumento) REFERENCES documento (idDocumento) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB;

-- Tabla administrativos
CREATE TABLE IF NOT EXISTS administrativos (
    idpersonas INT(20) UNSIGNED NOT NULL,
    cargo VARCHAR(20) DEFAULT NULL,
    tipo_vinculacion VARCHAR(20) DEFAULT NULL,
    PRIMARY KEY (idpersonas),
    CONSTRAINT FK_administrativos_1 FOREIGN KEY (idpersonas) REFERENCES personas (identificacion) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB;

-- Tabla docentes
CREATE TABLE IF NOT EXISTS docentes (
    idDocentes INT(10) UNSIGNED NOT NULL,
    nivelEscalafon VARCHAR(15) DEFAULT NULL,
    tituloProf VARCHAR(45) DEFAULT NULL,
    dirGrupo INT(10) UNSIGNED DEFAULT NULL,
    sede VARCHAR(45) DEFAULT NULL,
    PRIMARY KEY (idDocentes),
    KEY grupos (dirGrupo),
    CONSTRAINT fk_docentes_personas FOREIGN KEY (idDocentes) REFERENCES personas (identificacion) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB;

-- Tabla estudiantes
CREATE TABLE IF NOT EXISTS estudiantes (
    identificacion INT(10) UNSIGNED NOT NULL,
    nombreAcudiente VARCHAR(45) DEFAULT NULL,
    direccionAcudiente VARCHAR(15) DEFAULT NULL,
    telAcudi VARCHAR(15) DEFAULT NULL,
    instProcedencia VARCHAR(45) DEFAULT NULL,
    PRIMARY KEY (identificacion),
    CONSTRAINT identificacion_est FOREIGN KEY (identificacion) REFERENCES personas (identificacion) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB;

-- Tabla usuarios
CREATE TABLE IF NOT EXISTS usuarios (
    idUsuario INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    idnivel INT(10) UNSIGNED NOT NULL,
    username VARCHAR(15) NOT NULL,
    contrasena VARCHAR(45) NOT NULL,
    PRIMARY KEY (idUsuario),
    KEY nivel (idnivel),
    KEY personas (idUsuario),
    CONSTRAINT fk_usuarios_nivel FOREIGN KEY (idnivel) REFERENCES nivelusuario (idNivel) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB;

-- Tabla sedes
CREATE TABLE IF NOT EXISTS sedes (
    codigoDane INT(10) UNSIGNED NOT NULL,
    nombre VARCHAR(20) NOT NULL,
    direccion VARCHAR(45) DEFAULT NULL,
    telefono VARCHAR(15) DEFAULT NULL,
    cod_inst INT(10) UNSIGNED DEFAULT NULL,
    PRIMARY KEY (codigoDane)
) ENGINE=InnoDB;

-- Tabla grado
CREATE TABLE IF NOT EXISTS grado (
    idgrado INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    descripcion VARCHAR(15) NOT NULL,
    PRIMARY KEY (idgrado)
) ENGINE=InnoDB;

INSERT INTO grado (descripcion) VALUES ('Primero'), ('Segundo'), ('Tercero'), ('Cuarto'), ('Quinto');

-- Tabla grupo
CREATE TABLE IF NOT EXISTS grupo (
    idGrupo INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(5) NOT NULL,
    idSede INT(10) UNSIGNED NOT NULL,
    idgrado INT(10) UNSIGNED NOT NULL,
    PRIMARY KEY (idGrupo),
    KEY grado (idgrado),
    KEY sede (idSede),
    CONSTRAINT fk_grupo_grado FOREIGN KEY (idgrado) REFERENCES grado (idgrado) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_grupo_sede FOREIGN KEY (idSede) REFERENCES sedes (codigoDane) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB;

-- Tabla materias
CREATE TABLE IF NOT EXISTS materias (
    idMateria INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(45) NOT NULL,
    PRIMARY KEY (idMateria)
) ENGINE=InnoDB;

INSERT INTO materias (nombre) VALUES 
('Matemáticas'), ('Español'), ('Ciencias Sociales'), ('Ciencias Naturales'),
('Tecnología e Informática'), ('Ed. Física'), ('Religión'), ('Ética y Valores'),
('Comportamiento'), ('Inglés'), ('Artística');

-- Tabla materias_grado
CREATE TABLE IF NOT EXISTS materias_grado (
    idgrado INT(10) UNSIGNED NOT NULL,
    idmateria INT(10) UNSIGNED NOT NULL,
    num_unidades INT(10) UNSIGNED NOT NULL,
    int_horaria INT(10) UNSIGNED DEFAULT NULL,
    PRIMARY KEY (idgrado, idmateria),
    KEY grado_asig (idgrado),
    KEY materia_asig (idmateria),
    CONSTRAINT fk_mg_grado FOREIGN KEY (idgrado) REFERENCES grado (idgrado) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_mg_materia FOREIGN KEY (idmateria) REFERENCES materias (idMateria) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB;

-- Tabla horario (asignación académica de docentes)
CREATE TABLE IF NOT EXISTS horario (
    idDocente INT(10) UNSIGNED NOT NULL,
    idMateria INT(10) UNSIGNED NOT NULL,
    idgrupo INT(10) UNSIGNED NOT NULL,
    año INT(10) UNSIGNED NOT NULL,
    descripcion VARCHAR(45) DEFAULT NULL,
    idgrado INT(10) UNSIGNED NOT NULL,
    idsede INT(10) UNSIGNED NOT NULL,
    PRIMARY KEY (idDocente, idMateria, idgrupo, año),
    KEY docente_hor (idDocente),
    KEY grado_hor (idgrado),
    KEY grupo_hor (idgrupo),
    KEY materia_hor (idMateria),
    KEY sede_hor (idsede),
    CONSTRAINT fk_hor_docente FOREIGN KEY (idDocente) REFERENCES docentes (idDocentes) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_hor_materia FOREIGN KEY (idMateria) REFERENCES materias (idMateria) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_hor_grupo FOREIGN KEY (idgrupo) REFERENCES grupo (idGrupo) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_hor_grado FOREIGN KEY (idgrado) REFERENCES grado (idgrado) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_hor_sede FOREIGN KEY (idsede) REFERENCES sedes (codigoDane) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB;

-- Tabla tipo_logro
CREATE TABLE IF NOT EXISTS tipologro (
    id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    descripcion VARCHAR(13) NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB;

INSERT INTO tipologro (descripcion) VALUES ('Académico'), ('Actitudinal'), ('Comportamental');

-- Tabla logros
CREATE TABLE IF NOT EXISTS logros (
    idlogro INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    idtipo INT(10) UNSIGNED NOT NULL,
    descripcion VARCHAR(500) NOT NULL,
    idMateria INT(10) UNSIGNED NOT NULL,
    idGrado INT(10) UNSIGNED NOT NULL,
    unidad INT(10) UNSIGNED NOT NULL,
    PRIMARY KEY (idlogro),
    KEY Grado_logro (idGrado),
    KEY materia_log (idMateria),
    KEY tipo_logro (idtipo),
    CONSTRAINT fk_logro_grado FOREIGN KEY (idGrado) REFERENCES grado (idgrado) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_logro_materia FOREIGN KEY (idMateria) REFERENCES materias (idMateria) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_logro_tipo FOREIGN KEY (idtipo) REFERENCES tipologro (id) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB;

-- Tabla desempenio
CREATE TABLE IF NOT EXISTS desempenio (
    idDesempenio INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    descripcion VARCHAR(10) NOT NULL,
    minimo FLOAT UNSIGNED DEFAULT NULL,
    maximo FLOAT UNSIGNED DEFAULT NULL,
    nombre VARCHAR(15) NOT NULL,
    letra VARCHAR(1) NOT NULL,
    PRIMARY KEY (idDesempenio)
) ENGINE=InnoDB;

INSERT INTO desempenio (descripcion, minimo, maximo, nombre, letra) VALUES
('1.0 - 2.9', 1.0, 2.9, 'Bajo', 'B'),
('3.0 - 3.9', 3.0, 3.9, 'Básico', 'S'),
('4.0 - 4.5', 4.0, 4.5, 'Alto', 'A'),
('4.6 - 5.0', 4.6, 5.0, 'Superior', 'E');

-- Tabla matricula
CREATE TABLE IF NOT EXISTS matricula (
    idEstudiante INT(10) UNSIGNED NOT NULL,
    anio INT(11) UNSIGNED NOT NULL,
    idSede INT(10) UNSIGNED NOT NULL,
    idGrado INT(10) UNSIGNED NOT NULL,
    idGrupo INT(10) UNSIGNED NOT NULL,
    idEstadoAnterior INT(10) UNSIGNED DEFAULT NULL,
    idEstadoActual INT(10) UNSIGNED NOT NULL,
    fecha_matricula DATE NOT NULL,
    fecha_retiro DATE DEFAULT NULL,
    motivo_retiro VARCHAR(20) DEFAULT NULL,
    institucionProcedencia VARCHAR(45) DEFAULT NULL,
    PRIMARY KEY (idEstudiante, anio),
    KEY Estado_actual (idEstadoActual),
    KEY Estado_anterior (idEstadoAnterior),
    KEY Estudiante_matricula (idEstudiante),
    KEY Grado_matricula (idGrado),
    KEY Grupo_matricula (idGrupo),
    KEY sede_matricula (idSede),
    CONSTRAINT fk_mat_estado_act FOREIGN KEY (idEstadoActual) REFERENCES estado_actual (idEstado) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_mat_estudiante FOREIGN KEY (idEstudiante) REFERENCES estudiantes (identificacion) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_mat_grado FOREIGN KEY (idGrado) REFERENCES grado (idgrado) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_mat_grupo FOREIGN KEY (idGrupo) REFERENCES grupo (idGrupo) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_mat_sede FOREIGN KEY (idSede) REFERENCES sedes (codigoDane) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB;

-- Tabla situacion_ano_anterior
CREATE TABLE IF NOT EXISTS situacion_ano_anterior (
    idsit_ano_ant INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    descripcion VARCHAR(15) NOT NULL,
    PRIMARY KEY (idsit_ano_ant)
) ENGINE=InnoDB;

INSERT INTO situacion_ano_anterior (descripcion) VALUES ('Aprobado'), ('Reprobado'), ('Retirado');

-- Tabla nota
CREATE TABLE IF NOT EXISTS nota (
    idEstudiante INT(10) UNSIGNED NOT NULL,
    num_informe INT(2) UNSIGNED NOT NULL,
    idDesempeño INT(10) UNSIGNED DEFAULT NULL,
    Unidad1 INT(10) UNSIGNED DEFAULT NULL,
    promedio INT(10) UNSIGNED DEFAULT NULL,
    Logro_1 INT(10) UNSIGNED DEFAULT NULL,
    Logro_2 INT(10) UNSIGNED DEFAULT NULL,
    Logro_3 INT(10) UNSIGNED DEFAULT NULL,
    Logro_4 INT(10) UNSIGNED DEFAULT NULL,
    Logro_5 INT(10) UNSIGNED DEFAULT NULL,
    descripcion VARCHAR(500) DEFAULT NULL,
    idmateria INT(10) UNSIGNED NOT NULL,
    anio INT(10) UNSIGNED DEFAULT NULL,
    Unidad2 INT(10) UNSIGNED DEFAULT NULL,
    Unidad3 INT(10) UNSIGNED DEFAULT NULL,
    Unidad4 INT(10) UNSIGNED DEFAULT NULL,
    Unidad5 INT(10) UNSIGNED DEFAULT NULL,
    Unidad6 INT(10) UNSIGNED DEFAULT NULL,
    Unidad7 INT(10) UNSIGNED DEFAULT NULL,
    Unidad8 INT(10) UNSIGNED DEFAULT NULL,
    Unidad9 INT(10) UNSIGNED DEFAULT NULL,
    Unidad10 INT(10) UNSIGNED DEFAULT NULL,
    Avance INT(10) UNSIGNED DEFAULT NULL,
    idgrado INT(10) UNSIGNED NOT NULL,
    PRIMARY KEY (idEstudiante, num_informe, idmateria, idgrado),
    KEY estudiante_nota (idEstudiante),
    KEY grado_nota (idgrado),
    KEY materia (idmateria),
    CONSTRAINT fk_nota_estudiante FOREIGN KEY (idEstudiante) REFERENCES estudiantes (identificacion) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_nota_grado FOREIGN KEY (idgrado) REFERENCES grado (idgrado) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_nota_materia FOREIGN KEY (idmateria) REFERENCES materias (idMateria) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB;

-- Admin user por defecto (password: admin123)
INSERT INTO personas (identificacion, primerNombre, primerApellido, funcion) 
VALUES (4414730, 'José Andrés', 'Caicedo Hernandez', 'Administrador');

INSERT INTO administrativos (idpersonas, cargo) VALUES (4414730, 'Administrador');

INSERT INTO usuarios (idnivel, username, contrasena) 
VALUES (1, 'admin', MD5('admin123'));
