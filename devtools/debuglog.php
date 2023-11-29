<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/header.php";
if ($accrow['modlevel'] < 10) {
  echo "You don't have sufficient permissions to view the debug log!";
 } else {
  $filepath = dirname(__FILE__) . "/debuglog.txt";
  if ($_POST['clear'] == "true") {
    unlink($filepath);
    clearstatcache(); 
  }
  echo "Have a debug log.<br />";
  if (!file_exists($filepath)) {
    echo "<br />";
    echo "The debug log hasn't been created yet!<br />";
  } elseif (filesize($filepath) === 0) {
    echo "<br />";
    echo "The debug log is empty!<br />";
  } else {
    $debuglog = fopen($filepath, "r");
    $debugtext = fread($debuglog, filesize($filepath));
    echo $debugtext;
    fclose($debuglog);
  }

  echo '<br /><br /><form action="debuglog.php" method="post" id="clear"><input type="hidden" id="clear" name="clear" value="true" /><input type="submit" value="Clear the debug log" /></form>';
  
 }
require_once $_SERVER['DOCUMENT_ROOT'] . "/footer.php";
?>
