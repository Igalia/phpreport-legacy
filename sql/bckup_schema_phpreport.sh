#!/bin/bash
# only backup schema from phpreport15 database

#chown postgres:postgres schema_phpreport.sql
#chmod 777 schema_phpreport.sql
#su postgres

pg_dump > schema_phpreport -h localhost -U phpreport15 -sO phpreport15
