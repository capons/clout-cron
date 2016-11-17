<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
| -------------------------------------------------------------------
| DATABASE CONNECTIVITY SETTINGS
| -------------------------------------------------------------------
| This file will contain the settings needed to access your database.
|
| For complete instructions please consult the 'Database Connection'
| page of the User Guide.
|
| -------------------------------------------------------------------
| EXPLANATION OF VARIABLES
| -------------------------------------------------------------------
|
|	['hostname'] The hostname of your database server.
|	['username'] The username used to connect to the database
|	['password'] The password used to connect to the database
|	['database'] The name of the database you want to connect to
|	['dbdriver'] The database type. ie: mysql.  Currently supported:
				 mysql, mysqli, postgre, odbc, mssql, sqlite, oci8
|	['dbprefix'] You can add an optional prefix, which will be added
|				 to the table name when using the  Active Record class
|	['pconnect'] TRUE/FALSE - Whether to use a persistent connection
|	['db_debug'] TRUE/FALSE - Whether database errors should be displayed.
|	['cache_on'] TRUE/FALSE - Enables/disables query caching
|	['cachedir'] The path to the folder where cache files should be stored
|	['char_set'] The character set used in communicating with the database
|	['dbcollat'] The character collation used in communicating with the database
|				 NOTE: For MySQL and MySQLi databases, this setting is only used
| 				 as a backup if your server is running PHP < 5.2.3 or MySQL < 5.0.7
|				 (and in table creation queries made with DB Forge).
| 				 There is an incompatibility in PHP with mysql_real_escape_string() which
| 				 can make your site vulnerable to SQL injection if you are using a
| 				 multi-byte character set and are running versions lower than these.
| 				 Sites using Latin-1 or UTF-8 database character set and collation are unaffected.
|	['swap_pre'] A default table prefix that should be swapped with the dbprefix
|	['autoinit'] Whether or not to automatically initialize the database.
|	['stricton'] TRUE/FALSE - forces 'Strict Mode' connections
|							- good for ensuring strict SQL while developing
|
| The $active_group variable lets you choose which connection group to
| make active.  By default there is only one group (the 'default' group).
|
| The $active_record variables lets you determine whether or not to load
| the active record class
*/

$active_group = 'pro-dw-crn1.clout.com';
$query_builder = TRUE;

$db['pro-dw-crn1.clout.com']['hostname'] = HOSTNAME_CRON_WRITE;
$db['pro-dw-crn1.clout.com']['username'] = USERNAME_CRON_WRITE;
$db['pro-dw-crn1.clout.com']['password'] = PASSWORD_CRON_WRITE;
$db['pro-dw-crn1.clout.com']['database'] = DATABASE_CRON_WRITE;
$db['pro-dw-crn1.clout.com']['dbdriver'] = DBDRIVER_CRON_WRITE;
$db['pro-dw-crn1.clout.com']['dbprefix'] = '';
$db['pro-dw-crn1.clout.com']['pconnect'] = TRUE;
$db['pro-dw-crn1.clout.com']['db_debug'] = TRUE;
$db['pro-dw-crn1.clout.com']['cache_on'] = FALSE;
$db['pro-dw-crn1.clout.com']['cachedir'] = '';
$db['pro-dw-crn1.clout.com']['char_set'] = 'utf8';
$db['pro-dw-crn1.clout.com']['dbcollat'] = 'utf8_general_ci';
$db['pro-dw-crn1.clout.com']['swap_pre'] = '';
$db['pro-dw-crn1.clout.com']['autoinit'] = TRUE;
$db['pro-dw-crn1.clout.com']['stricton'] = FALSE;
$db['pro-dw-crn1.clout.com']['port'] = DBPORT_CRON_WRITE;




$db['pro-dw-crn2.clout.com']['hostname'] = HOSTNAME_CRON_READ;
$db['pro-dw-crn2.clout.com']['username'] = USERNAME_CRON_READ;
$db['pro-dw-crn2.clout.com']['password'] = PASSWORD_CRON_READ;
$db['pro-dw-crn2.clout.com']['database'] = DATABASE_CRON_READ;
$db['pro-dw-crn2.clout.com']['dbdriver'] = DBDRIVER_CRON_READ;
$db['pro-dw-crn2.clout.com']['dbprefix'] = '';
$db['pro-dw-crn2.clout.com']['pconnect'] = TRUE;
$db['pro-dw-crn2.clout.com']['db_debug'] = TRUE;
$db['pro-dw-crn2.clout.com']['cache_on'] = FALSE;
$db['pro-dw-crn2.clout.com']['cachedir'] = '';
$db['pro-dw-crn2.clout.com']['char_set'] = 'utf8';
$db['pro-dw-crn2.clout.com']['dbcollat'] = 'utf8_general_ci';
$db['pro-dw-crn2.clout.com']['swap_pre'] = '';
$db['pro-dw-crn2.clout.com']['autoinit'] = TRUE;
$db['pro-dw-crn2.clout.com']['stricton'] = FALSE;
$db['pro-dw-crn2.clout.com']['port'] = DBPORT_CRON_READ;

/* End of file database.php */
/* Location: ./application/config/database.php */