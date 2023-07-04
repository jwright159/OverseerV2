<?php

// password.php
//
// This page is just here for the API server to have access to the PHP
// password utilities.
//
// It's not meant to be used externally.

if($_POST['op'] == "verify") {
	if(password_verify($_POST['password'], $_POST['hash'])) {
		echo 'true';
	} else {
		echo 'false';
	}
} elseif($_POST['op'] == "crypt") {
	echo password_hash($_POST['password'], PASSWORD_BCRYPT);
}

?>
