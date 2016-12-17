<?php
$pagetitle = "Art Submitter";
$headericon = "/images/header/inventory.png";
require_once("header.php");
require_once("includes/designix.php");
require ("includes/item_render.php");

if ($_SESSION['username'] == "") {
	echo 'Log in to submit art.<br/>';
} else {
	echo 'Art submitter v0.1.2.3.4<br/>';
	echo 'Use the following base when sumbitting your image: <br/>';
	echo '<img src="images/art/emptycard.png" /><br/><br/><br/>';

	$subresult = mysqli_query($connection, "SELECT * FROM `Art_Submissions` WHERE submitter = '$charrow[ID]';");
	$numresults = mysqli_num_rows($subresult);
	if($numresults >= 20) {
		echo 'You have submitted enough art for now! Wait until some of it is approved or rejected.<br/>';
	} else {
		echo 'Your submissions:<br/>';
		while($subrow = mysqli_fetch_array($subresult)) {
			echo '<a href="getartsubmission.php?id='.$subrow['ID'].'">'.$subrow['code'].'</a><br/>';
		}
		echo '<br/><br/>';

		if(isset($_POST['code'])) {
			$iresult = mysqli_query($connection, "SELECT * FROM `Captchalogue` WHERE `code` = '".mysqli_real_escape_string($connection, $_GET['code'])."' LIMIT 1;");
			if(mysqli_num_rows($iresult) == 0) {
				echo 'ERROR: There are no items with that code.<br/>';
			} else {
				$irow = mysqli_fetch_array($iresult);
				echo 'You are submitting art for '.$irow['name'].'.<br/><br/>';
				renderItem2($irow, NULL, "", false);
				echo '<br/>';
				if($irow['session'] != 0) {
					echo 'ERROR: This item is session-bound. You can\'t submit art for it until it becomes approved.<br/>';
				} elseif(!empty($irow['art'] != "")) {
					echo 'ERROR: This item already has art!<br/>';
				} else {
					echo 'Uploading submission...<br/>';
					// ROW SHOULD BE INSERTED HERE
					$file = $_FILES['img'];
					if($file['size'] == 0) {
						echo 'ERROR: File is empty.<br/>';
					} elseif($file['size'] >= 20000) {
						echo 'ERROR: File is too big (the maximum is 20 kB).<br/>';
					} elseif($file['type'] != "image/png") {
						echo 'ERROR: File is not a PNG.<br/>';
					} else {
						$image_info = getimagesize($file['tmp_name']);
						if($image_info[0] != 148 || $image_info[1] != 188) {
							echo 'ERROR: Either the image has the wrong dimensions (148x188) or the uploaded file was not an image.<br/>';
						} else {
							$fp = fopen($file['tmp_name'], 'r');
							$content = fread($fp, filesize($file['tmp_name']));
							$content = mysqli_real_escape_string($connection, $content);
							fclose($fp);

							mysqli_query($connection, "INSERT INTO `Art_Submissions` (submitter, code, data) VALUES ('$charrow[ID]', '$_POST[code]', '$content');");
							echo 'Upload was successful.<br/>';
						}
					}
				}
			}
		} elseif(isset($_GET['code'])) {
			$iresult = mysqli_query($connection, "SELECT * FROM `Captchalogue` WHERE `code` = '".mysqli_real_escape_string($connection, $_GET['code'])."' LIMIT 1;");
			if(mysqli_num_rows($iresult) == 0) {
				echo 'ERROR: There are no items with that code.<br/>';
			} else {
				$irow = mysqli_fetch_array($iresult);
				echo 'You are submitting art for '.$irow['name'].'.<br/><br/>';
				renderItem2($irow, NULL, "", false);
				echo '<br/>';
				if($irow['session'] != 0) {
					echo 'ERROR: This item is session-bound. You can\'t submit art for it until it becomes approved.<br/>';
				} elseif(!empty($irow['art'] != "")) {
					echo 'ERROR: This item already has art!<br/>';
				} else {
					echo '<form action="" method="POST" enctype="multipart/form-data">';
					echo '<input type="hidden" name="code" value="'.$_GET['code'].'" />';
					echo 'Upload your art: ';
					echo '<input name="img" type="file" />';
					echo '<input type="submit" value="Submit!" />';
					echo '</form>';
				}
			}
		}
		echo '<br/>';
		echo '<form action="" method="GET">';
		echo 'Insert the code of the item you wish to submit art for: ';
		echo '<input type="text" name="code" />';
		echo '<input type="submit" value="==>" /><br/>';
		echo '</form>';
	}
}

require_once("footer.php");
?>

