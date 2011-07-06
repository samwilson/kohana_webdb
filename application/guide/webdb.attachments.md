# Attaching Files

Most database-backed web applications 
	store non-application files (such as uploaded images) in the filesystem 
	rather than as objects in the database.
These files relate to tables and rows in the databases in various ways,
and there are many different ways of recording these relationships.
Here we investigate the methods used by a number of web applications to store file relationship information in their databases.

The web applications examined here all

* use a database; and
* store attached files in the filesystem.

For each application, we aim to answer the following questions:

1. How is a file is added to the application?
2. How is a file's filesystem path retrieved and displayed to the user?

Ultimately, we are trying to determine when and where to allow file attachments, and where to save them.

## WordPress

[WordPress](http://wordpress.org/) is a blogging platform in which uploaded files are saved to a user-configurable uploads directory.
Files in this directory can be organised into whatever subdirectories the user desires (by default, named by year and month).
Each uploaded file has a single row in the <code>posts</code> table
    (which may or may not be linked to an actual post row via the <code>post_parent</code> key).
The <code>post_type</code> column of this row is 'attachment'.
Rows in the <code>postmeta</code> table are linked to this row.
Where the <code>meta_key</code> column of <code>postmeta</code> is '_wp_attached_file', the <code>meta_value</code> column is a filename, relative to the WP upload directory.

![WordPress ERD](webdb/img/guide/wordpress_erd.jpg)