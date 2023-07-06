<?php
$pagetitle = "Authoring Module";
require_once $_SERVER['DOCUMENT_ROOT'] . "/header.php";
if ($accrow['modlevel'] < 1) {
  echo "You don't have authoring privileges.";
 } else {
  if (!empty($_POST['text'])) {
    $code = $_POST['code'];
    $result = mysqli_query($connection, "SELECT * FROM Captchalogue WHERE code = '$code'");
    $row = mysqli_fetch_array($result);
    if (empty($row['name'])) echo "Item not found. whaaaaaat<br />";
    elseif (strpos($row['effects'],"READ:")) echo "This item already has text associated with it. (editing text TBA)<br />";
    else {
      $text = mysqli_real_escape_string($connection, $_POST['text']);
      mysqli_query($connection, "INSERT INTO Reading (`text`) VALUES ('$text')");
      $new = mysqli_insert_id($connection);
      $eff = mysqli_real_escape_string($connection, $row['effects'] . "READ:$new|");
      mysqli_query($connection, "UPDATE Captchalogue SET effects = '$eff' WHERE code = '$code'");
      echo "Published text with ID $new for item " . $row['name'] . "!<br />";
    }
  }
  echo "Deepest Lore Generator<br /><br />";
  echo '<form action="authoring.php" method="post" id="author">';
  echo 'Code of item: <input id="code" name="code" type="text" /></br>';
  echo "Text to add (can include HTML code):<br /><textarea id='author' name='text' rows='12' cols='80'></textarea>";
  echo '<input type="submit" value="Publish!" /></form>';
 }
 require_once $_SERVER['DOCUMENT_ROOT'] . "/footer.php";
?>
