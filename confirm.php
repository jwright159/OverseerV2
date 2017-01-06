<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/bootstrap.php');

use Overseer\Models\UserQuery;

$confirmKey = $_GET['confkey'];
$emailConfirming = $_GET['email'];

$user = UserQuery::create()->findOneByEmail($emailConfirming);

if ($user) { // if the user actually exists
	if ($user->getConfirmed() === true) { // if the user is already confirmed
		$flash->warning("This email is already confirmed.");
		redirect_to('/');
	} else {
		if ($user->getConfirmationKey() === $confirmKey) {
			$user->setConfirmed(true); $user->save();
			$flash->success("Email confirmed! You can log in now.");
			redirect_to('/login.php');
		} else {
			$flash->error("Confirmation key did not match!");
			redirect_to('/');
		}
	}
} else {
	$flash->error("This email isn't in the database!");
	redirect_to('/');
}
