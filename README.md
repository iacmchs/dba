# DB Anonymizer

This tool can grab a full/partial copy of some database and anonymize personal
or other sensitive data.

## Local development

We use [lando](https://lando.dev/) for development. Just run `lando start` and
it would create necessary containers for you.

### Useful commands

1. Execute symfony console command:
   ```
   lando console ...
   ```
2. Debug some php cli command for debug:
   ```
   lando phpdebug public/index.php
   ```
