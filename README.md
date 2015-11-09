# Roadiz CLI tools

**For the moment only compatible with MySQL/MariaDB databases.** 

- Copy database and files from a website folder to another
- Create a new Roadiz website

## Install

* Run composer for dependencies `composer install --no-dev -o`
* Copy `config.default.yml` to `config.yml` and adapt your commands to your system paths and MySQL server credentials.
* Execute `php roadiz-cli-tools roadiz:requirements` to check if every binary is available.

## Move websites contents

```bash
# Move roadiz1 to roadiz2 files and databases and backup roadiz2 contents
php roadiz-cli-tools roadiz:move -b /var/www/roadiz1 /var/www/roadiz2 roadiz1 roadiz2
```

Syntax: `roadiz:move [-b|--backup] [--] <source> <destination> <source-database> <destination-database>`

Using `backup` option, a `/path/to/dest/roadiz_backup` folder will be created to save documents files and SQL dumps.
