<?php


require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/header.php');
//GET VARS
$confirmKey = mysqli_escape_string($connection, $_GET['confkey']);
$emailConfirming = mysqli_escape_string($connection, $_GET['email']);

$confirmQuery = mysqli_fetch_array(mysqli_query($connection, "SELECT * FROM `Users` WHERE `email` = '$emailConfirming' LIMIT 1;"));
$emailCheck = mysqli_query($connection, "SELECT * FROM `Users` WHERE `email` = '$emailConfirming' LIMIT 1;")

if (mysqli_num_rows($emailCheck) > 0) {
	$status = $confirmQuery['confirmed'];
	$confKey = $confirmQuery['confirmationkey'];
	$emailInRow = $confirmQuery['email'];
	if ($status == 1) {
		echo '<div class="container"><div class="alert alert-danger" role="alert">This email is already confirmed!</div></div>';
	} else {
		if ($confKey == $confirmKey) {
			mysqli_query($connection, "UPDATE 'Users' SET 'confirmed' = '1', 'confirmationkey' = '' WHERE 'email' = '$emailConfirming';");
		} else {
			echo '<div class="container"><div class="alert alert-danger" role="alert">Key did not match...</div></div>';
		}
	}
} else {
	echo '<div class="container"><div class="alert alert-danger" role="alert">This email wasn\'t in the database!</div></div>';
}

require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php');
