#!/bin/bash
# only backup data from phpreport15 database

#chown postgres:postgres data_phpreport.sql
#chmod 777 data_phpreport.sql
#su postgres

pg_dump phpreport15 -h localhost -U phpreport15 -a -O > /tmp/data.sql
