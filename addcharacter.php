<?php
// Overseer v2 Character Creation Code

// Start the session and fire up the database connection.
session_start();
$dbtype = "PDO";
require_once $_SERVER['DOCUMENT_ROOT'] . '/inc/database.php';

// Check if the user is logged in, otherwise bounce them back to the login page.
if (empty($_SESSION['userid'])) {
  $_SESSION['loginmsg'] = "You must be logged in to do that!";
  header('Location: /');
  exit();
}

// Grab the user's account line
$accquery = $db->prepare("SELECT ID,username,characters FROM Users WHERE ID = :userid");
$accquery->bindParam(':userid', $_SESSION['userid']);
$accquery->execute();
if ($accquery->rowcount() != 1) {
  $_SESSION['loginmsg'] = "Sorry, your account is fucked royally.";
  header('Location: /?newchar');
  exit();
}
$accrow = $accquery->fetch();
unset($accquery);

// Check that the character's name isnt' blank, otherwise error out.
if ($_POST['charname'] == "") {
  $_SESSION['loginmsg'] = "Your character's name cannot be blank!";
  header('Location: /?newchar');
  exit();
}

// Check that the character's name consists of only alphanumeric characters and spaces.
if (!preg_match('/^[a-zA-Z0-9 ]*$/', $_POST['charname'])) {
  $_SESSION['loginmsg'] = "You may only use alphanumeric characters in your character's name.";
  header('Location: /?newchar');
  exit();
}

// Trim extra surrounding space from the character's name to eliminate problems.
$_POST['charname'] = trim($_POST['charname']);

// Load the session, since we'll need that to check if the password matches and to check for name collisions.
$sessionquery = $db->prepare("SELECT ID,name,password,members FROM Sessions WHERE name = :sessionname");
$sessionquery->bindParam(':sessionname', $_POST['session']);
$sessionquery->execute();
if ($sessionquery->rowcount() != 1) {
  $_SESSION['loginmsg'] = "Sorry, the session you provided doesn't exist.";
  header('Location: /?newchar');
  exit();
}
$sessionrow = $sessionquery->fetch();
unset($sessionquery);

// Check the session's password.
if (!password_verify($_POST['sessionpw'], $sessionrow['password'])) {
  $_SESSION['loginmsg'] = "Sorry, the password that you provided for the session is incorrect.";
  header('Location: /?newchar');
  exit();
}

// Check that the character's name isn't already being used in this session.
$checkquery = $db->prepare("SELECT name FROM Characters WHERE name = :charname AND session = :sessionid");
$checkquery->bindParam(':charname', $_POST['charname']);
$checkquery->bindParam(':sessionid', $sessionrow['ID']);
$checkquery->execute();
if ($checkquery->rowcount() != 0) {
  $_SESSION['loginmsg'] = 'That name is already taken in this session.';
  header('Location: /?newchar');
  exit();
}
unset($checkquery);

// Determine the starting grist bonus
$mems = substr_count($sessionrow['members'], "|") + 1;
if ($mems < 4) {
  $startgrist = pow(10,$mems) * 2;
} else $startgrist = 20000;
$members = $sessionrow['members'];

$grists = "Build_Grist:" . strval($startgrist) . "|";
$time = time(); //For initializing the fatigue timer.
$img = 'images/symbols/aspect_'. strtolower($_POST['aspect']) .'.png'; //location of default player symbol


$stats2 = [                    //put stats you want the character to start with here!
          'creation' => $time,
          //'examplestat' => 0,
          //'examplestat2' => 0,
          ];

$stats = '';
foreach($stats2 as $key => $stat){
  $stats= $stats . $key . ':' . $stat . '|';
}

$achievements = array('created');



$insertchar = $db->prepare("INSERT INTO Characters (name, owner, session, class, aspect, dreamer, symbol, grists, stats, fatiguetimer, invslots) VALUES (:charname, :userid, :session, :class, :aspect, :dreamer, :symbol, :grists, :stats, :time, 25);");
$insertchar->bindParam(':charname', $_POST['charname']);
$insertchar->bindParam(':userid', $_SESSION['userid']);
$insertchar->bindParam(':session', $sessionrow['ID']);
$insertchar->bindParam(':class', $_POST['class']);
$insertchar->bindParam(':aspect', $_POST['aspect']);
$insertchar->bindParam(':dreamer', $_POST['dreamer']);
$insertchar->bindParam(':symbol', $img);
$insertchar->bindParam(':grists', $grists);
$insertchar->bindParam(':stats', $stats);
$insertchar->bindParam(':time', $time);
$insertchar->execute();
$newcharid = $db->lastInsertId();
unset($insertchar);

$addtosession = $db->prepare("UPDATE `Sessions` SET members = :members WHERE ID = :sessionid");
$addtosession->bindValue(':members', $members.strval($newcharid).'|');
$addtosession->bindParam(':sessionid', $sessionrow['ID']);
$addtosession->execute();
unset($addtosession);

$addtoaccount = $db->prepare("UPDATE Users SET characters = :characters WHERE ID = :userid");
$addtoaccount->bindValue(':characters', $accrow['characters'].strval($newcharid).'|');
$addtoaccount->bindParam(':userid', $accrow['ID']);
$addtoaccount->execute();
unset($addtoaccount);

$addstrifer = $db->prepare("INSERT INTO Strifers (name, owner, leader, teamwork, control, health, maxhealth, description) VALUES (:charname, :charid, 1, 100, 1, 10, 10, :description)");
$addstrifer->bindParam(':charname', $_POST['charname']);
$addstrifer->bindParam(':charid', $newcharid);
$addstrifer->bindValue(':description', $_POST['charname']."'s waking self");
$addstrifer->execute();
$wakerowid = $db->lastInsertId();
$addstrifer->bindValue(':description', $_POST['charname']."'s dreamself");
$addstrifer->execute();
$dreamrowid = $db->lastInsertId();
unset($addstrifer);

$updatestrifers = $db->prepare("UPDATE Characters SET wakeself = :wakeid, dreamself = :dreamid WHERE ID = :charid LIMIT 1");
$updatestrifers->bindParam(':wakeid', $wakerowid);
$updatestrifers->bindParam(':dreamid', $dreamrowid);
$updatestrifers->bindParam(':charid', $newcharid);
$updatestrifers->execute();
unset($updatestrifers);

// This is gonna need to be rewritten, I couldn't be bothered to do it right now.
$dbtype = "";
require_once $_SERVER['DOCUMENT_ROOT'] . '/inc/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/global_functions.php';
$charresult = mysqli_query($connection, "SELECT * FROM `Characters` WHERE `ID` = $newcharid LIMIT 1;");
$charrow = mysqli_fetch_array($charresult);
strifeInit($charrow);
echo "Character " . $_POST['charname'] . " has been successfully created!<br />";
echo "You have joined session " . $_POST['session'] . " and have been credited with $startgrist Build Grist.<br />";
$string = "" . $charrow['name'] . " has joined the session!";
notifySession($charrow, $string);

echo '<script>
setTimeout(function() {window.location = "/";}, 3000);
</script>';
