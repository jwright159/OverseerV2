<?php
require_once($_SERVER['DOCUMENT_ROOT'] . "/header.php");

if (empty($_SESSION['username'])) {
	echo "Log in to do stuff.<br />";
} elseif ($accrow['modlevel'] < 10) {
	echo "You don't have permission to run this.<br />";
} else {
	$allabs = "";
	$donotadd = array("", "notaweapon", "jokerkind", "metakind", "jpegkind"); //any abstrati that shouldn't be selectable go here
	$autoadd = array(); //any abstrati that should be selectable but might not get picked up by the script go here
	$itemresult = mysqli_query($connection, "SELECT * FROM Captchalogue ORDER BY abstratus ASC");
	while ($row = mysqli_fetch_array($itemresult)) {
		$abs = explode(", ", $row['abstratus']);
		if (!in_array($abs[0], $donotadd)) {
			echo "Adding " . $abs[0] . " (" . $row['name'] . ")<br />";
			$allabs .= $abs[0] . "|";
			array_push($donotadd, $abs[0]); //so that it doesn't get added again
		}
	}
	foreach($autoadd as $v) {
		if (!in_array($v, $donotadd)) {
			echo "Adding " . $abs[0] . " (auto)<br />";
			$allabs .= $abs[0] . "|";
			array_push($donotadd, $abs[0]); //so that it doesn't get added again
		}
	}
	echo "New abstratus string: " . $allabs . "<br />";
	mysqli_query($connection, "UPDATE System SET allabstrati = '$allabs'");
	echo "Done!";
}
?>
