<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/inc/dotenv_load.php';

// Database host
$conn_host = $_ENV['DB_HOSTNAME'];

// Database user
$conn_user = $_ENV['DB_USERNAME'];

// Database password
$conn_pass = $_ENV['DB_PASSWORD'];

// Database, er, uhm, database!
$conn_db = $_ENV['DB_DATABASE'];



////////////////////////////////////////////
// and the actual reason this file exists //
////////////////////////////////////////////
global $dbtype;
if ($dbtype == "PDO") {
	try {
		$db = new PDO('mysql:host=' . $conn_host . ';dbname=' . $conn_db . ';', $conn_user, $conn_pass);
	} catch (PDOException $e) {
		exit("Could not connect to database: " . $e->getMessage() . "<br/>");
	}
} else {
	global $connection;
	$connection = mysqli_connect($conn_host, $conn_user, $conn_pass, $conn_db);
}
