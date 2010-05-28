WebDB, a database user-interface module for Kohana 3
================================================================================

WebDB is a database web interface aimed at non-technical users, providing simple
means to navigate and edit a database.  It is akin to phpMyAdmin, in that it
presents the data and structure of databases to the user for viewing and
modification, but where phpMyAdmin is a database administrator's tool, WebDB
tries to make things pretty and easy for users who are primarily interested in
the *data* (and not the schema).  For example: field names are properly
capitalised and cased; foreign keys are presented as links to their referenced
records; and edit form fields are all matched to their underlying data types to
make it as easy as possible to enter data.

* Established: March 2009
* Author: Sam Wilson
* Licence: Simplified BSD License
* Homepage: http://github.com/samwilson/kohana_webdb

__Dependencies:__

This module requires these additional Kohana 3 modules:
[Calendar](http://github.com/samwilson/kohana_calendar);
[Database](http://github.com/kohana/database);
and [Pagination](http://github.com/kohana/pagination).
[Auth](http://github.com/kohana/auth) is used for user authentication if it is
present.

Database schema naming and structure conventions
--------------------------------------------------------------------------------

Much the same as those of the ORM module, but more flexible.  *Details will be
coming soon.*

Access Control
--------------------------------------------------------------------------------

WebDB has a number of different options for user authentication and
authorisation.  Credentials can be supplied from the Database configuration
file, HTTP Basic Auth, or user input -- and are generally a combination of
these.  How they are combined, and what your options are for further refining
users' permissions, are detailed below.

1. If valid connection details are provided in
   `APPPATH/application/config/database.php` then these will be used to connect
   to the database.
2. If not, the user will be prompted for a username and password and these will
   be used to connect.  In this case, authorisation is taken to be whatever
   the DBMS reports it to be (i.e. the below authorisation systems are not
   used).  If no hostname is given, the application will exit with a message at
   this point.
3. If a connection is made with credentials from the configuration file, then it
   is most likely that there will be further application-level access control
   -- this will happen with the Auth module (see the next item).  However, if
   the Auth module is *not* present, then WebDB will run in read-only mode.
4. If the Auth module is present, but without the WebDB additions (see next
   item), then users will be able to log in.  Initially, they will not be logged
   in and will have read-only access.  After they've logged in, they will have
   complete read-write access to everything that the DBMS user can see.  In
   short: anonymous users can read everything; logged-in users can edit
   everything.
5. With the addition of the `role_privileges` table to the Auth tables, WebDB
   will run with full table- and row-level user privileges.  This means that an
   unauthenticated user will see nothing by default (unless the special user
   `anonymous` is granted some privileges), and the site administrator must
   explicitly grant all privileges.

Sources of user credentials:
Database config file
HTTP Basic Auth
Auth Module