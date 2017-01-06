<?php
session_start(); // Session begin. Pages utilising the header don't need to repeat this.
require_once __DIR__."/bootstrap.php";


//if (empty($_SESSION['username'])) {
//	if (!stripos($_SERVER['REQUEST_URI'], 'resetpass.php') && !stripos($_SERVER['REQUEST_URI'], 'changelog.php') && !stripos($_SERVER['REQUEST_URI'], 'register.php') && !stripos($_SERVER['REQUEST_URI'], 'confirm.php') && !stripos($_SERVER['REQUEST_URI'], 'login.php') && !stripos($_SERVER['PHP_SELF'], 'index.php')) {
//		// TODO: find a better way to do the above
//		header('Location: /');
//		exit();
//	}
//}

if (isset($pageTitle)) {
	$title = $pageTitle.' - Overseer v2';
} else {
	$title = 'Overseer v2';
}

$maint = getMaintLevel();
if (!hasAccessDuringMaint($maint, $currentUser) && !stripos($_SERVER['REQUEST_URI'], 'login.php')) {
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
		<div style="float:right; margin-right:1vw; margin-left:2vw;margin-top:4vw;"><iframe src="https://discordapp.com/widget?id=76431126977064960&theme=dark" width="350" height="500" allowtransparency="true" frameborder="0"></iframe></div>
		<?php
		if (getMaintLevel() === 1) {
			echo '<h1>Overseer v2.5 is currently in VIP Mode!</h1>';
			echo '<p>This means that we\'re almost done, and just testing a few things.</p>';
		} elseif (getMaintLevel() === 2) {
			echo '<h1>Overseer v2.5 is currently down for maintenance!</h1>';
		}?>

		<p>For more info, and live updates, feel free to join the official <a href="https://discord.gg/NgcS29n">Discord server</a>!</p>
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
		<title><?php echo $title; ?></title>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link rel="stylesheet" href="includes/css/bootstrap.min.css">
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
		<script src="includes/js/bootstrap.min.js"></script>
	</head>

		<body>
			<?php
			// display flashed messages
			$flash->display();
			?>
			<div>This is a header.</div>
