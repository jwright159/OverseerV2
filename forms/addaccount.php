<?php
session_start();
require_once __DIR__.'/../includes/bootstrap.php';

use Overseer\Models\User;
use Overseer\Models\UserQuery;


function sendValidationEmail($mailto, $user, $confKey) {
	$subject = 'Welcome to Overseer v2!';
	$message = "Hello ".$user."!\n
	\n 
	You, or someone pretending to be you, registered for Overseer v2 with this email. To confirm that it\'s actually you, please click the link below to verify your email.\n
	\n
	http://overseer2.com/confirm.php?email=".$mailto."&confkey=".$confKey." \n
	\n
	If the link doesn\'t work, head to http://overseer2.com/confirm.php and enter the confirmation key below.\n
	\n".$confKey."
	\n
	Regards, the v2 Team";

	$headers = 'From: no-reply@overseer2.com' . "\r\n" .
	'Reply-To: no-reply@overseer2.com' . "\r\n" .
	'X-Mailer: PHP/' . phpversion();
	mail($mailto, $subject, $message, $headers); 
}

function validateUsername($username) {
	return (bool) preg_match('/^[\w_\-]+$/', $username) && (strlen($username) <= 32);
}

// Set the Post vars
$username = $_POST['username'];
$email = $_POST['email'];
$emailConfirm = $_POST['emailconf'];
$password = $_POST['password'];
$passwordConfirm = $_POST['confirmpw'];

// normalize email
$email = strtolower($email);
$emailConfirm = strtolower($emailConfirm);

if ($_POST['tos'] != "yes") {
	echo '<div class="container"><div class="alert alert-danger" role="alert">You haven\'t accepted the conditions.</div></div>';
} else {
	$emailCheck = UserQuery::create()->findOneByEmail($email);

	if ($emailCheck) {
		echo '<div class="container"><div class="alert alert-danger" role="alert">This email is already registered.</div></div>';
	} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		echo '<div class="container"><div class="alert alert-danger" role="alert">This email is invalid.</div></div>';
	} elseif ($email !== $emailConfirm || empty($email)) {
		echo '<div class="container"><div class="alert alert-danger" role="alert">E-mails didn\'t match or were empty.</div></div>';
	} elseif (!validateUsername($username)) {
		echo '<div class="container"><div class="alert alert-danger" role="alert">Username is invalid!</div></div>';
	} elseif ($password !== $passwordConfirm || empty($password)) {
		echo '<div class="container"><div class="alert alert-danger" role="alert">Passwords didn\'t match.</div></div>';
	} else {
		$usernameCheck = UserQuery::create()->findOneByUsername($username);

		if ($usernameCheck) {
			echo '<div class="container"><div class="alert alert-danger" role="alert">Username taken!</div></div>';
		} else {
			$confirmationKey = substr(md5(rand()), 0, 20);
			$hashedPass = password_hash($password, PASSWORD_DEFAULT);

			// create and store a new user in the db
			$newUser = new User();
			$newUser->fromArray(["Username" => $username, "Password" => $hashedPass, "Email" => $email,
				"Confirmed" => false, "ConfirmationKey" => $confirmationKey]);
			$newUser->save();

			sendValidationEmail($email, $username, $confirmationKey);
			echo '<div class="container"><div class="alert alert-success" role="alert">Success! Check your email for a validation key.</div></div>';
		}
	}
}
