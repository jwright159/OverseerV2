<?php
session_start();
require_once __DIR__.'/../includes/bootstrap.php';

use Overseer\Models\Session;
use Overseer\Models\SessionQuery;


/*
 * Randomly selects a session type.
 * Provided the players has beaten a session, hardmode will be enabled.
 * Note that this does not protect new players from joining one!
 */
//function pickSessionType($userID) {
//	global $connection;
//	$userRow = mysqli_fetch_array(mysqli_query($connection, "SELECT * FROM `Users` WHERE `ID` = '$userID';"));
//	$hardmodeEnabled = $userRow['completedsession']; //Is a boolean
//	if ($hardmodeEnabled == 0) {
//			$sessionTypePicked = mysqli_fetch_array(mysqli_query($connection, "SELECT * FROM `SessionTypes` WHERE `hardmode` = 0 AND `special` = 0 AND `ID` > 3 ORDER BY RAND() LIMIT 1;")); // Excludes hardmode. Ordering by RAND ensures that - as we're taking the top result - a random session type is picked from the resulting set.
//	} else {
//		$sessionTypePicked = mysqli_fetch_array(mysqli_query($connection, "SELECT * FROM `SessionTypes` WHERE `special` = 0 AND `ID` > 3 ORDER BY RAND() LIMIT 1;")); // Above but hardmode is enabled.
//	}
//	$primaryType = rand(1,2); //Random primary type. Normal = 1, Null = 2
//	$chance = rand(0,100);
//	if ($hardmodeEnabled == 1) {
//		if ($chance <= 75) {
//			$typeString = $primaryType.'|'.$sessionTypePicked['ID'];
//		} else {
//			$typeString = $primaryType;
//		}
//	} else {
//		if ($chance <= 50) {
//			$typeString = $primaryType.'|'.$sessionTypePicked['ID'];
//		} else {
//			$typeString = $primaryType;
//		}
//	}
//	return $typeString;
//}

function validateSessionName($name) {
	return (bool) preg_match('/^[\w_\-\s]+$/', $name) && (strlen($name) <= 32);
}

$sessionName = $_POST['sessionName'];
$sessionPassword = $_POST['sessionPassword'];
$confirmPassword = $_POST['confPass'];

if (empty($sessionName)) {
	echo '<div class="container"><div class="alert alert-danger" role="alert">You must name your session.</div></div>';
} else {
	if (SessionQuery::create()->findOneByName($sessionName)) { // if we can find a session with this name
		echo '<div class="container"><div class="alert alert-danger" role="alert">This session name is already in use.</div></div>';
	} elseif ($sessionPassword !== $confirmPassword || empty($sessionPassword)) {
		echo '<div class="container"><div class="alert alert-danger" role="alert">Passwords didn\'t match, or were empty.</div></div>';
	} else {
		$session = new Session();
		$session->fromArray(["Name" => $sessionName, "Password" => password_hash($sessionPassword, PASSWORD_DEFAULT)]);
		$session->setOwner($currentUser);
		$session->save();

		echo '<div class="container"><div class="alert alert-success" role="alert">Success!</div></div>';
	}
}
