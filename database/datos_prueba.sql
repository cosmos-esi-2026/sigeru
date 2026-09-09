-- ============================================================================
--  SiGeRU 2026 - Datos de prueba
--  Archivo: datos_prueba.sql
--  Que es : datos de ejemplo para poder mostrar el sistema funcionando.
--  Requiere: haber importado antes schema.sql
--
--  Los datos son de Montevideo y verosimiles a proposito (direcciones reales,
--  matriculas con formato uruguayo, horarios de recoleccion plausibles): en la
--  defensa se ve el sistema con datos que parecen de produccion y no con
--  "prueba1 / prueba2".
--
--  Todos los usuarios tienen la contrasena 123456, guardada como hash bcrypt.
-- ============================================================================

USE sigeru;

-- Se vacia todo antes de cargar, para poder reimportar cuantas veces haga falta.
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE incidencia;
TRUNCATE TABLE vaciado;
TRUNCATE TABLE recoleccion;
TRUNCATE TABLE ruta;
TRUNCATE TABLE camion;
TRUNCATE TABLE estado_contenedor;
TRUNCATE TABLE contenedor;
TRUNCATE TABLE usuario;
TRUNCATE TABLE solicitud_registro;
TRUNCATE TABLE cuadrilla;
TRUNCATE TABLE tipo_residuo;
TRUNCATE TABLE zona;
TRUNCATE TABLE centro_acopio;
TRUNCATE TABLE rol;
SET FOREIGN_KEY_CHECKS = 1;


-- --- 1) Roles ---------------------------------------------------------------
INSERT INTO rol (id_rol, nombre, descripcion) VALUES
(1, 'Administrador', 'Gestiona usuarios, contenedores, flota y aprueba solicitudes'),
(2, 'Operario',      'Opera la recoleccion y actualiza el estado de los contenedores'),
(3, 'Cuadrilla',     'Integra un equipo de recoleccion y atiende incidencias'),
(4, 'Vecino',        'Consulta contenedores y reporta incidencias');


-- --- 2) Centros de acopio ---------------------------------------------------
INSERT INTO centro_acopio (id_centro, nombre, direccion, latitud, longitud, capacidad, estado) VALUES
(1, 'Centro de Acopio Felipe Cardoso', 'Cno. Felipe Cardoso 3255',   -34.8305000, -56.1214000, 250000, 'activo'),
(2, 'Planta de Clasificacion Aires Puros', 'Cno. Castro 1450',       -34.8395000, -56.1832000,  90000, 'activo'),
(3, 'Centro de Acopio Ciudad Vieja',   'Rambla 25 de Agosto 458',    -34.9055000, -56.2110000,  40000, 'activo');


-- --- 3) Zonas ---------------------------------------------------------------
INSERT INTO zona (id_zona, nombre, descripcion, id_centro) VALUES
(1, 'Ciudad Vieja',  'Casco historico, alta densidad de comercios',        3),
(2, 'Centro',        'Zona centrica con edificios y oficinas',             3),
(3, 'Pocitos',       'Zona residencial de alta densidad',                  1),
(4, 'Cordon',        'Zona mixta residencial y comercial',                 1),
(5, 'Aires Puros',   'Zona residencial con planta de clasificacion',       2);


-- --- 4) Tipos de residuo ----------------------------------------------------
INSERT INTO tipo_residuo (id_tipo_residuo, nombre, descripcion, reciclable) VALUES
(1, 'Organico', 'Restos de comida y poda',                    FALSE),
(2, 'Plastico', 'Envases PET, film y polietileno',            TRUE),
(3, 'Vidrio',   'Botellas y frascos',                         TRUE),
(4, 'Papel',    'Papel y carton limpios',                     TRUE),
(5, 'Metal',    'Latas de aluminio y hojalata',               TRUE),
(6, 'Mixto',    'Residuo domiciliario sin separar',           FALSE);


-- --- 5) Cuadrillas ----------------------------------------------------------
INSERT INTO cuadrilla (id_cuadrilla, nombre, turno, estado) VALUES
(1, 'Cuadrilla Norte',  'matutino',   'activa'),
(2, 'Cuadrilla Sur',    'vespertino', 'activa'),
(3, 'Cuadrilla Centro', 'nocturno',   'activa'),
(4, 'Cuadrilla Este',   'matutino',   'inactiva');


-- --- 6) Solicitudes de registro ---------------------------------------------
-- Dos ya resueltas (una aprobada, que origino un usuario) y dos pendientes,
-- para que la pantalla de aprobacion tenga algo que mostrar.
INSERT INTO solicitud_registro (id_solicitud, nombre, apellido, email, telefono, direccion, estado, fecha_solicitud) VALUES
(1, 'Marta',    'Rodriguez', 'vecino@sigeru.uy',        '099 415 220', 'Bvar. Espana 2233',      'aprobada',  '2026-08-14 09:12:00'),
(2, 'Diego',    'Fernandez', 'diego.fernandez@mail.uy', '098 337 145', 'Juan Benito Blanco 1180','pendiente', '2026-09-05 18:44:00'),
(3, 'Valentina','Suarez',    'valentina.suarez@mail.uy','091 220 874', 'Br. Artigas 1560',       'pendiente', '2026-09-07 11:03:00'),
(4, 'Ruben',    'Correa',    'ruben.correa@mail.uy',    '094 118 902', 'Cno. Maldonado 5540',    'rechazada', '2026-08-29 15:20:00');


