<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/database.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/accrow.php');

function pickSessionType($userID) {
	global $connection;
	$userRow = mysqli_fetch_array(mysqli_query($connection, "SELECT * FROM `Users` WHERE `ID` = '$userID';"));
	$hardmodeEnabled = $userRow['completedsession'];	//Is a boolean
	if ($hardmodeEnabled == 0) {
		$sessionTypePicked = mysqli_fetch_array(mysqli_query($connection, "SELECT * FROM `SessionTypes` WHERE `hardmode` = 0 AND `special` = 0 AND `ID` > 3 ORDER BY RAND() LIMIT 1;"));
	} else {	
		$sessionTypePicked = mysqli_fetch_array(mysqli_query($connection, "SELECT * FROM `SessionTypes` WHERE `special` = 0 AND `ID` > 3 ORDER BY RAND() LIMIT 1;"));
	}
	$primaryType = rand(0,1); //Random primary type. Normal = 0, Null = 1
	$typeString = $primaryType.'|'.$sessionTypePicked['ID'];
	return $typeString;
}




$sessionName = mysqli_escape_string($connection, $_POST['sessionName']);
$userCreating = $accountRow['ID'];
$sessionPassword = mysqli_escape_string($connection, $_POST['sessionPassword']);
$confPass = mysqli_escape_string($connection, $_POST['confPass']);

if ($sessionName == '' || $sessionName == null) {
	    echo '<div class="container"><div class="alert alert-danger" role="alert">You must name your session.</div></div>';
} else {
	$nameCheck = mysqli_query($connection, "SELECT `ID` FROM `Sessions` WHERE `name` = '$sessionName';");
	if (mysqli_num_rows($nameCheck) > 0) {
        echo '<div class="container"><div class="alert alert-danger" role="alert">This session name is already in use.</div></div>';
	} elseif ($sessionPassword != $confPass || empty($sessionPassword)) {
        echo '<div class="container"><div class="alert alert-danger" role="alert">Passwords didn\'t match.</div></div>';
    } else {
    	$sessionType = pickSessionType($userCreating);
    	$hashedPass = password_hash($sessionPassword, PASSWORD_DEFAULT);
    	mysqli_query($connection, "INSERT INTO `Sessions` (name, creator, password, type) VALUES ($sessionName, $userCreating, $hashedPass, $sessionType);");
    	echo '<div class="container"><div class="alert alert-success" role="alert">Success!</div></div>';
    }
}
?>