<?php
// Overseer v2 Login Code

// Fire up the database connection.
$dbtype = "PDO"; // New-fangled PDO style database stuff.
require_once $_SERVER['DOCUMENT_ROOT'] . '/inc/database.php';


// Grab the user row for specified user.
$userrowquery = $db->prepare("SELECT username,password,ID FROM Users WHERE Users.username = :username");
$userrowquery->bindParam(':username', $_POST['username']);
$userrowquery->execute();

// Check that we're only getting one result, any less and there's no user with that name, any more and shit's fucked up.
if ($userrowquery->rowcount() != 1) {
	$_SESSION['loginmsg'] = 'Invalid username: ' . $_POST['username'] . '<br />we found ' . strval($userrowquery->rowcount()) . ' rows';
	header('Location: /');
	exit();
}

// Store the results from the query in $userrow so that we can use actually use them.
$userrow = $userrowquery->fetch();

// We can already assume that the username is correct, so let's check the password!
if (password_verify($_POST['password'], $userrow['password'])) {
	// The login was successful, start the session and bounce them back to the index.
	session_set_cookie_params(0, '/');
	session_start();
	$_SESSION['username'] = $userrow['username'];
	$_SESSION['userid'] = $userrow['ID'];
	header('Location: /');
} else {
	// The login failed.  Bounce them back to the index and make it yell at them.
	$_SESSION['loginmsg'] = "Invalid password!";
	header('Location: /');
}
