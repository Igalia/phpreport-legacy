--
-- PostgreSQL database dump
--

SET client_encoding = 'UTF8';
SET standard_conforming_strings = off;
SET check_function_bodies = false;
SET client_min_messages = warning;
SET escape_string_warning = off;

SET search_path = public, pg_catalog;

--
-- Name: consult_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('consult_id_seq', 1, false);


--
-- Data for Name: block; Type: TABLE DATA; Schema: public; Owner: -
--

COPY block (uid, _date) FROM stdin;
admin	1999-12-31
amaneiro	1999-12-31
nes	1999-12-31
andres	1999-12-31
\.


--
-- Data for Name: compensation; Type: TABLE DATA; Schema: public; Owner: -
--

COPY compensation (uid, init, _end, hours, paid) FROM stdin;
\.


--
-- Data for Name: customer; Type: TABLE DATA; Schema: public; Owner: -
--

COPY customer (id, name, "type", sector, url) FROM stdin;
igalia	igalia corporation		inform	http://igalia.com
mit	Massachusetts Institute of Technology		education	http://web.mit.edu
\.


--
-- Data for Name: extra_hours; Type: TABLE DATA; Schema: public; Owner: -
--

COPY extra_hours (uid, hours, date) FROM stdin;
\.


--
-- Data for Name: holiday; Type: TABLE DATA; Schema: public; Owner: -
--

COPY holiday (fest, city) FROM stdin;
2007-10-15	vigo
2007-12-31	vigo
2008-01-01	vigo
2008-01-01	compostela
2007-12-31	compostela
2007-12-24	compostela
2007-12-25	compostela
2007-12-26	compostela
2007-12-27	compostela
2007-12-28	compostela
\.


--
-- Data for Name: label; Type: TABLE DATA; Schema: public; Owner: -
--

COPY label ("type", activation, no_customer, code, description) FROM stdin;
parea	t	\N	eng	engineering
parea	t	\N	inno	innovation
csector	t	\N	inform	informatics
customer	t	\N	igalia	igalia corporation
name	t	\N	phpreport	phpreport tool
csector	t	\N	education	education customers
customer	t	\N	mit	Massachusetts Institute of Technology
ttype	t	\N	analysis	analysis
ttype	t	\N	dev	develope
type	t	\N	adm	administration tasks
type	t	\N	form	formation tasks
type	t	\N	devt	developer tasks
name	t	\N	debian_course	Debian course at institute
ttype	t	\N	docs	documentation
ttype	t	\N	comm	community
parea	t	\N	adm_area	administration
name	t	\N	adm_igalia	administration tasks
\.


--
-- Data for Name: periods; Type: TABLE DATA; Schema: public; Owner: -
--

COPY periods (uid, journey, init, _end, city, hour_cost) FROM stdin;
andres	8	2007-09-01	2008-12-01	vigo	30.0000
amaneiro	4	2006-08-01	2007-12-01	compostela	20.0000
nes	6	2007-09-01	2008-09-01	compostela	20.0000
admin	8	2005-01-01	2006-05-01	compostela	10.0000
admin	6	2006-08-01	2007-05-01	vigo	30.0000
\.


--
-- Data for Name: project_user; Type: TABLE DATA; Schema: public; Owner: -
--

COPY project_user (uid, name) FROM stdin;
amaneiro	phpreport
admin	debian_course
nes	debian_course
admin	phpreport
andres	phpreport
admin	adm_igalia
\.


--
-- Data for Name: projects; Type: TABLE DATA; Schema: public; Owner: -
--

COPY projects (activation, init, _end, invoice, est_hours, customer, area, id, description) FROM stdin;
t	2007-09-01	2007-09-12	2400	80	igalia	eng	phpreport	phpreport tool
t	2007-10-01	2007-10-22	1200	120	mit	inno	debian_course	Debian course at institute
t	2007-10-01	2007-10-12	400	20	igalia	adm_area	adm_igalia	administration tasks
\.


--
-- Data for Name: report; Type: TABLE DATA; Schema: public; Owner: -
--

