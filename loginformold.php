<?php
require_once("header.php");
if ($_SESSION['username'] == "") {
	echo '<form id="login" action="login.php" method="post"> Username: <input id="username" maxlength="100" name="username" type="text" /><br /> Password: <input id="password" maxlength="100" name="password" type="password" /><br />
	<input name="Submit" type="submit" value="Submit" /> </form><br />
	';
} else {
	echo "You are currently logged in as $_SESSION[username]<br />";
}
require_once("footer.php");
?>