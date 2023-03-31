# DB Anonymizer

This tool can grab a full/partial copy of some database and anonymize personal
or other sensitive data.

## How to Use

1. Create a copy of `.env` file - `.env.local`.
2. Change these values if needed:  
   - `PG_DUMP` - path to **pg_dump**. Example: `PG_DUMP=/etc/pg_dump`.  
   - `DATABASE_DUMP_FOLDER` - folder to save db dumps to. Example: `DATABASE_DUMP_FOLDER=/var/database-dumps`.  

### Commands

#### app:db-export <dsn>

Create DB dump. See DATABASE_DUMP_FOLDER in ENV file.

Usage:
```
php bin/console app:db-export <dsn>
```

Arguments:
- dsn - the DB credentials. Should match the pattern:
  "driver://user:password@host:port/database".

## Local development

We use [lando](https://lando.dev/) for development. Just run `lando start` and
it would create necessary containers for you.

### Useful Commands

1. Execute symfony console command:
   ```
   lando console ...
   ```
2. Debug some php cli command for debug:
   ```
   lando phpdebug public/index.php
   ```
3. Run tests:
   ```
   lando test 
   ```

#### Code Sniffer Commands

Runs phpcs command to detect files updated on current git branch.
```
lando phpcs:branch
```

Runs phpcbf command to fix files updated on current git branch.
```
lando phpcbf:branch
```

Runs phpcs command to detect files in whole project.
```
lando phpcs
```

Runs phpcbf command to fix files in whole project.
```
lando phpcbf
```
