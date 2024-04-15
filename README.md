# DB Anonymizer

This tool can grab a full/partial copy of some database and anonymize personal
or other sensitive data on the fly.

## Why do I need it?

Have you ever faced some issues that require you to copy a DB from live site
to your local site to debug and fix it? In some cases this may be not a straight
forward task, because:
- Live DB may take dozens GBs of space.
- It may contain some sensitive data that developers should never see,
  e.g: real names, passport numbers, phone numbers, etc.

Using DB Anonymizer you can create a partial DB dump that has for example 5%
of actual data and anonymize it. And yes - if some table has related data in
other tables, it could be exported as well. You can configure the amount of data
to export for each table.

## How to Use

At the moment PostgreSQL only is supported, but other DBMS support may be
added in the future.

It requires a config file where you can specify export and anonymization rules
for tables of your DB (see `.example.dbaconfig.yml`). It may take some time
to create such config for your project, especially if database has many tables,
but once it's done you can use it as many times as needed.

### Initial setup

1. Create a copy of `.env` file if needed - `.env.local`.
2. Change these values accordingly:  
   - `PG_DUMP` - path to **pg_dump**. Example: `PG_DUMP=/etc/pg_dump`.  
   - `DATABASE_DUMP_FOLDER` - folder to save db dumps to. Example: `DATABASE_DUMP_FOLDER=/var/database-dumps`.  

### Export data

You can use `app:db-export` command to export the data. 
```shell
php bin/console app:db-export <dsn> <config-path>
```

Arguments:
- dsn - the DB credentials. Should match the pattern:
  "driver://user:password@host:port/database".
- config-path - a path to the project config file.

Example:
```shell
cd /path/to/dbanonymizer
php bin/console app:db-export "pdo-pgsql://poirot:HeRcUlE@agatha.portal:32888/portal_main" portal.dbaconfig.yml
```

Or if you don't have php/postgresql installed on your machine, but have lando:
```shell
cd /path/to/dbanonymizer
lando start
lando console app:db-export "pdo-pgsql://poirot:HeRcUlE@agatha.portal:32888/portal_main" portal.dbaconfig.yml
```

Or if you want to dump a db from another docker/lando container:
```shell
# Start your project container, get its internal docker url and then run:
cd /path/to/dbanonymizer
lando start
lando console app:db-export "pdo-pgsql://poirot:HeRcUlE@database.myportal.internal/portal_main" portal.dbaconfig.yml
```

#### Notes

1. The `app:db-export` command relies on random numbers to export data (random
   rows) from DB tables, it means that if you want to get 10% of data from some
   table then you may get slightly different number of rows as a result.
2. Due to that be careful with tiny tables. For example: if you configure to get
   2% from table with 100 rows in total then you may actually get nothing.

### Import data

Once export is completed you get a bunch of sql files, and you might want
to join them into a single file. However, in some cases this single file will
produce errors during the import. But there is a workaround - import
DB structure first and then import data. So here's how you can do that:
```shell
cd /path/to/dumps
# Move DB structure file outside of a dump folder. 
mv my_latest_dump/00_mydb_structure.sql ./
# Join other files to get a single data dump file.
cat my_latest_dump/*.sql > data.sql
# Import DB as usual.
psql databasename < 00_mydb_structure.sql
psql databasename < data.sql
```
