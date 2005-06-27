--
-- PostgreSQL database dump
--

SET client_encoding = 'LATIN1';
SET check_function_bodies = false;

SET SESSION AUTHORIZATION 'postgres';

--
-- TOC entry 4 (OID 2200)
-- Name: public; Type: ACL; Schema: -; Owner: postgres
--

REVOKE ALL ON SCHEMA public FROM PUBLIC;
GRANT ALL ON SCHEMA public TO PUBLIC;


SET SESSION AUTHORIZATION 'phpreport';

SET search_path = public, pg_catalog;

--
-- TOC entry 5 (OID 17143)
-- Name: consulta_id_seq; Type: SEQUENCE; Schema: public; Owner: phpreport
--

CREATE SEQUENCE consulta_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- TOC entry 6 (OID 17145)
-- Name: consulta; Type: TABLE; Schema: public; Owner: phpreport
--

CREATE TABLE consulta (
    id integer DEFAULT nextval('"consulta_id_seq"'::text) NOT NULL,
    titulo character varying(80) NOT NULL,
    sql character varying(4096) NOT NULL,
    parametros character varying(512),
    descripcion character varying(1024),
    publica boolean
);


--
-- TOC entry 7 (OID 17153)
-- Name: informe; Type: TABLE; Schema: public; Owner: phpreport
--

CREATE TABLE informe (
    uid character varying(20) NOT NULL,
    fecha date NOT NULL,
    fecha_modificacion date NOT NULL
);


--
-- TOC entry 8 (OID 17157)
-- Name: bloqueo; Type: TABLE; Schema: public; Owner: phpreport
--

CREATE TABLE bloqueo (
    uid character varying(20) NOT NULL,
    fecha date NOT NULL
);


--
-- TOC entry 9 (OID 17161)
-- Name: tarea; Type: TABLE; Schema: public; Owner: phpreport
--

CREATE TABLE tarea (
    uid character varying(20) NOT NULL,
    fecha date NOT NULL,
    inicio integer NOT NULL,
    fin integer NOT NULL,
    nombre character varying(80),
    tipo character varying(10),
    fase character varying(15),
    ttipo character varying(40),
    texto character varying(8192),
    story character varying(80)
);


--
-- TOC entry 10 (OID 17168)
-- Name: compensacion; Type: TABLE; Schema: public; Owner: phpreport
--

CREATE TABLE compensacion (
    uid character varying(20) NOT NULL,
    inicio date NOT NULL,
    fin date NOT NULL,
    horas integer NOT NULL,
    pagado numeric(8,2) NOT NULL
);


--
-- TOC entry 11 (OID 17170)
-- Name: etiqueta; Type: TABLE; Schema: public; Owner: phpreport
--

CREATE TABLE etiqueta (
    tipo character varying(20) NOT NULL,
    codigo character varying(20) NOT NULL,
    descripcion character varying(80) NOT NULL,
    activacion boolean DEFAULT true NOT NULL
);


--
-- TOC entry 12 (OID 52922)
-- Name: usuario; Type: TABLE; Schema: public; Owner: phpreport
--

CREATE TABLE usuario (
    uid character varying(20) NOT NULL,
    "password" character varying(255),
    admin boolean DEFAULT false
);


--
-- TOC entry 13 (OID 52907)
-- Name: consulta_id_key; Type: INDEX; Schema: public; Owner: phpreport
--

CREATE INDEX consulta_id_key ON consulta USING btree (id);


--
-- TOC entry 15 (OID 52908)
-- Name: informe_fecha_key; Type: INDEX; Schema: public; Owner: phpreport
--

CREATE INDEX informe_fecha_key ON informe USING btree (fecha);


--
-- TOC entry 17 (OID 52909)
-- Name: informe_uid_key; Type: INDEX; Schema: public; Owner: phpreport
--

CREATE INDEX informe_uid_key ON informe USING btree (uid);


--
-- TOC entry 19 (OID 52910)
-- Name: bloqueo_uid_key; Type: INDEX; Schema: public; Owner: phpreport
--

CREATE INDEX bloqueo_uid_key ON bloqueo USING btree (uid);


--
-- TOC entry 24 (OID 52911)
-- Name: etiqueta_tipo_codigo_key; Type: INDEX; Schema: public; Owner: phpreport
--

