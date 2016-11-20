<?php

require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/database.php');
function validateEmail($mailto, $user, $confKey) {
	$subject = 'Welcome to Overseer v2!';
	$message = 'Hello '.$user.'!\n
	\n 
	You, or someone pretending to be you, registered for Overseer v2 with this email. To confirm that it\'s actually you, please click the link below to verify your email.\n
	\n
	http://overseer2.com/confirm.php?email='.$mailto.'&confkey='.$confKey.'\n
	\n
	If the link doesn\'t work, head to http://overseer2.com/confirm.php and enter the confirmation key below.\n
	\n'.$confKey.'
	\n
	Regards, the v2 Team';

    $headers = 'From: no-reply@overseer2.com' . "\r\n" .
    'Reply-To: no-reply@overseer2.com' . "\r\n" .
    'X-Mailer: PHP/' . phpversion();
	mail($to, $subject, $message, $headers); 
}

// Set the Post vars
$username = mysqli_escape_string($_POST['username']);
$email = $_POST['email'];
$emailConfirm = $_POST['emailconf'];
$password = mysqli_escape_string($_POST['password']);
$passwordConfirm = mysqli_escape_string($_POST['confirmpw']);
$tosAccepted = mysqli_escape_string($_POST['tos']);

$emailCheck = mysqli_fetch_array(mysqli_query($connection, "SELECT * FROM 'Users' WHERE 'email' = $email;"));
if ($emailCheck == NULL) {
	if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
		$email = mysqli_escape_string($_POST['email']);
		$emailConfirm = mysqli_escape_string($_POST['emailconf']);
		if ($email == $emailConfirm && (!empty($email))) {
			if ($password == $passwordConfirm && (!empty($password))) {
				$usernameCheck = mysqli_fetch_array(mysqli_query($connection, "SELECT * FROM 'Users' WHERE 'username' = $username;"));
				if ($usernameCheck == NULL) {
					$emailHash = substr(md5(rand()), 0, 20);
					$hashedPass = password_hash($password, PASSWORD_DEFAULT);
					mysqli_query($connection, "INSERT INTO 'Users' ('username', 'email', 'password', 'confirmationkey') VALUES ('$username', '$email', '$hashedPass', '$emailHash');");
						validateEmail($email, $username, $emailHash);
						echo '<div class="container"><div class="alert alert-success" role="alert">Success! Check your email for a validation key.</div></div>';
				} else {
					echo '<div class="container"><div class="alert alert-danger" role="alert">Username taken!</div></div>';
				}
			} else {
				echo '<div class="container"><div class="alert alert-danger" role="alert">Passwords didn\'t match.</div></div>';
			}
		} else {
				echo '<div class="container"><div class="alert alert-danger" role="alert">E-mails didn\'t match.</div></div>';
		}	
	} else {
		echo '<div class="container"><div class="alert alert-danger" role="alert">This email is invalid.</div></div>';
	}	
} else {
	echo '<div class="container"><div class="alert alert-danger" role="alert">This email is already registered.</div></div>';
}