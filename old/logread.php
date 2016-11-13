<?php
$pagetitle = "CHARACTER LOG";
require_once("header.php");

if (empty($_SESSION['username'])) {
	echo "Choose a character to see their log.<br>";
} else {
	$charID = $charrow['ID'];
	$logContent = readCharLog($charID);
	echo $logContent;
	echo '<br><br><br> Your log has now been cleared to save space.<br>';
}

require_once("footer.php");