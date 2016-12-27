<?php

session_start(); // Session begin. Pages utilising the header don't need to repeat this.

if (empty($_SESSION['username'])) {
	if (!stripos($_SERVER['REQUEST_URI'], 'resetpass.php') && !stripos($_SERVER['REQUEST_URI'], 'changelog.php') && !stripos($_SERVER['REQUEST_URI'], 'register.php') && !stripos($_SERVER['REQUEST_URI'], 'confirm.php') && !stripos($_SERVER['REQUEST_URI'], 'login.php') && !stripos($_SERVER['PHP_SELF'], 'index.php')) {
		// TODO: find a better way to do the above
		header('Location: /');
		exit();
	}
}

/*if (!empty($_SESSION['username']) && empty($_SESSION['character'])) {
	header('Location: /charselect.php');
	exit();
}*/

// Fantastic code for tracking page loading time
$loadtime = explode(' ', microtime()); $loadtime = $loadtime[1] + $loadtime[0];

// All of our required things for running this show
require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/database.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/global_functions.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/accrow.php');

$maintResult = mysqli_query($connection, "SELECT * FROM `System` WHERE `Index` = '$system_index';");
$maintRow = mysqli_fetch_array($maintResult);
$maint = $maintRow['maint'];
if ($maint != 0 && $accountRow['modlevel'] < 99 && !stripos($_SERVER['REQUEST_URI'], 'login.php')) {
	?>

<!DOCTYPE html>
<html>
	<head>
		<style>
			h1 { text-align: center; }
			p { text-align: center; }
		</style>
		<title>Overseer v2</title>
	</head>
	<body>
		<?php
			if ($maint == 1 && $accountRow['modlevel'] < 10) {
				echo '<h1>Overseer v2 is currently in VIP Mode!</h1>';
			} elseif ($maint == 2) {
				echo '<h1>Overseer v2 is currently down for maintenance!</h1>';
			}
			// discord widget
			echo '<div style="float:right; margin-right:4vw; margin-left:2vw;"><iframe src="https://discordapp.com/widget?id=76431126977064960&theme=dark" width="350" height="500" allowtransparency="true" frameborder="0"></iframe></div>';
			if ($maint == 1 && $accountRow['modlevel'] < 10) {
				echo '<p>This means that we\'re almost done, and just testing a few things.</p>';
			}
		?>
		<p>For more info, and live updates, feel free to join the official <a href="https://discord.gg/NgcS29n">Discord server</a> using either your browser, or the app!</p>
		<p><a href="/login.php">Login</a></p>
	</body>
</html>

	<?php
	exit();
}
?>

<!DOCTYPE html>
<html>
	<head>
		<title>Overseer 2</title>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link rel="stylesheet" href="includes/css/bootstrap.min.css">
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
		<script src="includes/js/bootstrap.min.js"></script>
	</head>

		<body>
			This is a header.
