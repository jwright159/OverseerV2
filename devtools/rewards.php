<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/header.php";
require_once $_SERVER['DOCUMENT_ROOT'] . "/includes/additem.php";
if ($accrow['modlevel'] < 10) {
  echo "You don't have sufficient permissions! Please go away.";
 } else {
  if (!empty($_POST['gift'])) {
    $_POST['character'] = mysqli_real_escape_string($connection, $_POST['character']);
    $recipientresult = mysqli_query($connection, "SELECT * FROM `Characters` WHERE `Characters`.`ID` = '" . $_POST['character'] . "' LIMIT 1;");
    $recipientrow = mysqli_fetch_array($recipientresult);
    $success = storeItem($recipientrow, $_POST['itemID'], 1, "");
    if ($success) {
      echo "Item successfully given.<br /><br />";
    } else {
      echo "No room in storage!<br /><br />";
    }    
  }
  echo "Welcome to Rewards Inc, home of Rewards Inc. How may I help you?<br /><br />";
  echo '<form action="rewards.php" method="post" id="reward">';
  echo 'Character ID of recipient: <input id="character" name="character" type="text" /></br>';
  echo 'What to gift: <select name="gift">';
  echo '<option value="item">Item (send to storage)</option>';
  echo '</select></br>';
  echo 'ID of item: <input id="itemID" name="itemID" type="text" /></br>';
  echo '<input type="submit" value="Give reward!" /></form>';
 }
 require_once $_SERVER['DOCUMENT_ROOT'] . "/footer.php";
?>
