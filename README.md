# DB Anonymizer

This tool can grab a full/partial copy of some database and anonymize personal
or other sensitive data.

It requires a config file where you can specify export and anonymization rules
for tables of your DB (see `.example.dbaconfig.yml`). It may take some time
to create such config for your project, especially if database has many tables,
but once it's done you can use it as many times as needed.

## How to Use

1. Create a copy of `.env` file if needed - `.env.local`.
2. Change these values accordingly:  
   - `PG_DUMP` - path to **pg_dump**. Example: `PG_DUMP=/etc/pg_dump`.  
   - `DATABASE_DUMP_FOLDER` - folder to save db dumps to. Example: `DATABASE_DUMP_FOLDER=/var/database-dumps`.  

### Commands

#### app:db-export <dsn>

Create DB dump. See `DATABASE_DUMP_FOLDER` in `.env` file.

Usage:
```
php bin/console app:db-export <dsn> <config-path>
```

Arguments:
- dsn - the DB credentials. Should match the pattern:
  "driver://user:password@host:port/database".
- config-path - a path to the project config file.

Example:
```
cd /path/to/dbanonymizer
php bin/console app:db-export "pdo-pgsql://poirot:HeRcUlEs@agatha.portal:32888/portal_main" portal.dbaconfig.yml
```

Or if you don't have php installed on your machine, but have lando:
```
cd /path/to/dbanonymizer
lando start
lando console app:db-export "pdo-pgsql://poirot:HeRcUlEs@agatha.portal:32888/portal_main" portal.dbaconfig.yml
```

Or if you want to dump a db from another docker/lando container:
```
# Start a db container, get its internal docker url and then run:
cd /path/to/dbanonymizer
lando start
lando console app:db-export "pdo-pgsql://poirot:HeRcUlEs@database.container.internal/portal_main" portal.dbaconfig.yml
```

## Notes

1. The `app:db-export` command relies on random numbers to export data (random
rows) from DB tables, it means that if you want to get 10% of data from some
table then you may get slightly different number of rows as a result.
2. Due to that be careful with tiny tables. For example: if you configure to get
2% from table with 100 rows in total then you may actually get nothing.