CREATE UNIQUE INDEX etiqueta_tipo_codigo_key ON etiqueta USING btree (tipo, codigo);


--
-- TOC entry 25 (OID 52912)
-- Name: etiqueta_tipo_key; Type: INDEX; Schema: public; Owner: phpreport
--

CREATE INDEX etiqueta_tipo_key ON etiqueta USING btree (tipo);


--
-- TOC entry 22 (OID 52913)
-- Name: etiqueta_codigo_key; Type: INDEX; Schema: public; Owner: phpreport
--

CREATE INDEX etiqueta_codigo_key ON etiqueta USING btree (codigo);


--
-- TOC entry 21 (OID 52914)
-- Name: etiqueta_activacion_key; Type: INDEX; Schema: public; Owner: phpreport
--

CREATE INDEX etiqueta_activacion_key ON etiqueta USING btree (activacion);


--
-- TOC entry 14 (OID 17151)
-- Name: consulta_pkey; Type: CONSTRAINT; Schema: public; Owner: phpreport
--

ALTER TABLE ONLY consulta
    ADD CONSTRAINT consulta_pkey PRIMARY KEY (id);


--
-- TOC entry 16 (OID 17155)
-- Name: informe_pkey; Type: CONSTRAINT; Schema: public; Owner: phpreport
--

ALTER TABLE ONLY informe
    ADD CONSTRAINT informe_pkey PRIMARY KEY (fecha, uid);


--
-- TOC entry 18 (OID 17159)
-- Name: bloqueo_pkey; Type: CONSTRAINT; Schema: public; Owner: phpreport
--

ALTER TABLE ONLY bloqueo
    ADD CONSTRAINT bloqueo_pkey PRIMARY KEY (uid);


--
-- TOC entry 20 (OID 17166)
-- Name: tarea_pkey; Type: CONSTRAINT; Schema: public; Owner: phpreport
--

ALTER TABLE ONLY tarea
    ADD CONSTRAINT tarea_pkey PRIMARY KEY (fecha, inicio, uid);


--
-- TOC entry 23 (OID 17173)
-- Name: etiqueta_pkey; Type: CONSTRAINT; Schema: public; Owner: phpreport
--

ALTER TABLE ONLY etiqueta
    ADD CONSTRAINT etiqueta_pkey PRIMARY KEY (tipo, codigo);


--
-- TOC entry 26 (OID 52925)
-- Name: usuario_pkey; Type: CONSTRAINT; Schema: public; Owner: phpreport
--

ALTER TABLE ONLY usuario
    ADD CONSTRAINT usuario_pkey PRIMARY KEY (uid);


--
-- TOC entry 27 (OID 52915)
-- Name: RI_ConstraintTrigger_52915; Type: TRIGGER; Schema: public; Owner: phpreport
--

CREATE CONSTRAINT TRIGGER "RI_ConstraintTrigger_17449"
    AFTER UPDATE ON informe
    FROM tarea
    NOT DEFERRABLE INITIALLY IMMEDIATE
    FOR EACH ROW
    EXECUTE PROCEDURE "RI_FKey_noaction_upd"('&lt;unnamed&gt;', 'tarea', 'informe', 'UNSPECIFIED', 'uid', 'uid', 'fecha', 'fecha');


--
-- TOC entry 28 (OID 52916)
-- Name: RI_ConstraintTrigger_52916; Type: TRIGGER; Schema: public; Owner: phpreport
--

CREATE CONSTRAINT TRIGGER "RI_ConstraintTrigger_17447"
    AFTER DELETE ON informe
    FROM tarea
    NOT DEFERRABLE INITIALLY IMMEDIATE
    FOR EACH ROW
    EXECUTE PROCEDURE "RI_FKey_noaction_del"('&lt;unnamed&gt;', 'tarea', 'informe', 'UNSPECIFIED', 'uid', 'uid', 'fecha', 'fecha');


SET SESSION AUTHORIZATION 'postgres';

--
-- TOC entry 3 (OID 2200)
-- Name: SCHEMA public; Type: COMMENT; Schema: -; Owner: postgres
--

COMMENT ON SCHEMA public IS 'Standard public schema';


