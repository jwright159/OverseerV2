<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/head.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/ui/header.php');
echo '<div class="content">';

if (!isset($accountRow)) {
	echo "<p>You are not logged in.</p>";

	?>
	<ul>
		<li><a href="register.php">Register</a></li>
		<li><a href="login.php">Login</a></li>
	</ul>
	<?php
} else {
	echo "<p>You are logged in as " . $accountRow['username'] . ".</p>";
	
	?>
	<ul>
		<li><a href="createsession.php">Create session</a></li>
		<li><a href="charcreate.php">Create character</a></li>
		<li><a href="logout.php">Logout</a></li>
	</ul>

	<?php
	if (!isset($characterRow)) {
		echo "<p>You have no character selected.</p>";
		echo "<p>Your characters:<ul>";

		$charsQuery = mysqli_query($connection, "SELECT `name`, `ID` FROM `Characters` WHERE `owner` = '$accountRow[ID]';");
		while($char = mysqli_fetch_array($charsQuery)) {
			echo '<li>' . $char['name'] . ': <a href="forms/selchar.php?id='.$char['ID'].'">select</a>.</li>';
		}
		echo "</ul>";
	} else {
		echo "<p>You have selected " . $characterRow['name'] . '. <a href="forms/selchar.php">Unselect</a>.</p>';
	}
}

echo '</div>';

require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/ui/footer.php');
?>
