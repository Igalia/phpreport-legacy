--
-- PostgreSQL database dump
--

SET client_encoding = 'LATIN1';
SET check_function_bodies = false;

SET SESSION AUTHORIZATION 'phpreport';

SET search_path = public, pg_catalog;

--
-- Data for TOC entry 3 (OID 17145)
-- Name: consulta; Type: TABLE DATA; Schema: public; Owner: phpreport
--

COPY consulta (id, titulo, sql, parametros, descripcion, publica) FROM stdin;
12	Recuento general de horas trabajadas en un intervalo	SELECT uid, SUM(fin-inicio)/60.0 - COALESCE((\r\n  SELECT SUM(horas)\r\n  FROM compensacion\r\n  WHERE uid=tarea.uid\r\n   AND inicio>=fecha_inicio\r\n   AND fin<=fecha_fin\r\n ),0) AS suma_horas\r\nFROM tarea\r\nWHERE fecha>=fecha_inicio\r\n AND fecha<=fecha_fin\r\nGROUP BY uid	fecha_inicio,fecha_fin	Calcula el número de horas trabajadas por todos los trabajadores en el intervalo desde "fecha_inicio" a "fecha_fin" (formato AAAA-MM-DD). A este número de horas se le descuentan automáticamente las horas ya compensadas por el trabajador en ese intervalo.	t
13	Borrado de informes y tareas de un trabajador en un intervalo	BEGIN TRANSACTION;\r\nDELETE FROM tarea WHERE uid=uid_trabajador AND fecha>=fecha_inicio AND fecha<=fecha_fin AND confirmacion='ok';\r\nDELETE FROM informe WHERE uid=uid_trabajador AND fecha>=fecha_inicio AND fecha<=fecha_fin AND confirmacion='ok';\r\nCOMMIT TRANSACTION	uid_trabajador,fecha_inicio,fecha_fin,confirmacion	Borra todos los informes y tareas del trabajador uid_trabajador en el intervalo desde fecha_inicio hasta fecha_fin (formato AAAA-MM-DD).\r\nEs necesario introducir la palabra "ok" en confirmacion. Esto se hace para evitar borrar informes por error.	\N
23	Listado de informes huérfanos	SELECT * FROM informe WHERE (SELECT COUNT(*) FROM tarea WHERE tarea.uid=informe.uid AND tarea.fecha=informe.fecha)=0		Lista aquellos informes que no tienen ninguna tarea dentro	\N
11	Listado de bloqueos	SELECT * FROM bloqueo		Esta consulta muestra el listado de bloqueos para todos los empleados.	\N
10	Listado de consultas	SELECT * FROM consulta ORDER BY id		Esta consulta muestra el listado de todas las consultas definidas.	\N
2	Listado de tareas	SELECT * FROM tarea ORDER BY uid,inicio		Esta consulta selecciona todas las tareas ordenadas por usuario y fecha.	\N
15	Listado de compensaciones	SELECT * FROM compensacion		Muestra un listado de todas las compensaciones existentes. <br>La compensación es un mecanismo que permite cambiar horas trabajadas por dinero, vacaciones, etc, de modo que dichas horas de más no se tengan luego en cuenta a la hora de generar los recuentos de horas totales trabajadas. Al estar compensadas (remuneradas) esas horas, será como si nunca hubiesen existido.	\N
16	Borrar compensación	DELETE FROM compensacion WHERE uid=uid_trabajador AND inicio=fecha_inicio AND fin=fecha_fin	uid_trabajador,fecha_inicio,fecha_fin	Borra de la BD una compensación referente al trabajador uid_trabajador durante el intervalo desde fecha_inicio hasta fecha_fin. Las fechas están en formato AAAA-MM-DD. El intervalo debe coincidir exactamente con el de una compensación ya existente para que el borrado sea efectivo.<br>La compensación es un mecanismo que permite cambiar horas trabajadas por dinero, vacaciones, etc, de modo que dichas horas de más no se tengan luego en cuenta a la hora de generar los recuentos de horas totales trabajadas. Al estar compensadas (remuneradas) esas horas, será como si nunca hubiesen existido.	\N
14	Añadir compensación	INSERT INTO compensacion (uid,inicio,fin,horas,pagado) VALUES (uid_trabajador,fecha_inicio,fecha_fin,num_horas,cantidad_pagada)	uid_trabajador,num_horas,fecha_inicio,fecha_fin,cantidad_pagada	Inserta una nueva compensación en la BD en la que al trabajador uid_trabajador se le conmuta una cierta cantidad de num_horas de horas extra durante el período desde fecha_inicio hasta fecha_fin a cambio de una cantidad_pagada de dinero. Las fechas están en formato AAAA-MM-DD.<br>La compensación es un mecanismo que permite cambiar horas trabajadas por dinero, vacaciones, etc, de modo que dichas horas de más no se tengan luego en cuenta a la hora de generar los recuentos de horas totales trabajadas. Al estar compensadas (remuneradas) esas horas, será como si nunca hubiesen existido.	\N
26	Borrar informes de todos los trabajadores en un intervalo	BEGIN TRANSACTION;\r\nDELETE FROM tarea WHERE fecha>=fecha_inicio AND fecha<=fecha_fin AND confirmacion='ok';\r\nDELETE FROM informe WHERE fecha>=fecha_inicio AND fecha<=fecha_fin AND confirmacion='ok';\r\nCOMMIT TRANSACTION	fecha_inicio,fecha_fin,confirmacion		\N
18	Borrar informes huérfanos	DELETE FROM informe WHERE (SELECT COUNT(*) FROM tarea WHERE tarea.uid=informe.uid AND tarea.fecha=informe.fecha)=0		Borra informes que no tienen tareas asociadas	\N
19	Tareas de un trabajador por proyecto en un intervalo 	SELECT uid,fecha,\r\n to_char(inicio/60,'FM00')||':'||to_char(inicio%60,'FM00') as inicio,\r\n to_char(fin/60,'FM00')||':'||to_char(fin%60,'FM00') as fin,\r\n nombre,tipo,fase,ttipo,texto\r\nFROM tarea WHERE uid LIKE uid_trabajador AND nombre LIKE project_name AND fecha>=fecha_inicio AND fecha<=fecha_fin ORDER BY uid,fecha,inicio	uid_trabajador, project_name,fecha_inicio,fecha_fin	Selección de todas las tareas de un trabajador (uid_trabajador) durante un determinado intervalo (fecha_inicio, fecha_fin).\r\nLas fechas deben estar en formato YYYY-MM-DD.Se admiten comodines (%) en el nombre del trabajador y el proyecto.	t
29	Recuento de horas de un trabajador por tipo, proyecto y fecha	SELECT uid, tipo, nombre, SUM(fin-inicio)/60.0 - COALESCE((\r\n  SELECT SUM(horas)\r\n  FROM compensacion\r\n  WHERE uid=tarea.uid\r\n   AND inicio>=fecha_inicio\r\n   AND fin<=fecha_fin\r\n ),0) AS suma_horas\r\nFROM tarea\r\nWHERE uid LIKE uid_trabajador AND \r\n   nombre LIKE project_name AND \r\n   tipo LIKE type_name AND \r\n   fecha>=fecha_inicio AND\r\n   fecha<=fecha_fin\r\nGROUP BY uid, nombre, tipo	uid_trabajador,fecha_inicio,fecha_fin,type_name,project_name	Calcula el número de horas trabajadas por un trabajador en el intervalo desde "fecha_inicio" a "fecha_fin" (formato AAAA-MM-DD) para un trabajador concreto, para un tipo de tarea concreto y para un proyecto concreto.\r\nSe admite % como comodín en todos los campos de texto.	t
30	Recuento de horas de un trabajador por tipo y proyecto	SELECT uid, tipo, nombre, SUM(fin-inicio)/60.0 - COALESCE((\r\n  SELECT SUM(horas)\r\n  FROM compensacion\r\n  WHERE uid=tarea.uid\r\n ),0) AS suma_horas\r\nFROM tarea\r\nWHERE uid LIKE uid_trabajador AND \r\n   nombre LIKE project_name AND \r\n   tipo LIKE type_name \r\nGROUP BY uid, nombre, tipo	uid_trabajador,type_name,project_name	Recuento de todas las horas de un trabajador (uid_trabajador) en un tipo  y en proyecto concreto (nombre)\r\n	t
34	Recuento de horas de un trabajador por story, tipo, proyecto y fecha	SELECT uid, tipo, nombre, story, SUM(fin-inicio)/60.0 - COALESCE((\r\n  SELECT SUM(horas)\r\n  FROM compensacion\r\n  WHERE uid=tarea.uid\r\n   AND inicio>=fecha_inicio\r\n   AND fin<=fecha_fin\r\n ),0) AS suma_horas\r\nFROM tarea\r\nWHERE uid LIKE uid_trabajador AND \r\n   nombre LIKE project_name AND \r\n   story  LIKE story_name AND\r\n   tipo LIKE type_name AND \r\n   fecha>=fecha_inicio AND\r\n   fecha<=fecha_fin\r\nGROUP BY uid, nombre, tipo, story	uid_trabajador,fecha_inicio,fecha_fin,story_name,type_name,project_name	Calcula el número de horas trabajadas por un trabajador en el intervalo desde "fecha_inicio" a "fecha_fin" (formato AAAA-MM-DD) para un trabajador concreto, para un tipo y story de tarea concreto y para un proyecto concreto.\r\nSe admite % como comodín en todos los campos de texto.	t
31	Recuento general de horas trabajadas en la semana actual	SELECT uid, SUM(fin-inicio)/60.0 - COALESCE((\r\n  SELECT SUM(horas)\r\n  FROM compensacion\r\n  WHERE uid=tarea.uid\r\n   AND inicio>=(current_timestamp - ((int2(date_part('dow',current_timestamp )+7-1) % 7)||' days')::interval)::date\r\n   AND fin<=(current_timestamp - ((int2(date_part('dow',current_timestamp)+7-1) % 7)-6||' days')::interval)::date\r\n ),0) AS suma_horas\r\nFROM tarea\r\nWHERE fecha>=(current_timestamp - ((int2(date_part('dow',current_timestamp )+7-1) % 7)||' days')::interval)::date\r\n AND fecha<= (current_timestamp - ((int2(date_part('dow',current_timestamp )+7-1) % 7)-6||' days')::interval)::date\r\nGROUP BY uid		Calcula el número de horas trabajadas por todos los trabajadores en la semana actual. A este número de horas se le descuentan automáticamente las horas ya compensadas por el trabajador en ese intervalo.	t
35	Recuento de horas de un trabajador por tipo, ttipo, proyecto y fecha	SELECT uid, tipo, nombre, ttipo,  SUM(fin-inicio)/60.0 - COALESCE((\r\n  SELECT SUM(horas)\r\n  FROM compensacion\r\n  WHERE uid=tarea.uid\r\n   AND inicio>=fecha_inicio\r\n   AND fin<=fecha_fin\r\n ),0) AS suma_horas\r\nFROM tarea\r\nWHERE uid LIKE uid_trabajador AND \r\n   nombre LIKE project_name AND \r\n   tipo LIKE type_name AND \r\n   ttipo LIKE ttipo_name AND\r\n   fecha>=fecha_inicio AND\r\n   fecha<=fecha_fin\r\nGROUP BY uid, nombre, tipo,  ttipo	uid_trabajador,fecha_inicio,fecha_fin,type_name,ttipo_name,project_name	Calcula el número de horas trabajadas por un trabajador en el intervalo desde "fecha_inicio" a "fecha_fin" (formato AAAA-MM-DD) para un trabajador concreto, para un tipo y ttipo de tarea concreto y para un proyecto concreto.\r\nSe admite % como comodín en todos los campos de texto.	t
32	Recuento de horas de un proyecto	SELECT nombre, SUM(fin-inicio)/60.0 as horas\r\nFROM tarea\r\nWHERE \r\n   nombre LIKE project_name\r\nGROUP BY nombre\r\n	project_name	Recuento de todas las horas de un proyecto concreto (nombre)\r\n	t
\.


