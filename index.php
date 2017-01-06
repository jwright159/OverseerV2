<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/header.php');

echo '<div class="content">';

if (!isset($currentUser)) {
	echo "<p>You are not logged in.</p>";

	?>
	<ul>
		<li><a href="register.php">Register</a></li>
		<li><a href="login.php">Login</a></li>
	</ul>
	<?php
} else {
	echo "<p>You are logged in as <b>" . $currentUser->getUsername() . "</b>.</p>";
	
	?>
	<ul>
		<li><a href="createsession.php">Create session</a></li>
		<li><a href="charcreate.php">Create character</a></li>
		<li><a href="logout.php">Logout</a></li>
	</ul>

	<?php
	if (!isset($currentCharacter)) {
		echo "<p>You have no character selected.</p>";
		echo "<p>Your characters:<ul>";

		foreach ($currentUser->getCharacters() as $char) {
			echo '<li>' . $char->getName() . ': <a href="forms/selchar.php?id='.$char->getId().'">select</a>.</li>';
		}
		echo "</ul>";
	} else {
		echo "<p>You have selected <b>" . $currentCharacter->getName() . '</b>. <a href="forms/selchar.php">Unselect</a>.</p>';
	}
}

echo '</div>';

require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php');
?>
