<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/database.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/accrow.php');

if (isset($_GET['id'])) {
	$charID = mysqli_escape_string($connection, $_GET['id']);
	$charQuery = mysqli_query($connection, "SELECT `owner` FROM `Characters` WHERE `ID` = '$charID';");
	if (mysqli_num_rows($charQuery) == 0) {
		echo 'No such character.';
	} else {
		$charRow = mysqli_fetch_array($charQuery);
		if ($charRow['owner'] != $accountRow['ID']) {
			echo 'That character doesn\'t belong to you!';
		} else {
			$_SESSION['character'] = $charID;
			header('Location: /');
		}
	}
} else {
	// unselect character
	unset($_SESSION['character']);
	header('Location: /');
}

?>
