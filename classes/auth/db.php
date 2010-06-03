<?php defined('SYSPATH') or die('No direct access allowed.');
/**
 * Database-user Auth driver, used to authenticate as a user of the DBMS (rather
 * than a user whose credentials are stored in the database).
 *
 * To use, simply:
 * 
 *  - set `'driver' => 'db'` in the `APPPATH/config/auth.php` config file,
 *  - do not set the username or password in the`APPPATH/config/database.php`
 *    config file,
 *  - and make sure a valid database name is given in that latter file.
 *
 * For example, a minimal `APPPATH/config/auth.php` looks like this:
 *
 *     <?php defined('SYSPATH') or die('No direct access allowed.');
 *     return array(
 *         'driver' => 'db'
 *     );
 *
 * ...and a minimal `APPPATH/config/database.php` could be this:
 *
 *     <?php defined('SYSPATH') OR die('No direct access allowed.');
 *     return array (
 *         'default' => array (
 *             'connection' => array(
 *                 'hostname'   => 'db.example.net', // But usually 'localhost'
 *                 'database'   => 'db_name_here',
 *              ),
 *         ),
 *     );
 *
 * (Of course, if your hast and database names match the defaults as given in
 * `MODPATH/database/config/database.php` then you don't need your own Database
 * config file at all!  How easy is that?!)
 *
 * @package  Auth
 * @author   Sam Wilson
 * @license  Simplified BSD License
 * @link     http://github.com/samwilson/kohana_webdb
 */
class Auth_Db extends Webdb_Auth_Db
{

}