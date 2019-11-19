<?php

require_once('rtzCSVtoSQL.php');

$file = '2019';
$year = 2019;

//column in resourse file
$column_amount = 19; //для файлов с названием 2013-2014, 2018-all, 2019
//$column_amount = 21; //для файлов с названием 2015-2017, 2018-old

$update_year = ['year' => 2019, 'file' => '2019', 'last_updated' => '2019-10-01'];

//connection to databases
$dbConnection = pdoConnection();

//parse file
$parse_obj = new CSVtoSQLController();

$resource = __DIR__."/resource/$file.csv";

$parse_obj->parse($resource, $dbConnection, $column_amount, $year, $file, $update_year);

/**
 * PDO-connection for main database
 *
 * @return obj PDO
 */
function pdoConnection()
{
	$db = 'database_name';
	$user = 'username';
	$pass = 'pass';
	$dbh = new \PDO('mysql:host=127.0.0.1;dbname='.$db.';charset=utf8', $user, $pass);
	return ($dbh);
}