<?php

require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/database.php');
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

// Set the Post vars
$username = mysqli_escape_string($connection, $_POST['username']);
$email = mysqli_escape_string($connection, $_POST['email']);
$emailConfirm = mysqli_escape_string($connection, $_POST['emailconf']);
$password = mysqli_escape_string($connection, $_POST['password']);
$passwordConfirm = mysqli_escape_string($connection, $_POST['confirmpw']);

// normalize email
$email = strtolower($email);
$emailConfirm = strtolower($emailConfirm);

if ($_POST['tos'] != "yes") {
    echo '<div class="container"><div class="alert alert-danger" role="alert">You haven\'t accepted the conditions.</div></div>';
} else {
    $emailCheck = mysqli_query($connection, "SELECT `ID` FROM `Users` WHERE `email` = '$email';");
    if (mysqli_num_rows($emailCheck) > 0) {
        echo '<div class="container"><div class="alert alert-danger" role="alert">This email is already registered.</div></div>';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo '<div class="container"><div class="alert alert-danger" role="alert">This email is invalid.</div></div>';
    } elseif ($email != $emailConfirm || empty($email)) {
        echo '<div class="container"><div class="alert alert-danger" role="alert">E-mails didn\'t match.</div></div>';
    } elseif ($password != $passwordConfirm || empty($password)) {
        echo '<div class="container"><div class="alert alert-danger" role="alert">Passwords didn\'t match.</div></div>';
    } else {
        $usernameCheck = mysqli_query($connection, "SELECT `ID` FROM `Users` WHERE `username` = '$username';");
        if (mysqli_num_rows($usernameCheck) > 0) {
            echo '<div class="container"><div class="alert alert-danger" role="alert">Username taken!</div></div>';
        } else {
            $confirmationKey = substr(md5(rand()), 0, 20);
            $hashedPass = password_hash($password, PASSWORD_DEFAULT);
            $query = "INSERT INTO `Users` (`username`, `email`, `password`, `confirmationkey`) VALUES ('$username', '$email', '$hashedPass', '$confirmationKey');";
            mysqli_query($connection, $query);
            sendValidationEmail($email, $username, $confirmationKey);
            echo '<div class="container"><div class="alert alert-success" role="alert">Success! Check your email for a validation key.</div></div>';
        }
    }
}
