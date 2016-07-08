# appBackup
appBackup is a simple php script that dumps an applications mysql database and archives it along side the applications code and data.

The result looks something like:
```
YYYY.MM.DD-UNIXTIMESTAMP-APPNAME.tar.gz:
- YYYY.MM.DD-UNIXTIMESTAMP-APPNAME
  - _path_to_data_directory //Does not exist when the data directory is inside the code directory.
  - _path_to_code_directory
  - YYYY.MM.DD-UNIXTIMESTAMP-APPNAME.sql
```

## Requirements
php must be able to use the ```exec()``` function. So check your ```php.ini``` ```disable_functions``` setting if you run into issues.

* php-cli
* mysqldump

## Installation
* Copy default.config.php to config.php.
* Edit it to add your applications.
* Make sure the ```tmp``` and ```logs``` directories are writable.

## Usage
### Backup all apps
* ```cd path/to/appBackup```
* ```php appBackup.php```

### Backup a selection of apps
* ```cd path/to/appBackup```
* ```php appBackup.php --- -apps "app1,app2,...,appN"```

## Notes
Logs are stored as a json array per line.

## Author
<pre>
David Reagan
david@reagannetworks.com
davidreagan.net
</pre>
