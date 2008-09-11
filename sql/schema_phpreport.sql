--
-- PostgreSQL database dump
--

SET client_encoding = 'UTF8';
SET standard_conforming_strings = off;
SET check_function_bodies = false;
SET client_min_messages = warning;
SET escape_string_warning = off;

--
-- Name: SCHEMA public; Type: COMMENT; Schema: -; Owner: -
--

COMMENT ON SCHEMA public IS 'Standard public schema';


SET search_path = public, pg_catalog;

--
-- Name: one(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION one() RETURNS integer
    AS $$SELECT 1 as RESULT$$
    LANGUAGE sql;


--
-- Name: plpgsql_call_handler(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION plpgsql_call_handler() RETURNS opaque
    AS '$libdir/plpgsql', 'plpgsql_call_handler'
    LANGUAGE c;


SET default_tablespace = '';

SET default_with_oids = false;

--
-- Name: block; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE block (
    uid character varying(20) NOT NULL,
    _date date NOT NULL
);


--
-- Name: compensation; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE compensation (
    uid character varying(20) NOT NULL,
    init date NOT NULL,
    _end date NOT NULL,
    hours integer NOT NULL,
    paid numeric(8,2) NOT NULL
);


--
-- Name: consult; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE consult (
    id integer DEFAULT nextval(('"consult_id_seq"'::text)::regclass) NOT NULL,
    title character varying(80) NOT NULL,
    sql character varying(4096) NOT NULL,
    parameters character varying(512),
    description character varying(1024),
    public boolean
);


--
-- Name: consult_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE consult_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: customer; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE customer (
    id character varying(256) NOT NULL,
    name character varying(256) NOT NULL,
    "type" character varying(256) NOT NULL,
    sector character varying(256) NOT NULL,
    url character varying(8192)
);


--
-- Name: extra_hours; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE extra_hours (
    uid character varying(20) NOT NULL,
    hours double precision NOT NULL,
    date date NOT NULL
);


--
-- Name: holiday; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE holiday (
    fest date NOT NULL,
    city character varying(30) NOT NULL
);


--
-- Name: label; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE label (
    "type" character varying(20) NOT NULL,
    activation boolean DEFAULT true NOT NULL,
    no_customer boolean,
    code character varying(256),
    description character varying(256)
);


--
-- Name: periods; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE periods (
    uid character varying(20) NOT NULL,
    journey integer NOT NULL,
    init date,
    _end date,
    city character varying(30),
    hour_cost numeric(8,4),
    area character varying(256)
);


--
-- Name: project_user; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE project_user (
    uid character varying(256) NOT NULL,
    name character varying(256) NOT NULL
);


--
-- Name: projects; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE projects (
    activation boolean DEFAULT true NOT NULL,
    init date,
    _end date,
    invoice double precision,
    est_hours double precision,
    customer character varying(256),
    area character varying(256),
    id character varying(256) NOT NULL,
    description character varying(256),
    type character varying(256)
);


--
-- Name: report; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE report (
    uid character varying(20) NOT NULL,
    _date date NOT NULL,
    modification_date date NOT NULL
);


--
-- Name: task; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE task (
    uid character varying(20) NOT NULL,
    _date date NOT NULL,
    init integer NOT NULL,
    _end integer NOT NULL,
    "type" character varying(10),
    phase character varying(15),
    ttype character varying(40),
    text character varying(8192),
    story character varying(80),
    telework boolean,
    customer character varying(256),
    name character varying(256)
);


--
-- Name: users; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE users (
    uid character varying(20) NOT NULL,
    "password" character varying(255),
    "admin" boolean DEFAULT false,
    staff boolean DEFAULT false
);


--
-- Name: block_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY block
    ADD CONSTRAINT block_pkey PRIMARY KEY (uid);


--
-- Name: consult_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY consult
    ADD CONSTRAINT consult_pkey PRIMARY KEY (id);


--
-- Name: extra_hours_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY extra_hours
    ADD CONSTRAINT extra_hours_pkey PRIMARY KEY (uid, date);


--
-- Name: holiday_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY holiday
    ADD CONSTRAINT holiday_pkey PRIMARY KEY (fest, city);


--
-- Name: pk_customer; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY customer
    ADD CONSTRAINT pk_customer PRIMARY KEY (id);

--
-- Name: pk_projects; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY projects
    ADD CONSTRAINT pk_projects PRIMARY KEY(id);

--
-- Name: pk_project_user; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY project_user
    ADD CONSTRAINT pk_project_user PRIMARY KEY (uid, name);

--
-- Name: pk_periods; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY periods
    ADD CONSTRAINT pk_periods PRIMARY KEY (uid, init);

--
-- Name: report_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY report
    ADD CONSTRAINT report_pkey PRIMARY KEY (_date, uid);


--
-- Name: task_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY task
    ADD CONSTRAINT task_pkey PRIMARY KEY (_date, init, uid);


--
-- Name: users_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY users
    ADD CONSTRAINT users_pkey PRIMARY KEY (uid);


--
-- Name: block_uid_key; Type: INDEX; Schema: public; Owner: -; Tablespace: 
--

CREATE INDEX block_uid_key ON block USING btree (uid);


--
-- Name: consult_id_key; Type: INDEX; Schema: public; Owner: -; Tablespace: 
--

CREATE INDEX consult_id_key ON consult USING btree (id);


--
-- Name: extra_hours_id_key; Type: INDEX; Schema: public; Owner: -; Tablespace: 
--

CREATE INDEX extra_hours_id_key ON extra_hours USING btree (uid, date);


--
-- Name: holiday_city_key; Type: INDEX; Schema: public; Owner: -; Tablespace: 
--

CREATE INDEX holiday_city_key ON holiday USING btree (city);


--
-- Name: holiday_date_key; Type: INDEX; Schema: public; Owner: -; Tablespace: 
--

CREATE INDEX holiday_date_key ON holiday USING btree (fest);


--
-- Name: label_activation_key; Type: INDEX; Schema: public; Owner: -; Tablespace: 
--

CREATE INDEX label_activation_key ON label USING btree (activation);


--
-- Name: label_type_key; Type: INDEX; Schema: public; Owner: -; Tablespace: 
--

CREATE INDEX label_type_key ON label USING btree ("type");


--
-- Name: report_date_key; Type: INDEX; Schema: public; Owner: -; Tablespace: 
--

CREATE INDEX report_date_key ON report USING btree (_date);


--
-- Name: report_uid_key; Type: INDEX; Schema: public; Owner: -; Tablespace: 
--

CREATE INDEX report_uid_key ON report USING btree (uid);


--
-- Name: RI_ConstraintTrigger_23910; Type: TRIGGER; Schema: public; Owner: -
--

CREATE CONSTRAINT TRIGGER "RI_ConstraintTrigger_17449"
    AFTER UPDATE ON report
    FROM task
    NOT DEFERRABLE INITIALLY IMMEDIATE
    FOR EACH ROW
    EXECUTE PROCEDURE "RI_FKey_noaction_upd"('&lt;unnamed&gt;', 'task', 'report', 'UNSPECIFIED', 'uid', 'uid', '_date', '_date');


--
-- Name: RI_ConstraintTrigger_23911; Type: TRIGGER; Schema: public; Owner: -
--

CREATE CONSTRAINT TRIGGER "RI_ConstraintTrigger_17447"
    AFTER DELETE ON report
    FROM task
    NOT DEFERRABLE INITIALLY IMMEDIATE
    FOR EACH ROW
    EXECUTE PROCEDURE "RI_FKey_noaction_del"('&lt;unnamed&gt;', 'task', 'report', 'UNSPECIFIED', 'uid', 'uid', '_date', '_date');


--
-- Name: public; Type: ACL; Schema: -; Owner: -
--

REVOKE ALL ON SCHEMA public FROM PUBLIC;
REVOKE ALL ON SCHEMA public FROM postgres;
GRANT ALL ON SCHEMA public TO postgres;
GRANT ALL ON SCHEMA public TO PUBLIC;


--
-- Name: plpgsql_call_handler(); Type: ACL; Schema: public; Owner: -
--

REVOKE ALL ON FUNCTION plpgsql_call_handler() FROM PUBLIC;
REVOKE ALL ON FUNCTION plpgsql_call_handler() FROM postgres;
GRANT ALL ON FUNCTION plpgsql_call_handler() TO PUBLIC;


--
-- PostgreSQL database dump complete
--

