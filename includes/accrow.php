<?php
require_once($_SERVER['DOCUMENT_ROOT']."/includes/global_functions.php");
require_once($_SERVER['DOCUMENT_ROOT']."/includes/database.php");
if (!empty($_SESSION['username'])) {
	$username = $_SESSION['username'];
	$accountRow = mysqli_fetch_array(mysqli_query($connection, "SELECT * FROM `Users` WHERE `username` = '" . $_SESSION['username'] . "' LIMIT 1;"));
	if (!empty($_SESSION['character'])) {
		$characterRow = mysqli_fetch_array(mysqli_query($connection, "SELECT * FROM `Characters` WHERE `ID` = " . $_SESSION['character'] . " LIMIT 1;"));
		if ($characterRow['owner'] != $accountRow['ID']) {
			echo "ERROR: You tried to select a character that doesn't belong to you!";
			$_SESSION['character'] = 0; //reset character
			$characterRow = array(); //blank the character row
		} else {
			$characterID = $characterRow['ID'];
		}
		$time = time();
	}
}