<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/header.php');

$characterName = trim(mysqli_escape_string($connection, $_POST['characterName']);
$species = mysqli_escape_string($connection, $_POST['species']);
$chumHandle = mysqli_escape_string($connection, $_POST['chumHandle']);
$sessionName = mysqli_escape_string($connection, $_POST['sessionName'];
$sessionPass = mysqli_escape_string($connection, $_POST['sessionPass']);

// Check all are filled in
if (empty($characterName) || $species == null || empty($chumHandle) || empty($sessionName) || empty($sessionPass)) {
	echo 'Some info was missing!';
} else {	
// Verify session exists
	$sessionCheck = mysqli_query($connection, "SELECT * FROM `Sessions` WHERE `name` = '$sessionName';");
    if (mysqli_num_rows($sessionCheck) > 0) {
    	$sessionRow = mysqli_fetch_array(mysqli_query($connection, "SELECT * FROM `Sessions` WHERE `name` = '$sessionName';"));
    	$sessionID = $sessionRow['id'];
		$correctPass = password_verify($password, $userRow['password']);
		if ($correctPass) {
			$charnameCheck = mysqli_query($connection, "SELECT * FROM `Characters` WHERE `name` = '$characterName' AND `session` = '$sessionID';");
			if (mysqli_num_rows($charnameCheck) == 0) {
				$chumHandleCheck = mysqli_query($connection, "SELECT * FROM `Characters` WHERE `chumhandle` = '$chumHandle' AND `session` = $sessionID';");
				    if (mysqli_num_rows($chumHandleCheck) > 0) {
				    	echo 'Chumhandle taken in this session!';
				    } else {
				    	// Set session vars and display the rest of the page. 
				    }

			} else {
				echo 'Character name is taken in this session!';
			}
		} else {
			echo 'Wrong password...';
		}
	} else {
		echo 'Session doesn\'t exist!';
	}

}