-- --- 7) Usuarios ------------------------------------------------------------
-- Contrasena de todos: 123456
INSERT INTO usuario (id_usuario, nombre, apellido, email, password_hash, telefono, direccion, id_rol, id_cuadrilla, id_solicitud, estado, fecha_registro) VALUES
(1, 'Ana',    'Perez',     'admin@sigeru.uy',     '$2y$10$hDCZ02JPdOHZz6oJNgENCOAgpB3QeicLJqjVjxX/6dtbGm/yNiFym', '099 123 456', 'Av. 18 de Julio 1360', 1, NULL, NULL, 'activo', '2026-07-01 08:00:00'),
(2, 'Luis',   'Gomez',     'cuadrilla@sigeru.uy', '$2y$10$hDCZ02JPdOHZz6oJNgENCOAgpB3QeicLJqjVjxX/6dtbGm/yNiFym', '099 234 567', 'Cno. Castro 1502',     3, 1,    NULL, 'activo', '2026-07-01 08:05:00'),
(3, 'Marta',  'Rodriguez', 'vecino@sigeru.uy',    '$2y$10$hDCZ02JPdOHZz6oJNgENCOAgpB3QeicLJqjVjxX/6dtbGm/yNiFym', '099 415 220', 'Bvar. Espana 2233',    4, NULL, 1,    'activo', '2026-08-15 10:30:00'),
(4, 'Sergio', 'Ferreira',  'operario@sigeru.uy',  '$2y$10$hDCZ02JPdOHZz6oJNgENCOAgpB3QeicLJqjVjxX/6dtbGm/yNiFym', '098 771 300', 'Cno. Felipe Cardoso 3200', 2, NULL, NULL, 'activo', '2026-07-03 09:15:00'),
(5, 'Carla',  'Nunez',     'carla.nunez@sigeru.uy','$2y$10$hDCZ02JPdOHZz6oJNgENCOAgpB3QeicLJqjVjxX/6dtbGm/yNiFym','091 556 043', 'Av. Rivera 2890',      3, 2,    NULL, 'activo', '2026-07-20 14:00:00'),
(6, 'Pablo',  'Machado',   'pablo.machado@mail.uy','$2y$10$hDCZ02JPdOHZz6oJNgENCOAgpB3QeicLJqjVjxX/6dtbGm/yNiFym','092 664 118', 'Ellauri 470',          4, NULL, NULL, 'inactivo','2026-07-25 16:40:00');


-- --- 8) Contenedores --------------------------------------------------------
INSERT INTO contenedor (id_contenedor, codigo, direccion, latitud, longitud, capacidad, estado, id_zona, id_tipo_residuo) VALUES
(1,  'CNT-001', 'Av. Italia esq. Propios',        -34.8760000, -56.1520000, 1100, 'disponible',    4, 6),
(2,  'CNT-002', 'Plaza Independencia 750',        -34.9068000, -56.1998000, 1100, 'lleno',         2, 6),
(3,  'CNT-003', 'Bvar. Artigas 2200',             -34.8912000, -56.1662000,  800, 'disponible',    4, 2),
(4,  'CNT-004', 'Sarandi 620',                    -34.9080000, -56.2035000,  660, 'disponible',    1, 3),
(5,  'CNT-005', 'Perez Castellano 1520',          -34.9052000, -56.2124000, 1100, 'mantenimiento', 1, 6),
(6,  'CNT-006', '21 de Setiembre 2680',           -34.9182000, -56.1560000,  800, 'disponible',    3, 4),
(7,  'CNT-007', 'Av. Brasil 3040',                -34.9128000, -56.1497000, 1100, 'lleno',         3, 6),
(8,  'CNT-008', 'Ellauri 350',                    -34.9165000, -56.1540000,  660, 'disponible',    3, 2),
(9,  'CNT-009', 'Cno. Castro 1480',               -34.8390000, -56.1830000, 1100, 'disponible',    5, 6),
(10, 'CNT-010', 'Av. Millan 4200',                -34.8480000, -56.1935000,  800, 'retirado',      5, 1);


