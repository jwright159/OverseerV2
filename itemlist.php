<?php
require_once("header.php");
if (empty($_POST['orderby'])) {
	$itemresult = mysqli_query($connection, "SELECT * FROM Captchalogue ORDER BY name ASC;");
} else {
	$itemresult = mysqli_query($connection, "SELECT * FROM Captchalogue ORDER BY " . mysqli_real_escape_string($connection, $_POST['orderby']) . " ASC;");
}
if ($accrow['modlevel'] >= 3) {
	echo "Reorder the list by:<br />";
	echo "<form action='itemlist.php' method='post'>";
	echo "<input type='radio' name='orderby' value='name'>Item Name<br />";
	echo "<input type='radio' name='orderby' value='power'>Base Power<br />";
	echo "<input type='submit' value='Reorder it!'></form><br />";
}
while ($itemrow = mysqli_fetch_array($itemresult)) {
	echo $itemrow['name'];
	if ($accrow['modlevel'] >= 3) { //User has item adding enabled or is a high enough mod level to need this information. Display extra info.
		$power = $itemrow['power'] + floor((max($itemrow['aggrieve'], $itemrow['aggress'], $itemrow['assail'], $itemrow['assault']) + max($itemrow['abuse'], $itemrow['accuse'], $itemrow['abjure'], $itemrow['abstain'])) / 2);
		echo ". Code: " . $itemrow['code'] . ". Abstrati: " . $itemrow['abstratus'] . ". Base power: " . $itemrow['power'] . ". Equivalent power: $power<br />"; 
		echo "Grist_cost:" . $itemrow['gristcosts'] . "<br /> Effects:" . $itemrow['effects'] . "<br />" . "Statuses:" . $itemrow['status'] . "<br />";
	}
	echo "<br />";
}
require_once("footer.php");
?>