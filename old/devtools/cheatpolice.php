<?php
require_once($_SERVER['DOCUMENT_ROOT'] . "/header.php");
if ($accrow['modlevel'] < 10) {
  echo "You don't have sufficient permissions to view the cheat log!";
 } else {
  if ($_POST['clear'] == "true") {
    unlink("cheatpolice.txt");
    clearstatcache(); 
  }
  echo "Have a cheat log.<br />";
  if (filesize("cheatpolice.txt") == 0) {
    echo "<br />";
    echo "The cheat log is empty!<br />";
  } else {
    $cheatlog = fopen("cheatpolice.txt", "r");
    $cheattext = fread($cheatlog,filesize("cheatpolice.txt"));
    echo $cheattext;
    fclose($cheatlog);
  }
  echo '<br /><br /><form action="cheatpolice.php" method="post" id="clear"><input type="hidden" name="clear" id="clear" value="true" /><input type="submit" value="Clear the cheat log" /></form>';
  
 }
 require_once($_SERVER['DOCUMENT_ROOT'] . "/footer.php");
?>
