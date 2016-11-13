 <?php
if (empty($_SESSION['username'])) {
  echo "Log in to upload art.</br>";
  echo '</br><a href="/">Home</a> <a href="controlpanel.php">Control Panel</a></br>';
} else {
  $con = mysql_connect("localhost","theovers_DC","pi31415926535");
  if (!$con)
    {
      echo "Connection failed.\n";
      die('Could not connect: ' . mysql_error());
    }
  
  mysql_select_db("theovers_HS", $con);
  if ($userrow['session_name'] != "Developers") {
    echo "And just what do you think YOU'RE doing?";
  } else {
    if (!empty($_POST['file'])) {
      if ($_FILES["file"]["error"] > 0) {
	echo "ERROR! Return Code: " . $_FILES["file"]["error"] . "<br>";
      } else {
	echo "Upload: " . $_FILES["file"]["name"] . "<br>";
	echo "Type: " . $_FILES["file"]["type"] . "<br>";
	echo "Size: " . ($_FILES["file"]["size"] / 1024) . " kB<br>";
	echo "Temp file: " . $_FILES["file"]["tmp_name"] . "<br>";
	//if (file_exists("/www/overseerproject/Images/Items/" . $_FILES["file"]["name"])) {
	//echo $_FILES["file"]["name"] . " already exists. ";
	//} else {
	move_uploaded_file($_FILES["file"]["tmp_name"], "/www/overseerproject" . $_FILES["file"]["name"]);
	echo "Code file stored in: " . "/www/overseerproject" . $_FILES["file"]["name"];
	}
      }
    }
    echo '<html>
         <body>

         <form action="uploadart.php" method="post" enctype="multipart/form-data">
         <label for="file">Filename:</label>
         <input type="file" name="file" id="file"></br>
         <input type="submit" name="submit" value="Submit">
         </form>

         </body>
         </html> ';
  }
}
?> 