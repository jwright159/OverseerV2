<?php

session_start(); // Session begin. Pages utilising the header don't need to repeat this. 

if (empty($_SESSION['username'])) { 
    if (!stripos($_SERVER['REQUEST_URI'], 'resetpass.php') || !stripos($_SERVER['REQUEST_URI'], 'changelog.php')) {
        header('Location: /'); 
        exit(); 
    }
}

if (empty($_SESSION['character'])) {
	header('Location: /charselect.php');
	exit();
}

// Fantastic code for tracking page loading time
$loadtime = explode(' ', microtime()); $loadtime = $loadtime[1] + $loadtime[0];

// All of our required things for running this show
require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/database.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/global_functions.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/accrow.php');


?>

<!DOCTYPE html>
<title>Overseer 2</title>
<head>
<meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/css/bootstrap.min.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
  <script src="js/bootstrap.min.js"></script>
</head>


