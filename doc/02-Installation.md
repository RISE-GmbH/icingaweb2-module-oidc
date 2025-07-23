# Installation <a id="module-oidc-installation"></a>

## Requirements <a id="module-oidc-installation-requirements"></a>

* Icinga Web 2 (&gt;= 2.12.1)
* PHP (&gt;= 7.3)


## Installation from .tar.gz <a id="module-oidc-installation-manual"></a>

Download the latest version and extract it to a folder named `oidc`
in one of your Icinga Web 2 module path directories.

## Enable the newly installed module <a id="module-oidc-installation-enable"></a>

Enable the `oidc` module either on the CLI by running

```sh
icingacli module enable oidc
```

Or go to your Icinga Web 2 frontend, choose `Configuration` -&gt; `Modules`, chose the `oidc` module and `enable` it.

It might afterwards be necessary to refresh your web browser to be sure that
newly provided styling is loaded.

## Setting up the Database

### Setting up a MySQL or MariaDB Database

The module needs a MySQL/MariaDB database with the schema that's provided in the `/usr/share/icingaweb2/modules/oidc/schema/mysql.schema.sql` file.

You can use the following sample command for creating the MySQL/MariaDB database. Please change the password:

```
CREATE DATABASE oidc;
GRANT CREATE, SELECT, INSERT, UPDATE, DELETE, DROP, ALTER, CREATE VIEW, INDEX, EXECUTE ON oidc.* TO oidc@localhost IDENTIFIED BY 'secret';
```

After, you can import the schema using the following command:

```
mysql -p -u root oidc < /usr/share/icingaweb2/modules/oidc/schema/mysql.schema.sql
```

## Set a Database / Choose a backend - Web

Create a Database as usual and set is a database for the oidc module


## Config via CLI

### Select a database resource

You can run the following command choose a resource:

```
sudo -u www-data icingacli oidc set resource --name NAMEOFYOURDATABASERESOURCE
```
