# DB Anonymizer

## Local development

We use [lando](https://lando.dev/) for development.

1. Just run `lando start` and it would create necessary containers for you.
2. Configure phpcs and git hooks using giho (see giho readme for details):
   ```
   lando ssh
   bash vendor/iac/giho/bin/init.sh
   ```

### Useful Commands

1. Execute symfony console command:
   ```
   lando console ...
   ```
2. Run some php script for debugging:
   ```
   lando phpdebug public/index.php
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

Runs phpcs command to detect files in the whole project.
```
lando phpcs ./src
```

Runs phpcbf command to fix files in the whole project.
```
lando phpcbf ./src
```
