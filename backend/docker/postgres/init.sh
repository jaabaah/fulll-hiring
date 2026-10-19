#!/bin/sh
# Run once by the postgres image on first start: creates the test database
# and applies the schema to both databases.
set -eu

psql -v ON_ERROR_STOP=1 -U "$POSTGRES_USER" -d "$POSTGRES_DB" -c 'CREATE DATABASE fleet_test'

for database in "$POSTGRES_DB" fleet_test; do
    psql -v ON_ERROR_STOP=1 -U "$POSTGRES_USER" -d "$database" -f /schema/schema.sql
done
