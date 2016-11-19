<?php

require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/database.php');
// email
// emailconf
// password
//confirmpw
//tos

// Set the Post vars
$email = mysqli_escape_string($_POST['email']);
$emailconf = mysqli_escape_string($_POST['emailconf']);
$password = mysqli_escape_string($_POST['password']);
$confirmpw = mysqli_escape_string($_POST['confirmpw']);
$tosAccepted = mysqli_escape_string($_POST['tos']);

