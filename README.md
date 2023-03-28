# DB Anonymizer

This tool can grab a full/partial copy of some database and anonymize personal
or other sensitive data.

## How to Use

1. Copy .env to .env.local
2. Change these values to your own:  
   PG_DUMP - path to **pg_dump**. Example, PG_DUMP=/etc/pg_dump  
   DATABASE_DUMP_FOLDER - local dump storage. Example, DATABASE_DUMP_FOLDER=/var/database-dumps  

### Commands

#### app:db-export <dsn>

Create DB dump. See DATABASE_DUMP_FOLDER in ENV file.

Usage:
```
php bin/console app:db-export <dsn>
```

Arguments:
- dsn - the DB credentials. Should match the pattern:
  "driver://user:password@host:port/database"

## Local development

We use [lando](https://lando.dev/) for development. Just run `lando start` and
it would create necessary containers for you.

### PHP Code Sniffer Configuration

PHP Code Sniffer (phpcs) is used to detect violations of a defined coding
standard. In this project it is executed automatically on `git push`, but also
can be executed manually.

It's recommended to install phpcs globally to avoid duplication while working
with multiple projects. Or to use git commands from lando container as
all necessary configuration is already done there.

Note: in the example below we use `~/.config/composer/vendor/bin` path,
if you have a different path then replace it with your value.

1. Install phpcs with symfony rulesets:
   ` composer global require escapestudios/symfony2-coding-standard`.
2. To make `phpcs` and `phpcbf` commands available globally you may add the next
   path to the `$PATH` variable (or just add a whole line) to the file you use -
   `~/.profile`, `~/.bash_profile`, `~/.bashrc`, `~/.zshrc`:
   ```
   export PATH="$PATH:$HOME/.config/composer/vendor/bin"
   ```
   More likely you need to add this to `~/.bashrc` on Windows:
   ```
   set PATH="%AppData%/Composer/vendor/bin:$PATH"
   ```
3. Probably you have to close your terminal and open it again to make new
   settings work.
4. Check if phpcs is working: `phpcs -i`.
5. Activate git hooks:
   ```
   cd path/to/project_root
   sh .gitlab/_scripts/init-hooks.sh
   ```

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
4. Run psalm:
   ```
   lando psalm
   ```
