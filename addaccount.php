<?php
// Overseer v2 Character Creation Code

// Start the session and fire up the database connection.
session_start();
$dbtype = "PDO";
require_once($_SERVER['DOCUMENT_ROOT'] . '/inc/database.php');


// Check that the account name isnt' blank, otherwise error out.
if ($_POST['username'] == "") {
  $_SESSION['loginmsg'] = "Your character's name cannot be blank!";
  header('Location: /?register');
  exit();
}

// Check that the account name consists of only alphanumeric characters and spaces.
if (!preg_match('/^[a-zA-Z0-9 ]*$/', $_POST['username'])) {
  $_SESSION['loginmsg'] = "You may only use alphanumeric characters in your character's name.";
  header('Location: /?register');
  exit();
}

// Trim extra surrounding space from the account name to eliminate problems.
$_POST['username'] = trim($_POST['username']);

// Grab the user's account line
$checkquery = $db->prepare("SELECT ID FROM Users WHERE username = :name");
$checkquery->bindParam(':name', $_POST['username']);
$checkquery->execute();
if ($checkquery->rowcount() != 0) {
  $_SESSION['loginmsg'] = "Sorry, that username is already taken";
  header('Location: /?register');
  exit();
}
unset($checkquery);

if ($_POST['password'] != $_POST['confirmpw']) {
  $_SESSION['loginmsg'] = "Session creation failed: Passwords do not match.";
  header('Location: /?register');
  exit();
}

if ($_POST['email'] != $_POST['cemail']) {
  $_SESSION['loginmsg'] = "Session creation failed: Email address confirmation is incorrect";
  header('Location: /?register');
  exit();
}

if (!isset($_POST['email'])) $_POST['email'] = "";

$insertquery = $db->prepare("INSERT INTO Users (username, password, email) VALUES (:username, :password, :email)");
$insertquery->bindParam(':username', $_POST['username']);
$insertquery->bindValue(':password', crypt($_POST['password']));
$insertquery->bindParam(':email', $_POST['email']);
$insertquery->execute();
unset($insertquery);

$_SESSION['userid'] = $db->lastInsertId();
$_SESSION['username'] = $_POST['username'];
$_SESSION['loginmsg'] = "Account created successfully.  Have fun!";
header('Location: /');
