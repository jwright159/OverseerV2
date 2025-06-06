FROM mysql:9.3

ARG MYSQL_DATABASE

# Copy over schema
COPY ./database.sql /docker-entrypoint-initdb.d/database.sql
RUN sed -i 's/MYSQL_DATABASE/'$MYSQL_DATABASE'/g' /docker-entrypoint-initdb.d/database.sql
