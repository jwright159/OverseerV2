<?php
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

header("Content-type: image/png");

$result = mysqli_query($connection, "SELECT * FROM `Art_Submissions` WHERE ID = '".mysqli_real_escape_string($connection, $_GET['id'])."' LIMIT 1;");
$row = mysqli_fetch_array($result);
echo $row['data'];

?>
