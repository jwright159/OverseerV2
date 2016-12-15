<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/database.php');
$username = mysqli_escape_string($connection, $_POST['username']);
$userResult = mysqli_query($connection, "SELECT * FROM `Users` WHERE `username` = '$username';");

if(mysqli_num_rows($userResult) == 0){
	echo '<div class="container"><div class="alert alert-warning" role="alert">User doesn\'t exist!</div></div><br>';
} else {
	$userRow = mysqli_fetch_array($userResult); //
	$password = mysqli_escape_string($connection, $_POST['password']);
	$correctPass = password_hash($password, $userRow['password']);
	if ($correctPass == 1) {
		echo '<div class="container"><div class="alert alert-success" role="alert">Login successful</div></div>';
		$_SESSION['userID'] = $userRow['ID'];
		$_SESSION['username'] = $userRow['username'];
	} else { 
	echo '<div class="container"><div class="alert alert-warning" role="alert">Error: Invalid username/password combination</div></div><br>';
	}
}