COPY report (uid, _date, modification_date) FROM stdin;
admin	2007-12-28	2007-10-03
admin	2007-10-04	2007-10-03
admin	2007-10-05	2007-10-03
admin	2007-10-02	2007-10-03
admin	2007-10-01	2007-10-03
admin	2007-10-08	2007-10-03
admin	2007-10-09	2007-10-03
admin	2007-10-10	2007-10-03
admin	2007-10-12	2007-10-03
amaneiro	2007-10-02	2007-10-03
amaneiro	2007-10-01	2007-10-03
amaneiro	2007-10-03	2007-10-03
amaneiro	2007-10-04	2007-10-03
amaneiro	2007-10-05	2007-10-03
amaneiro	2007-10-08	2007-10-03
amaneiro	2007-10-09	2007-10-03
amaneiro	2007-10-10	2007-10-03
amaneiro	2007-10-11	2007-10-03
admin	2007-10-03	2007-10-03
admin	2007-10-11	2007-10-03
nes	2007-10-01	2007-10-03
nes	2007-10-02	2007-10-03
nes	2007-10-03	2007-10-03
nes	2007-10-04	2007-10-03
nes	2007-10-05	2007-10-03
nes	2007-10-08	2007-10-03
nes	2007-10-09	2007-10-03
nes	2007-10-10	2007-10-03
nes	2007-10-11	2007-10-03
nes	2007-10-12	2007-10-03
andres	2007-10-01	2007-10-03
andres	2007-10-02	2007-10-03
andres	2007-10-03	2007-10-03
andres	2007-10-04	2007-10-03
andres	2007-10-05	2007-10-03
andres	2007-10-08	2007-10-03
andres	2007-10-09	2007-10-03
andres	2007-10-10	2007-10-03
andres	2007-10-11	2007-10-03
andres	2007-10-12	2007-10-03
\.


--
-- Data for Name: task; Type: TABLE DATA; Schema: public; Owner: -
--