-- --- 9) Historial de estados de contenedor ----------------------------------
-- Muestra la trazabilidad: el CNT-005 se rompio y paso a mantenimiento, el
-- CNT-010 se dio de baja despues de reparaciones repetidas.
INSERT INTO estado_contenedor (id_contenedor, fecha_hora, estado, observacion) VALUES
(2,  '2026-09-07 07:10:00', 'disponible',    'Vaciado en recoleccion matutina'),
(2,  '2026-09-08 19:35:00', 'lleno',         'Reportado por vecino'),
(5,  '2026-08-30 10:20:00', 'mantenimiento', 'Tapa rota, se solicita reparacion'),
(7,  '2026-09-08 21:05:00', 'lleno',         'Desborde en fin de semana largo'),
(10, '2026-07-12 09:00:00', 'mantenimiento', 'Rueda partida'),
(10, '2026-08-02 11:30:00', 'mantenimiento', 'Segunda reparacion en dos meses'),
(10, '2026-08-20 08:45:00', 'retirado',      'Fin de vida util, se retira de circulacion');


-- --- 10) Camiones -----------------------------------------------------------
INSERT INTO camion (id_camion, matricula, marca, modelo, capacidad, estado) VALUES
(1, 'SBA-1234', 'Volkswagen',    'Delivery 11.180', 4000, 'operativo'),
(2, 'SBB-5678', 'Iveco',         'Tector 170E22',   6000, 'en_ruta'),
(3, 'SBC-9012', 'Mercedes-Benz', 'Accelo 1016',     5000, 'mantenimiento'),
(4, 'SBD-3456', 'Ford',          'Cargo 1722',      5500, 'operativo'),
(5, 'SBE-7890', 'Iveco',         'Daily 70C17',     3000, 'fuera_servicio');


-- --- 11) Rutas --------------------------------------------------------------
INSERT INTO ruta (id_ruta, nombre, dia_semana, hora_inicio, estado, id_zona) VALUES
(1, 'Ruta Ciudad Vieja Manana', 'lunes',     '06:00:00', 'activa',   1),
(2, 'Ruta Centro Manana',       'lunes',     '06:30:00', 'activa',   2),
(3, 'Ruta Pocitos Tarde',       'miercoles', '14:00:00', 'activa',   3),
(4, 'Ruta Cordon Manana',       'viernes',   '06:15:00', 'activa',   4),
(5, 'Ruta Aires Puros Noche',   'jueves',    '22:00:00', 'inactiva', 5);


-- --- 12) Recolecciones ------------------------------------------------------
INSERT INTO recoleccion (id_recoleccion, fecha_hora_inicio, fecha_hora_fin, estado, observaciones, id_ruta, id_camion, id_cuadrilla, id_centro) VALUES
(1, '2026-09-07 06:00:00', '2026-09-07 09:40:00', 'finalizada', 'Sin novedades',                         1, 1, 1, 3),
(2, '2026-09-07 06:30:00', '2026-09-07 10:15:00', 'finalizada', 'Demora por corte en 18 de Julio',       2, 4, 1, 3),
(3, '2026-09-09 14:00:00', NULL,                  'en_curso',   NULL,                                    3, 2, 2, 1),
(4, '2026-09-11 06:15:00', NULL,                  'planificada',NULL,                                    4, 1, 1, 1),
(5, '2026-09-04 22:00:00', NULL,                  'cancelada',  'Camion fuera de servicio',              5, 5, 3, 2);


-- --- 13) Vaciados -----------------------------------------------------------
INSERT INTO vaciado (id_recoleccion, id_contenedor, fecha_hora, peso_kg, volumen_l) VALUES
(1, 4,  '2026-09-07 06:25:00', 210.50, 640.00),
(1, 5,  '2026-09-07 07:05:00', 180.00, 520.00),
(2, 2,  '2026-09-07 07:10:00', 340.75, 1080.00),
(2, 1,  '2026-09-07 08:20:00', 295.20, 950.00),
(3, 6,  '2026-09-09 14:35:00', 150.00, 480.00),
(3, 8,  '2026-09-09 15:10:00', 132.40, 410.00);


-- --- 14) Incidencias --------------------------------------------------------
INSERT INTO incidencia (descripcion, fecha_reporte, fecha_resolucion, estado, prioridad, id_usuario, id_contenedor, id_cuadrilla) VALUES
('El contenedor esta desbordado y hay bolsas en la vereda', '2026-09-08 19:30:00', NULL,                  'reportada',  'alta',  3, 2,  NULL),
('La tapa no cierra y entran perros',                       '2026-08-30 10:05:00', '2026-09-01 11:00:00', 'resuelta',   'media', 3, 5,  1),
('Contenedor quemado durante la noche',                     '2026-09-06 08:15:00', NULL,                  'en_proceso', 'alta',  6, 7,  2),
('Olor muy fuerte, parece que no se levanta hace dias',     '2026-09-09 09:40:00', NULL,                  'reportada',  'media', 3, 7,  NULL),
('Reporte duplicado del mismo vecino',                      '2026-09-02 12:00:00', '2026-09-02 12:30:00', 'descartada', 'baja',  6, 1,  NULL);

-- ============================================================================
--  Fin de los datos de prueba.
-- ============================================================================
