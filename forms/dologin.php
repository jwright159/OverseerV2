<?php
session_start();
require_once __DIR__.'/../includes/bootstrap.php';

use Overseer\Models\UserQuery;

$username = $_POST['username'];
$password = $_POST['password'];
$user = UserQuery::create()->findOneByUsername($username);

if (!$user) {
	// redirect back to the login page, displaying an error
	$flash->error("User doesn't exist!");
	header('Location: /login.php');
} else {
	if (password_verify($password, $user->getPassword())) {
		$flash->success("Login successful!");
		$_SESSION['userId'] = $user->getId();
		$_SESSION['username'] = $user->getUsername();
		header('Location: /');
	} else {
		$flash->error("Invalid username or password.");
		header('Location: /login.php');
	}
}
