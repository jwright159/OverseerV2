<?php
// Overseer v2 Session Creation Code

// Start the session and fire up the database connection.
session_start();
$dbtype = "PDO";
require_once($_SERVER['DOCUMENT_ROOT'] . '/inc/database.php');

// Check if the user is logged in, otherwise bounce them back to the login page.
if (empty($_SESSION['userid'])) {
  $_SESSION['loginmsg'] = "You must be logged in to do that!";
  header('Location: /');
  exit();
}

// Check that the character's name isnt' blank, otherwise error out.
if ($_POST['sessionname'] == "") {
  $_SESSION['loginmsg'] = "Your session's name cannot be blank!";
  header('Location: /?newsession');
  exit();
}

// Check that the character's name consists of only alphanumeric characters and spaces.
if (!preg_match('/^[a-zA-Z0-9 ]*$/', $_POST['charname'])) {
  $_SESSION['loginmsg'] = "You may only use alphanumeric characters in your session's name.";
  header('Location: /?newsession');
  exit();
}

// Trim extra surrounding space from the character's name to eliminate problems.
$_POST['sessionname'] = trim($_POST['sessionname']);

// Grab the user's account line
$accquery = $db->prepare("SELECT ID,username FROM Users WHERE ID = :userid");
$accquery->bindParam(':userid', $_SESSION['userid']);
$accquery->execute();
if ($accquery->rowcount() != 1) {
  $_SESSION['loginmsg'] = "Sorry, your account is fucked royally.";
  header('Location: /?newsession');
  exit();
}
$accrow = $accquery->fetch();
unset($accquery);

// Check that the session hasn't already been taken
$checkquery = $db->prepare("SELECT ID FROM Sessions WHERE name = :session");
$checkquery->bindParam(':session', $_POST['sessionname']);
$checkquery->execute();
if ($checkquery->rowcount() != 0) {
  $_SESSION['loginmsg'] = 'Sorry, that session name is already taken.';
  header('Location: /?newsession');
  exit();
}
unset($checkquery);

if ($_POST['password'] != $_POST['confirmpw']) {
  $_SESSION['loginmsg'] = "Session creation failed: Passwords do not match.";
  header('Location: /?newsession');
  exit();
}

// Insert the session.
$insertquery = $db->prepare("INSERT INTO Sessions (name, password, creator) VALUES (:name, :password, :username)");
$insertquery->bindParam(':name', $_POST['sessionname']);
$insertquery->bindValue(':password', password_hash($_POST['password'], PASSWORD_BCRYPT));
$insertquery->bindParam(':username', $accrow['username']);
$insertquery->execute();
unset($insertquery);

$_SESSION['loginmsg'] = "Creation of session ".$_POST['sessionname']." successful.";
header('Location: /');
