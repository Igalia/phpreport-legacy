#!/bin/sh
# Creacion de la base de datos y usuario
export DBUSER="phpreport"
export DBNAME="phpreport"
export DBPWD="phpreport"
su postgres -c "\
psql template1 -c \"CREATE USER $DBUSER WITH ENCRYPTED PASSWORD '$DBPWD'\" \
 || { echo "User exists. Aborting install."; exit 0; } ; \
psql template1 -c \"CREATE DATABASE $DBNAME WITH OWNER=$DBUSER ENCODING='latin1'\" \
 || { echo "Database exists. Aborting install."; exit 0; } ; \
if [ -f tablas.sql.gz ]; then gunzip tablas.sql.gz -c \
 > /tmp/phpreport_tablas.sql; \
else cp tablas.sql /tmp/phpreport_tablas.sql; fi
if [ -f datos.sql.gz ]; then gunzip datos.sql.gz -c \
 > /tmp/phpreport_datos.sql; \
else cp datos.sql /tmp/phpreport_datos.sql; fi
psql $DBNAME < /tmp/phpreport_tablas.sql; \
psql $DBNAME < /tmp/phpreport_datos.sql;  \
psql $DBNAME -c \"GRANT ALL PRIVILEGES ON DATABASE $DBNAME TO $DBUSER\""
rm /tmp/phpreport_tablas.sql /tmp/phpreport_datos.sql
