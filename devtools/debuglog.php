<?php
require_once($_SERVER['DOCUMENT_ROOT'] . "/header.php");
if ($accrow['modlevel'] < 10) {
  echo "You don't have sufficient permissions to view the debug log!";
 } else {
  if ($_POST['clear'] == "true") {
    unlink("debuglog.txt");
    clearstatcache(); 
  }
  echo "Have a debug log.<br />";
  if (filesize("debuglog.txt") == 0) {
    echo "<br />";
    echo "The debug log is empty!<br />";
  } else {
    $debuglog = fopen("debuglog.txt", "r");
    $debugtext = fread($debuglog,filesize("debuglog.txt"));
    echo $debugtext;
    fclose($debuglog);
  }

  echo '<br /><br /><form action="debuglog.php" method="post" id="clear"><input type="hidden" id="clear" name="clear" value="true" /><input type="submit" value="Clear the debug log" /></form>';
  
 }
require_once($_SERVER['DOCUMENT_ROOT'] . "/footer.php");
?>
