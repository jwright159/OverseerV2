<?php

// The magical error handling code handler of the future.
set_error_handler(function ($errorNumber, $message, $errfile, $errline) {
	global $errorlog;
	switch ($errorNumber) {
		case E_ERROR: $errorLevel = 'Error'; break;
		case E_WARNING: $errorLevel = 'Warning'; break;
		case E_NOTICE: $errorLevel = 'Notice'; break;
		default : $errorLevel = 'Undefined';
	}
	if (!isset($errorlog)) $errorlog = "<h1>PHP Errors:</h1>\n";
	$errorlog .= '<b>' . $errorLevel . '</b>: ' . $message . ' in <b>'.$errfile . '</b> on line <b>' . $errline . "</b><br>\n";
});

// Start up a session and see if we have a player, otherwise bounce them to index.
session_start(); 
if (empty($_SESSION['username']) or empty($_SESSION['character'])) { 
    if (!stripos($_SERVER['REQUEST_URI'], 'resetpass.php') || !stripos($_SERVER['REQUEST_URI'], 'changelog.php')) {
        header('Location: /'); 
        exit(); 
    }
}

// Fantastic code for tracking page loading time
$loadtime = explode(' ', microtime()); $loadtime = $loadtime[1] + $loadtime[0];

// All of our required things for running this show
require_once $_SERVER['DOCUMENT_ROOT'] . '/inc/autoload.php';
// Nasty hack to enable simultaneous MySQL and PDO.
$dbtype="PDO"; require($_SERVER['DOCUMENT_ROOT'] . '/inc/database.php');
unset($dbtype); require($_SERVER['DOCUMENT_ROOT'] . '/inc/database.php');
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/global_functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/inc/pageload.php';

// Load striferow
if ($charrow['dreamingstatus'] == "Awake") {
	$sid = $charrow['wakeself']; //$sid for strife ID
} else {
	$sid = $charrow['dreamself'];
}
$striferesult = mysqli_query($connection, "SELECT * FROM `Strifers` WHERE `Strifers`.`ID` = $sid LIMIT 1;");
$striferow = mysqli_fetch_array($striferesult);

$me = new \Overseer\Character($db, $_SESSION['character']);

require_once "accrow.php";
$maintResult = mysqli_query($connection, "SELECT * FROM `System` WHERE `Index` = '0';");
$maintRow = mysqli_fetch_array($maintResult);
$maint = isset($maintRow['maint']) ? $maintRow['maint'] : 0;
if ($maint != 0 && $accrow['modlevel'] < 99) { ?>
	<style>
		h1 {text-align:center;}
		p {text-align:center;}
	</style> 
	<title> Overseer v2</title>
	<h1> Overseer v2 is currently 
<?php
	if ($maint == 1) {
		echo 'in VIP Mode!</h1><p>This means that we\'re almost done, and just testing a few things.</p>';
	} elseif ($maint == 2) {
		echo 'down for maintenance!</h1>';
	} ?>
	<p>For more info, and live updates, feel free to join the official <a href="https://discord.gg/NgcS29n">Discord server</a> using either your browser, or the app! </p> <?php
	exit();
	}
?>
<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <title>Overseer v2<?php if (!empty($pagetitle)) echo(" - ".$pagetitle); ?></title>
    <meta name="description" content="A Homestuck-based online game.">
    <link rel="stylesheet" href="/css/overseer.css">
    <style>
      .statbar {  background: url(/images/header/aspect/<?php echo(strtolower($charrow['aspect'])); ?>_statbarcend.png) top right no-repeat,
                              url(/images/header/aspect/<?php echo(strtolower($charrow['aspect'])); ?>_statbarcrepeat.png) top right repeat-x; }
      .statbarinner { background: url(/images/header/aspect/<?php echo(strtolower($charrow['aspect'])); ?>_statbarend.png) top right no-repeat,
                                  url(/images/header/aspect/<?php echo(strtolower($charrow['aspect'])); ?>_statbarrepeat.png) top right repeat-x;
    </style>
  </head>
  <body>
    <div id="layout-container">
      <div id="ad-container">
      </div>
      <div id="content-container">
        <div id="content-header-container">
          <div id="content-header"><img id="pageimg" src="<?php echo($headericon ?? '/images/header/spirograph.png');?>"> <?php $title=$pagetitle ?? ucfirst(pathinfo($_SERVER['PHP_SELF'], PATHINFO_FILENAME)); echo('<div id="content-header-text">' . $title  .'</div>'); ?></div>
        </div>
        <div id="content-area">
<?php // Content goes here!!
if ($charrow['session'] == -1) {
    echo '<h2><b>You have been exiled to Doomheim, the session of the exiled. Enjoy the afterlife, or hope that someone invites you to join their session.</b></h2>';
}