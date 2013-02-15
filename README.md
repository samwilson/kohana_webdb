# What is WebDB?

WebDB is a database web interface aimed at non-technical users, providing simple
means to navigate and edit a database.  It is akin to phpMyAdmin, in that it
presents the data and structure of databases to the user for viewing and
modification, but where phpMyAdmin is a database administrator's tool, WebDB
tries to make things pretty and easy for users who are primarily interested in
the *data* (and not the schema).  For example: field names are properly
capitalised and formatted; foreign keys are presented as links to their referenced
records; and edit form fields are all matched to their underlying data types to
make it as easy as possible to enter data.  Many other features make WebDB a
(hopefully) useful prototyping or bare-bones data base interface.

* **Established:** March 2008
* **Author:** Sam Wilson
* **Licence:** Simplified BSD License
* **Homepage:** [http://github.com/samwilson/kohana_webdb](http://github.com/samwilson/kohana_webdb)

## Quick Start

1. Download and uncompress in a web-accessible location.
2. Copy application/bootstrap.dist.php to application/bootstrap.php and edit the
   basic configuration therein.
3. Copy at a configuration files:
   `APPPATH/config/database.php` to `APPPATH/config/database.php`,
   `APPPATH/config/database.php` to `APPPATH/config/database.php`,
3. Set up database information and an authentication source:
   1. Set username and password to NULL in `APPPATH/application/config/database.php`,
      and Auth driver to `'db'` in `APPPATH/application/config/auth.php`;
   2. **Or** set all credentials in `APPPATH/application/config/database.php`
      and elect to use a different Auth driver (such as ORM or file) in
      `APPPATH/application/config/auth.php`.

   If you do the latter, you will also need to set up whatever else is required
   for your chosen Auth driver.
3. That's it!  You can now log in, and unless you set up the additional
   WebDB-specific user authorization measures (described below), you will have
   complete access to view and modify everything (depending on your DB-level
   authorization).

## Configuration

The following configuration files are required:

* Copy `MODPATH/cache/config/database.php` to `APPPATH/cache/auth.php`
* Copy `MODPATH/auth/config/auth.php` to `APPPATH/config/auth.php`

These other config files are not required unless you wish to change from the default
values:

* Copy `MODPATH/database/config/database.php` to `APPPATH/config/database.php`
* Copy `APPPATH/config/webdb.dist.php` to `APPPATH/config/webdb.php`
* Copy `MODPATH/kadldap/config/kadldap.php` to `APPPATH/config/kadldap.php`

## Schema Structure and Nomenclature

For its records be edited or viewed, tables should have single-column primary
keys, with values that can be included in URLs.  For example, autoincrementing integers.

## Access Control

WebDB has a number of different options for user authentication and
authorisation.  Credentials can be supplied from the Database configuration
file, user input, or elsewhere (such as an LDAP server) — and are generally a
combination of these.  How they are combined, and what the options are for
further refining users' permissions, are detailed below.

1. If valid connection details are provided in `APPPATH/config/database.php`
   then these will be used to connect to the database.  The user will at this
   point have all of the permissions of the database user.  Note that if the
   MySQL anonymous user (who has an empty username) is present, then its access
   will apply at this point.
2. WebDB user authentication is handled by the Auth module, and so any drivers
   for that module can be used.  The DB driver, provided with WebDB, can be used
   to authenticate as MySQL users.
3. If a valid MySQL user is given in the Database configuration file, and a
   driver other than DB is selected in the Auth configuration file, then users
   can authenticate against that other driver, and if logged in will have all of
   the permissions of the MySQL user.
4. With the addition of 'permissions' tables to the system, WebDB will run with
   full *table-* and *row-level* user permissions.  This means that an
   unauthenticated user will see nothing by default (unless the wildcard `*` is
   specified for some permissions), and the site administrator must explicitly
   grant all privileges.

The `webdb_permissions` table schema:

    CREATE TABLE IF NOT EXISTS `webdb_permissions` (
        `id`            int(5)        NOT NULL PRIMARY KEY AUTO_INCREMENT,
        `database_name` varchar(65)   NOT NULL DEFAULT '*' COMMENT 'A single database name, or an asterisk to denote all databases.',
        `table_name`    varchar(65)   NOT NULL DEFAULT '*' COMMENT 'A single table name, or an asterisk to denote all tables.',
        `column_names`  varchar(1000) NOT NULL DEFAULT '*' COMMENT 'A comma-delimited list of table columns, or an asterisk to denote all columns.',
        `where_clause`  varchar(200)  NULL DEFAULT NULL COMMENT 'The SQL WHERE clause to use to determine row-level access.',
        `permission`    enum('*','Select','Insert','Update','Delete','Import','Export') NOT NULL DEFAULT '*' COMMENT 'The permission that is being assigned (the asterisk denotes all).',
        `identifier`    varchar(65)   NOT NULL DEFAULT '*' COMMENT 'A single username or a role name, or asterisk to denote ALL databases.'
    ) COMMENT 'User permissions on databases, tables, and/or rows.';

The name of this table can be changed in the WebDB configuration file at
`APPPATH/config/webdb.php`.  If no database is set in the config, the table must
be present in the current database.  If it is not, the permissions of the MySQL
user will be used.

## Other Features

* **Table and column comments** are displayed wherever appropriate.  For MySQL,
  column comments are limited to 255 characters, and table comments to only 60
  characters.
* **Foreign keys** are shown as links to their foreign rows, or (when being edited)
  as autocomplete drop-down lists.
* Users can **filter** by any column and a range of operations ('contains',
  'is empty', 'equals', etc.).  This includes searching foreign keys for values
  found in the 'title column' of the foreign table.
* The first non-primary unique key is used as a table's 'title column', and
  displayed wherever a table is referred to from another table (i.e. from a
  foreign key).

## Simplified BSD License

Copyright &copy; 2010, Sam Wilson.  All rights reserved.

Redistribution and use in source and binary forms, with or without modification,
are permitted provided that the following conditions are met:

* Redistributions of source code must retain the above copyright notice, this
  list of conditions and the following disclaimer.
* Redistributions in binary form must reproduce the above copyright notice, this
  list of conditions and the following disclaimer in the documentation and/or
  other materials provided with the distribution.

This software is provided by the copyright holders and contributors "as is" and
any express or implied warranties, including, but not limited to, the implied
warranties of merchantability and fitness for a particular purpose are
disclaimed. In no event shall the copyright holder or contributors be liable for
any direct, indirect, incidental, special, exemplary, or consequential damages
(including, but not limited to, procurement of substitute goods or services;
loss of use, data, or profits; or business interruption) however caused and on
any theory of liability, whether in contract, strict liability, or tort
(including negligence or otherwise) arising in any way out of the use of this
software, even if advised of the possibility of such damage.
