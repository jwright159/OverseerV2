<?php
require_once("header.php");
if (empty($_SESSION['username'])) {
	echo '
	<form id="login" action="login.php" method="post">
	Username: <input id="username" maxlength="100" name="username" type="text" />
	<br />
	Password: <input id="password" maxlength="100" name="password" type="password" />
	<br />
	Login length: <select name="loginSessionLength" onchange="addThing(this.value)">
	<option value="session">Until you close your browser</option>
	<option value="forever">Forever</option>
	<option value="theirTime">You decide</option>
	</select>
	<script>
	function addThing(dropdownValue)
	{
		if (dropdownValue != "theirTime")
		{
		document.getElementById("damnitDiv").style.visibility="hidden";
		}
		else
		{
		document.getElementById("damnitDiv").style.visibility="visible";
		}
	}
	</script>
	<div id="damnitDiv" style="visibility:hidden">
	Length (in minutes): <input id="loginSessionLength" maxlength="6" name="loginSessionLength" type="text" />
	</div>
	<input name="Submit" type="submit" value="Submit" />
	</form>
	<br />
	';
} else {
	echo "You are currently logged in as $_SESSION[username]<br />";
}
require_once("footer.php");
?>