COPY task (uid, _date, init, _end, "type", phase, ttype, text, story, telework, customer, name) FROM stdin;
admin	2007-12-28	660	900	devt	\N	dev	Implementing new use case	STR2007CASE2	\N	igalia	phpreport
admin	2007-12-28	960	1200	devt	\N	dev	Implementing new use case	STR2007CASE2	\N	igalia	phpreport
admin	2007-10-04	540	840	devt	\N	dev	Implementing new use case	STR2007CASE2	\N	igalia	phpreport
admin	2007-10-04	960	1200	form	\N	analysis	Creating documentatio about kernel 2.4	STR20MIT	\N	mit	
admin	2007-10-05	540	840	devt	\N	dev	Implementing new use case	STR2007CASE2	\N	igalia	phpreport
admin	2007-10-05	960	1200	form	\N	analysis	Creating documentation about kernel 2.4	STR20MIT	\N	mit	
admin	2007-10-02	660	900	devt	\N	dev	Implementing new use case	STR2007CASE2	\N	igalia	phpreport
admin	2007-10-02	960	1200	devt	\N	dev	Implementing new use case	STR2007CASE2	\N	igalia	phpreport
admin	2007-10-01	660	900	devt	\N	analysis	Studying and creating model to new use case	STR2007CASE1	\N	igalia	phpreport
admin	2007-10-01	960	1200	devt	\N	dev	Implementing new use case	STR2007CASE2	\N	igalia	phpreport
admin	2007-10-08	540	840	form	\N	dev	Creating PDF and slides	STR30MIT	\N	mit	debian_course
admin	2007-10-08	930	1170	form	\N	dev	Creating PDF and slides	STR30MIT	\N	mit	debian_course
admin	2007-10-09	540	840	form	\N	dev	Creating PDF and slides	STR30MIT	\N	mit	debian_course
admin	2007-10-09	930	1170	devt	\N	dev	Function to replace date	STR2007CASE3	\N	igalia	phpreport
admin	2007-10-10	540	840	devt	\N	dev	Function to replace date	STR2007CASE3	\N	igalia	phpreport
admin	2007-10-10	930	1170	devt	\N	dev	Function to replace date	STR2007CASE3	\N	igalia	phpreport
admin	2007-10-12	540	840	devt	\N	dev	Write function to create new graphs	STR2007CASE3	\N	igalia	phpreport
admin	2007-10-12	960	1185	devt	\N	dev	Write function to create new graphs	STR2007CASE3	\N	igalia	phpreport
amaneiro	2007-10-02	540	840	form	\N	analysis	Learning about phpreport creating projects	STR10NEW2	\N	igalia	phpreport
amaneiro	2007-10-01	540	840	form	\N	analysis	Learning about phpreport creating projects	STR10NEW2	\N	igalia	phpreport
amaneiro	2007-10-03	540	840	form	\N	dev	Modify function to plot data	STR10CASE40	\N	igalia	phpreport
amaneiro	2007-10-04	540	840	form	\N	dev	Modify function to plot data	STR10CASE40	\N	igalia	phpreport
amaneiro	2007-10-05	540	840	devt	\N	dev	Resolving bugs	STR10CASE40	\N	igalia	phpreport
amaneiro	2007-10-08	540	840	devt	\N	dev	Resolving bugs	STR10CASE40	\N	igalia	phpreport
amaneiro	2007-10-09	540	840	devt	\N	dev	Resolving bugs	STR10CASE40	\N	igalia	phpreport
amaneiro	2007-10-10	540	840	devt	\N	dev	Resolving bugs	STR10CASE40	\N	igalia	phpreport
amaneiro	2007-10-11	540	840	devt	\N	dev	Write documentation	STR20DOC	\N	igalia	phpreport
admin	2007-10-03	660	900	devt	\N	dev	Implementing new use case	STR2007CASE2	\N	igalia	phpreport
admin	2007-10-03	960	1200	adm	\N	docs	Write comunication protocols	STR2007CASE2	\N	igalia	adm_igalia
admin	2007-10-11	540	840	devt	\N	dev	Write function to create new graphs	STR2007CASE3	\N	igalia	phpreport
admin	2007-10-11	960	1185	adm	\N	docs	Write management protocols	\N	\N	igalia	adm_igalia
nes	2007-10-01	660	840	form	\N	comm	Talking with teacher about bash programing students level and writing specification document	\N	\N	mit	debian_course
nes	2007-10-01	1080	1200	form	\N	docs	Writing specification document	\N	\N	mit	debian_course
nes	2007-10-02	660	840	form	\N	docs	Write document about bash programing	\N	\N	mit	debian_course
nes	2007-10-02	1080	1200	form	\N	docs	Write document about bash programing	\N	\N	mit	debian_course
nes	2007-10-03	660	840	form	\N	docs	Write document about bash programing	\N	\N	mit	debian_course
nes	2007-10-03	1080	1200	form	\N	docs	Write document about bash programing	\N	\N	mit	debian_course
nes	2007-10-04	660	840	devt	\N	dev	Script to install new tools	\N	\N	mit	debian_course
nes	2007-10-04	1080	1200	form	\N	docs	Write document about bash programing	\N	\N	mit	debian_course
nes	2007-10-05	660	840	form	\N	docs	Write document about bash programing	\N	\N	mit	debian_course
nes	2007-10-05	1080	1200	form	\N	docs	Write document about bash programing	\N	\N	mit	debian_course
nes	2007-10-08	720	840	form	\N	comm	Emails and chat with teachers in order to finish documentation about bash programing	\N	\N	mit	debian_course
nes	2007-10-08	1080	1200	form	\N	analysis	Read documentation about drivers for kernel 2.6	\N	\N	mit	debian_course
nes	2007-10-09	720	840	form	\N	analysis	Read documentation about drivers for kernel 2.6	\N	\N	mit	debian_course
nes	2007-10-09	1080	1200	form	\N	analysis	Read documentation about drivers for kernel 2.6	\N	\N	mit	debian_course
nes	2007-10-10	720	840	form	\N	comm	Talking with teachers about drivers documentation and write first version	\N	\N	mit	debian_course
nes	2007-10-10	1080	1200	form	\N	dev	Write slides and docs about drivers into kernel 2.4	\N	\N	mit	debian_course
nes	2007-10-11	720	840	form	\N	dev	Write slides and docs about drivers into kernel 2.4	\N	\N	mit	debian_course
nes	2007-10-11	1080	1200	form	\N	dev	Write slides and docs about drivers into kernel 2.4	\N	\N	mit	debian_course
nes	2007-10-12	720	840	form	\N	dev	Write slides and docs about drivers into kernel 2.4	\N	\N	mit	debian_course
nes	2007-10-12	1080	1200	form	\N	dev	Write slides and docs about drivers into kernel 2.4	\N	\N	mit	debian_course
andres	2007-10-01	480	720	devt	\N	dev	Programing class to wrapper access database	STR_DEV04	\N	igalia	phpreport
andres	2007-10-01	840	1080	devt	\N	dev	Programing class to wrapper access database	STR_DEV04	\N	igalia	phpreport
andres	2007-10-02	480	720	devt	\N	dev	Programing class to wrapper access database	STR_DEV04	\N	igalia	phpreport
andres	2007-10-02	840	1080	devt	\N	dev	Programing class to wrapper access database	STR_DEV04	\N	igalia	phpreport
andres	2007-10-03	480	720	devt	\N	dev	Programing function to change permissions	STR_DEV04	\N	igalia	phpreport
andres	2007-10-03	840	1080	devt	\N	dev	Programing function to edit queries	STR_DEV04	\N	igalia	phpreport
andres	2007-10-04	480	720	devt	\N	dev	Programing function to change permissions	STR_DEV04	\N	igalia	phpreport
andres	2007-10-04	840	1080	devt	\N	dev	Programing function to edit queries	STR_DEV04	\N	igalia	phpreport
andres	2007-10-05	480	720	devt	\N	dev	Programing function to edit queries	STR_DEV04	\N	igalia	phpreport
andres	2007-10-05	840	1080	devt	\N	dev	Programing function to edit queries	STR_DEV04	\N	igalia	phpreport
andres	2007-10-08	480	720	devt	\N	dev	Programing function to edit queries	STR_DEV04	\N	igalia	phpreport
andres	2007-10-08	840	1080	devt	\N	dev	Building class to manage admin profile	STR_DEV05	\N	igalia	phpreport
andres	2007-10-09	480	720	devt	\N	dev	Building class to manage admin profile	STR_DEV05	\N	igalia	phpreport
andres	2007-10-09	840	1080	devt	\N	dev	Building class to manage admin profile	STR_DEV05	\N	igalia	phpreport
andres	2007-10-10	480	720	devt	\N	dev	Building class to manage user profile	STR_DEV05	\N	igalia	phpreport
andres	2007-10-10	840	1080	devt	\N	dev	Building class to manage user profile	STR_DEV05	\N	igalia	phpreport
andres	2007-10-11	480	720	devt	\N	dev	Building class to manage user profile	STR_DEV05	\N	igalia	phpreport
andres	2007-10-11	840	1080	devt	\N	dev	Building class to manage user profile	STR_DEV05	\N	igalia	phpreport
andres	2007-10-12	480	720	devt	\N	docs	Review code and write documentation about it	STR_DEV05	\N	igalia	phpreport
andres	2007-10-12	840	1080	devt	\N	docs	Review code and write documentation about it	STR_DEV05	\N	igalia	phpreport
\.


--
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: -
--

COPY users (uid, "password", "admin", staff) FROM stdin;
andres	231badb19b93e44f47da1bd64a8147f2	f	t
amaneiro	f8b4a886169853f0dc54b9282583e8d5	f	t
nes	1d7b2eac3b0ae5d9f772a801186d6c71	f	t
admin	21232f297a57a5a743894a0e4a801fc3	t	t
\.


--
-- PostgreSQL database dump complete
--