--
-- Data for TOC entry 4 (OID 17153)
-- Name: informe; Type: TABLE DATA; Schema: public; Owner: phpreport
--

COPY informe (uid, fecha, fecha_modificacion) FROM stdin;
\.


--
-- Data for TOC entry 5 (OID 17157)
-- Name: bloqueo; Type: TABLE DATA; Schema: public; Owner: phpreport
--

COPY bloqueo (uid, fecha) FROM stdin;
\.


--
-- Data for TOC entry 6 (OID 17161)
-- Name: tarea; Type: TABLE DATA; Schema: public; Owner: phpreport
--

COPY tarea (uid, fecha, inicio, fin, nombre, tipo, fase, ttipo, texto, story) FROM stdin;
\.


--
-- Data for TOC entry 7 (OID 17168)
-- Name: compensacion; Type: TABLE DATA; Schema: public; Owner: phpreport
--

COPY compensacion (uid, inicio, fin, horas, pagado) FROM stdin;
\.


--
-- Data for TOC entry 8 (OID 17170)
-- Name: etiqueta; Type: TABLE DATA; Schema: public; Owner: phpreport
--

COPY etiqueta (tipo, codigo, descripcion, activacion) FROM stdin;
tipo	sis	Administración de sistemas	t
tipo	baj	Baja	t
tipo	bur	Burocracia	t
tipo	com	Comercial/Marketing	t
tipo	cor	Coordinación general	t
tipo	for	Formación	t
tipo	pex	Proyectos a terceros	t
tipo	pin	Proyectos internos	t
tipo	tap	Tareas de apoyo	t
tipo	vac	Vacaciones	t
ttipo	mto_sistemas	Mantenimiento - Sistemas	t
ttipo	demo	Demostración	t
fase	construccion	Construcción	t
fase	elaboracion	Elaboracion	t
fase	inicio	Inicio	t
fase	transicion	Transición	t
fase	explotacion	Explotación	t
ttipo	analisis	Análisis	t
ttipo	comunidad	Comunidad	t
tipo	ap	Asuntos personales	t
ttipo	coordinacion	Coordinación	t
ttipo	despliegue	Despliegue	t
ttipo	diseno	Diseño	t
ttipo	documentacion	Documentación	t
ttipo	entorno	Entorno	t
ttipo	formacion	Formación	t
ttipo	implementacion	Implementación	t
ttipo	mantenimiento	Mantenimiento	t
ttipo	prueba	Prueba	t
ttipo	publicacion	Publicación	t
ttipo	requisitos	Requisitos	t
ttipo	tecnologia	Tecnología	t
nombre	prueba1	Proyecto de prueba 1	t
nombre	prueba2	Proyecto de prueba 2	t
nombre	prueba3	Proyecto de prueba 3 (anticuado)	f
\.


--
-- Data for TOC entry 9 (OID 52922)
-- Name: usuario; Type: TABLE DATA; Schema: public; Owner: phpreport
--

COPY usuario (uid, "password", admin) FROM stdin;
admin	21232f297a57a5a743894a0e4a801fc3	t
usuario	f8032d5cae3de20fcec887f395ec9a6a	f
\.


--
-- TOC entry 2 (OID 17143)
-- Name: consulta_id_seq; Type: SEQUENCE SET; Schema: public; Owner: phpreport
--

SELECT pg_catalog.setval('consulta_id_seq', 35, true);


